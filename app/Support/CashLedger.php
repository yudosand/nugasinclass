<?php
namespace App\Support;

use App\Models\{Order, User, CuaninWallet};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashLedger
{
    public static function people(): array { return User::orderBy('id')->get(['id', 'name', 'role'])->keyBy('id')->toArray(); }
    public static function conversionsTable(): string { return CashSchema::active() ? 'cash_conversions' : 'referral_conversions'; }
    public static function bonusesTable(): string { return CashSchema::active() ? 'cash_bonuses' : 'cuanin_bonus_awards'; }

    public static function sources(): array
    {
        $sources = [];
        $admins = User::where('role', 'admin')->where('status', 'approved')->orderBy('id')->pluck('id')->all();
        $orders = Order::with('user')->orderBy('id')->get()->keyBy('id');
        $settlements = DB::table('cuanin_settlements')->orderBy('id')->get()->keyBy('order_id');
        $referrals = DB::table('order_referrals')->get()->keyBy('order_id');
        foreach ($orders->keys()->merge($settlements->keys())->unique()->sort()->values() as $id) {
            $order = $orders->get($id); $settlement = $settlements->get($id); $referral = $referrals->get($id);
            $snapshotAdmins = $settlement ? collect(json_decode($settlement->allocations, true))->where('kind', 'Bagian admin')->pluck('user_id')->unique()->values()->all() : $admins;
            $sources[$id] = ['order_id' => (int) $id, 'order_number' => $order?->order_number ?? $settlement->order_number,
                'client_id' => $order?->user_id, 'client_name' => $order?->user?->name ?? 'Client lama (akun dihapus)',
                'total' => Cuanin::rupiah($order?->amount ?? $settlement->gross), 'received' => 0,
                'admin_ids' => $snapshotAdmins, 'referral_owner_id' => $settlement?->referral_owner_id ?? $referral?->owner_id,
                'referral_code' => $settlement?->referral_code ?? $referral?->code];
        }
        return $sources;
    }

    public static function paidTotals(): array
    {
        return DB::table('cuanin_withdrawals')->join('cuanin_wallets', 'cuanin_wallets.id', '=', 'cuanin_withdrawals.wallet_id')
            ->where('cuanin_withdrawals.status', 'paid')->groupBy('cuanin_wallets.user_id')
            ->selectRaw('cuanin_wallets.user_id, SUM(cuanin_withdrawals.amount) as total')->pluck('total', 'user_id')->map(fn ($v) => (int) $v)->all();
    }

    public static function fingerprint(): string
    {
        return hash('sha256', json_encode([
            self::sources(), User::orderBy('id')->get(['id', 'role', 'status', 'name']),
            DB::table('orders')->orderBy('id')->get(['id', 'payment_status', 'payment_proof', 'updated_at']),
            DB::table('cuanin_wallets')->orderBy('id')->get(['id', 'user_id', 'balance', 'reserved']),
            DB::table('cuanin_withdrawals')->orderBy('id')->get(['id', 'status', 'amount', 'updated_at']),
            DB::table('cuanin_entries')->max('id'), config('marketing.bonuses'),
        ], JSON_THROW_ON_ERROR));
    }

    public static function plan(array $received, array $withdrawn): array
    {
        if (DB::table('cuanin_withdrawals')->where('status', 'pending')->exists() || DB::table('cuanin_wallets')->where('reserved', '>', 0)->exists()) {
            throw ValidationException::withMessages(['cash' => 'Selesaikan atau tolak pengajuan pencairan yang masih pending sebelum mencocokkan kas awal.']);
        }
        $sources = self::sources(); $people = self::people(); $knownPaid = self::paidTotals();
        $wallets = CuaninWallet::all()->keyBy('user_id');
        $members = collect($people)->filter(fn ($person, $id) => in_array($person['role'], ['admin', 'marketing', 'marketing_deleted'], true) || isset($wallets[$id]));
        if (array_diff(array_keys($sources), array_keys($received)) || array_diff(array_keys($received), array_keys($sources)) ||
            array_diff($members->keys()->all(), array_keys($withdrawn)) || array_diff(array_keys($withdrawn), $members->keys()->all())) {
            throw ValidationException::withMessages(['cash' => 'Daftar order atau penerima berubah. Muat ulang formulir pencocokan.']);
        }
        $state = ['conversions' => [], 'bonuses' => [], 'rotation' => 0]; $rows = []; $earned = []; $incoming = 0;
        foreach ($sources as $id => $account) {
            $amount = (int) $received[$id];
            if ($amount < 0 || $amount > $account['total']) throw ValidationException::withMessages(['cash' => 'Pembayaran '.$account['order_number'].' harus antara 0 dan total harga order.']);
            $allocations = $amount ? CashAllocation::split($account, $amount, $people, $state) : [];
            foreach ($allocations as $allocation) $earned[$allocation['user_id']] = ($earned[$allocation['user_id']] ?? 0) + $allocation['amount'];
            $rows[] = ['account' => $account, 'amount' => $amount, 'allocations' => $allocations]; $incoming += $amount;
        }
        $balances = []; $outgoing = 0;
        foreach ($members as $id => $person) {
            $paid = (int) $withdrawn[$id];
            if ($paid < ($knownPaid[$id] ?? 0)) throw ValidationException::withMessages(['cash' => 'Total pencairan '.$person['name'].' tidak boleh lebih kecil dari pencairan selesai yang sudah tercatat.']);
            $balance = ($earned[$id] ?? 0) - $paid;
            if ($balance < 0) throw ValidationException::withMessages(['cash' => 'Pencairan '.$person['name'].' melebihi bagian dari pembayaran nyata. Periksa penerima pembagian/catatan pembayaran terlebih dahulu; saldo tidak diubah.']);
            $balances[$id] = ['name' => $person['name'], 'earned' => $earned[$id] ?? 0, 'paid' => $paid, 'before' => (int) ($wallets[$id]?->balance ?? 0), 'after' => $balance];
            $outgoing += $paid;
        }
        return ['rows' => $rows, 'balances' => $balances, 'state' => $state, 'incoming' => $incoming, 'outgoing' => $outgoing,
            'balance' => $incoming - $outgoing, 'outstanding' => array_sum(array_column($sources, 'total')) - $incoming];
    }

    // All mutations use the existing global Cuanin lock; approval, payout and reconciliation serialize.
    public static function reconcile(array $draft, int $actor): void
    {
        DB::transaction(function () use ($draft, $actor) {
            Cuanin::lock();
            if (CashSchema::active()) throw ValidationException::withMessages(['cash' => 'Kas awal sudah dicocokkan. Jangan mengimpor ulang saldo awal.']);
            if (!hash_equals(self::fingerprint(), $draft['fingerprint'])) throw ValidationException::withMessages(['cash' => 'Data berubah sejak pratinjau dibuat. Buat pratinjau baru sebelum menyimpan.']);
            $plan = self::plan($draft['received'], $draft['withdrawn']);
            foreach ($plan['rows'] as $row) {
                $account = $row['account']; $amount = $row['amount'];
                DB::table('cash_accounts')->insert([...$account, 'admin_ids' => json_encode($account['admin_ids']), 'received' => $amount]);
                if ($amount) {
                    $receipt = DB::table('cash_receipts')->insertGetId(['order_id' => $account['order_id'], 'amount' => $amount,
                        'request_key' => 'opening:'.$account['order_id'], 'reference' => 'SALDO-AWAL-'.$account['order_number'],
                        'proof' => Order::whereKey($account['order_id'])->value('payment_proof'),
                        'actor_id' => $actor, 'received_at' => now(), 'created_at' => now()]);
                    foreach ($row['allocations'] as $allocation) DB::table('cash_allocations')->insert(['receipt_id' => $receipt, ...$allocation]);
                    self::entry($amount, 'receipt', 'receipt:'.$receipt, $account['client_name'], 'Penerimaan awal terverifikasi '.$account['order_number'], $actor, receipt: $receipt);
                }
                Order::whereKey($account['order_id'])->update(['payment_status' => $amount === $account['total'] ? 'approved' : 'pending']);
            }
            foreach ($plan['balances'] as $id => $balance) {
                $wallet = CuaninWallet::firstOrCreate(['user_id' => $id]);
                Cuanin::entry($wallet, $balance['after'] - $balance['before'], 'cash_reconcile', 'cash-opening:'.$id, 'Pencocokan kas awal: pembayaran nyata dan total pencairan');
                if ($balance['paid']) self::entry(-$balance['paid'], 'opening_withdrawal', 'opening-withdrawal:'.$id, $balance['name'], 'Total pencairan sebelum pencocokan (termasuk yang sudah tercatat)', $actor, user: $id);
            }
            self::saveAllocationState($plan['state']);
            DB::table('cash_reconciliations')->insert(['request_key' => $draft['key'], 'actor_id' => $actor,
                'snapshot' => json_encode(['plan' => $plan, 'note' => $draft['note'], 'fingerprint' => $draft['fingerprint']], JSON_THROW_ON_ERROR), 'created_at' => now()]);
            DB::table('cash_state')->where('id', 1)->update(['active' => true, 'activated_at' => now(), 'note' => $draft['note']]);
        }, 3);
    }

    public static function record(Order $order, array $data, int $actor): void
    {
        DB::transaction(function () use ($order, $data, $actor) {
            Cuanin::lock(); abort_unless(CashSchema::active(), 409, 'Cocokkan kas awal terlebih dahulu.');
            if ($existing = DB::table('cash_receipts')->where('request_key', $data['request_key'])->first()) {
                abort_unless((int) $existing->order_id === $order->id && (int) $existing->amount === (int) $data['amount'], 409); return;
            }
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if (DB::table('cash_receipts')->where('reference', $data['reference'])->exists()) throw ValidationException::withMessages(['reference' => 'Referensi transfer ini sudah dicatat. Periksa riwayat agar tidak menghitung dua kali.']);
            $account = self::account($order);
            $amount = (int) $data['amount'];
            if ($amount < 1 || $amount > $account['total'] - $account['received']) throw ValidationException::withMessages(['amount' => 'Nominal harus positif dan tidak boleh melebihi sisa tagihan.']);
            $state = self::allocationState();
            $allocations = CashAllocation::split($account, $amount, self::people(), $state);
            $receipt = DB::table('cash_receipts')->insertGetId(['order_id' => $order->id, 'amount' => $amount, 'request_key' => $data['request_key'],
                'reference' => $data['reference'], 'proof' => $order->payment_status === 'paid' ? $order->payment_proof : null,
                'actor_id' => $actor, 'received_at' => $data['received_at'], 'created_at' => now()]);
            foreach ($allocations as $index => $allocation) {
                DB::table('cash_allocations')->insert(['receipt_id' => $receipt, ...$allocation]);
                $wallet = CuaninWallet::firstOrCreate(['user_id' => $allocation['user_id']]);
                Cuanin::entry($wallet, $allocation['amount'], $allocation['kind'], 'cash:'.$receipt.':'.$index, 'Pembayaran '.$order->order_number.' • '.$data['reference']);
            }
            self::entry($amount, 'receipt', 'receipt:'.$receipt, $account['client_name'], 'Pembayaran '.$order->order_number.' • '.$data['reference'], $actor, receipt: $receipt, occurred: $data['received_at']);
            DB::table('cash_accounts')->where('order_id', $order->id)->update(['received' => $account['received'] + $amount]);
            self::saveAllocationState($state);
            $order->update(['payment_status' => $account['received'] + $amount === $account['total'] ? 'approved' : 'pending', 'notes' => $data['notes'] ?? $order->notes]);
            if (!empty($data['activate_client'])) $order->user->update(['status' => 'approved', 'rejection_reason' => null]);
        }, 3);
    }

    public static function correctOrder(int $id, array $data, User $actor): void
    {
        DB::transaction(function () use ($id, $data, $actor) {
            Cuanin::lock(); abort_unless(CashSchema::active(), 409, 'Cocokkan kas awal terlebih dahulu.');
            if ($existing = DB::table('cash_order_changes')->where('request_key', $data['request_key'])->first()) {
                abort_unless((int) $existing->order_id === $id && (int) $existing->new_total === (int) $data['new_total']
                    && (int) $existing->new_package_id === (int) $data['package_id'] && (int) $existing->actor_id === $actor->id
                    && $existing->reason === $data['reason'], 409);
                return;
            }
            $order = Order::lockForUpdate()->findOrFail($id);
            $oldTotal = Cuanin::rupiah($order->amount); $newTotal = (int) $data['new_total'];
            if (Exams::isOrder($id) && (int)$data['package_id'] !== (int)$order->package_id) throw ValidationException::withMessages(['package_id'=>'Paket soal pada pembelian UAS tidak dapat diganti. Buat pembelian terpisah untuk mata kuliah lain.']);
            if ($oldTotal !== (int) $data['expected_total'] || (int) $order->package_id !== (int) $data['expected_package_id']) {
                throw ValidationException::withMessages(['correction' => 'Order sudah berubah. Muat ulang halaman dan periksa harga terbaru.']);
            }
            $snapshot = DB::table('cash_accounts')->where('order_id', $id)->first();
            if ($snapshot && (int) $snapshot->total !== $oldTotal) throw ValidationException::withMessages(['correction' => 'Harga order dan catatan kas tidak cocok. Koreksi dibatalkan.']);
            $received = (int) ($snapshot->received ?? 0);
            if ($received >= $oldTotal) throw ValidationException::withMessages(['correction' => 'Order sudah lunas. Koreksi ini hanya untuk order yang masih memiliki sisa tagihan.']);
            if ($newTotal < 1 || $newTotal < $received) throw ValidationException::withMessages(['new_total' => 'Harga baru tidak boleh lebih kecil dari pembayaran yang sudah diterima.']);
            $package = \App\Models\Package::whereKey($data['package_id'])->where(function ($q) use ($order) {
                $q->where('is_active', true)->orWhere('id', $order->package_id);
            })->first();
            if (!$package) throw ValidationException::withMessages(['package_id' => 'Paket tidak tersedia. Pilih paket aktif.']);
            if ($newTotal === $oldTotal && $package->id === $order->package_id) throw ValidationException::withMessages(['correction' => 'Tidak ada perubahan paket atau harga.']);
            DB::table('cash_order_changes')->insert(['order_id' => $id, 'order_number' => $order->order_number,
                'old_total' => $oldTotal, 'new_total' => $newTotal, 'received' => $received,
                'old_package_id' => $order->package_id, 'new_package_id' => $package->id,
                'old_package' => $order->package?->name ?? 'Paket lama', 'new_package' => $package->name,
                'reason' => $data['reason'], 'actor_id' => $actor->id, 'actor_name' => $actor->name,
                'request_key' => $data['request_key'], 'created_at' => now()]);
            if ($snapshot) DB::table('cash_accounts')->where('order_id', $id)->update(['total' => $newTotal]);
            $order->update(['amount' => $newTotal, 'package_id' => $package->id,
                'payment_status' => $received === $newTotal ? 'approved' : $order->payment_status]);
            if ($snapshot && $received === $newTotal) {
                $state = self::allocationState();
                CashAllocation::split(self::account($order), 0, self::people(), $state);
                self::saveAllocationState($state);
            }
        }, 3);
    }

    public static function account(Order $order): array
    {
        $existing = DB::table('cash_accounts')->where('order_id', $order->id)->first();
        if ($existing && (int) $existing->total !== Cuanin::rupiah($order->amount)) throw ValidationException::withMessages(['cash' => 'Harga order berbeda dari catatan kas. Periksa perubahan harga sebelum mencatat pembayaran.']);
        if (!$existing) {
            $referral = DB::table('order_referrals')->where('order_id', $order->id)->first();
            DB::table('cash_accounts')->insert(['order_id' => $order->id, 'order_number' => $order->order_number, 'client_id' => $order->user_id,
                'client_name' => $order->user->name, 'total' => Cuanin::rupiah($order->amount), 'received' => 0,
                'admin_ids' => json_encode(User::where('role', 'admin')->where('status', 'approved')->orderBy('id')->pluck('id')->all()),
                'referral_owner_id' => $referral?->owner_id, 'referral_code' => $referral?->code]);
            $existing = DB::table('cash_accounts')->where('order_id', $order->id)->first();
        }
        $result = (array) $existing; $result['admin_ids'] = json_decode($result['admin_ids'], true); return $result;
    }

    public static function entry(int $amount, string $kind, string $key, string $party, string $description, int $actor, ?int $receipt = null, ?int $withdrawal = null, ?int $user = null, mixed $occurred = null): void
    {
        if (DB::table('cash_entries')->where('entry_key', $key)->exists()) return;
        $balance = (int) DB::table('cash_state')->where('id', 1)->value('balance') + $amount;
        if ($balance < 0) throw ValidationException::withMessages(['cash' => 'Kas Nugasin tidak mencukupi. Periksa pembayaran dan pencairan.']);
        DB::table('cash_entries')->insert(['amount' => $amount, 'balance_after' => $balance, 'kind' => $kind, 'entry_key' => $key, 'party' => $party,
            'description' => mb_substr($description, 0, 255), 'actor_id' => $actor, 'receipt_id' => $receipt, 'withdrawal_id' => $withdrawal, 'user_id' => $user,
            'occurred_at' => $occurred ?? now(), 'created_at' => now()]);
        DB::table('cash_state')->where('id', 1)->update(['balance' => $balance]);
    }

    private static function allocationState(): array
    {
        return ['rotation' => (int) DB::table('cash_state')->where('id', 1)->value('rotation'),
            'conversions' => DB::table('cash_conversions')->pluck('owner_id', 'client_id')->map(fn ($id) => (int) $id)->all(),
            'bonuses' => DB::table('cash_bonuses')->orderBy('id')->get()->mapWithKeys(fn ($b) => [$b->owner_id.':'.$b->threshold => (array) $b])->all()];
    }
    private static function saveAllocationState(array $state): void
    {
        foreach ($state['conversions'] as $client => $owner) DB::table('cash_conversions')->insertOrIgnore(['client_id' => $client, 'owner_id' => $owner, 'created_at' => now()]);
        foreach ($state['bonuses'] as $bonus) DB::table('cash_bonuses')->updateOrInsert(['owner_id' => $bonus['owner_id'], 'threshold' => $bonus['threshold']], ['amount' => $bonus['amount'], 'paid' => $bonus['paid'], 'created_at' => $bonus['created_at'] ?? now()]);
        DB::table('cash_state')->where('id', 1)->update(['rotation' => $state['rotation']]);
    }
}
