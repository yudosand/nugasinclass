<?php
namespace App\Support;

use App\Models\{Order, User, CuaninWallet};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;

class CashSplitCorrection
{
    private const LAILA_EXCEPTION_KEY = 'laila-split-indra-20261005';

    // One explicitly agreed exception, bound to the production order and recipient identities.
    private static function allowsLailaDebt(object $account, array $oldIds, array $selected, int $userId, ?CuaninWallet $wallet, int $delta): bool
    {
        if ($account->order_number !== 'ORD-20260917-3WEYS' || (int) $account->total !== 850000 || (int) $account->received !== 500000
            || $oldIds !== [1, 8, 14] || $selected !== [1, 8] || $userId !== 14 || !$wallet || $wallet->id !== 4
            || $wallet->reserved !== 0 || $wallet->balance < 0 || $delta !== -166667 || $wallet->balance + $delta < -50001) return false;
        $names = User::whereIn('id', [1, 8, 14])->pluck('name', 'id')->map(fn ($name) => strtolower(trim($name)))->all();
        return ($names[1] ?? '') === 'kojelzs' && ($names[8] ?? '') === 'faisal' && ($names[14] ?? '') === 'indra'
            && !DB::table('cuanin_entries')->where('entry_key', self::LAILA_EXCEPTION_KEY)->exists();
    }

    public static function prepareLailaDebtColumn(array $draft): void
    {
        if (empty($draft['plan']['negative_exception'])) return;
        // MySQL DDL commits implicitly: execute after password validation, outside the money transaction.
        // Signed storage alone does not authorize other debit paths to overdraw a wallet.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $column = collect(Schema::getColumns('cuanin_wallets'))->firstWhere('name', 'balance');
            if (str_contains(strtolower($column['type'] ?? ''), 'unsigned')) {
                Schema::table('cuanin_wallets', fn (Blueprint $t) => $t->bigInteger('balance')->default(0)->change());
            }
        }
    }

    public static function ensure(): void
    {
        if (!Schema::hasTable('cash_split_changes')) Schema::create('cash_split_changes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('order_id')->index(); $t->string('request_key')->unique();
            $t->json('snapshot'); $t->text('reason'); $t->unsignedBigInteger('actor_id');
            $t->string('actor_name'); $t->timestamp('created_at');
        });
    }

    // Called under the global accounting lock for both preview and commit.
    public static function plan(int $id, array $selected): array
    {
        abort_unless(CashSchema::active(), 409);
        $order = Order::findOrFail($id);
        $account = DB::table('cash_accounts')->where('order_id', $id)->first();
        if (!$account) throw ValidationException::withMessages(['split' => 'Order belum memiliki catatan kas. Catat pembayaran pertama sebelum mengoreksi pembagian.']);
        if ($order->order_number !== $account->order_number || Cuanin::rupiah($order->amount) !== (int) $account->total) throw ValidationException::withMessages(['split' => 'Identitas atau harga order berbeda dari catatan kas. Periksa data sebelum koreksi.']);
        $selected = array_map('intval', $selected); sort($selected);
        if (!$selected || count($selected) !== count(array_unique($selected))) throw ValidationException::withMessages(['admin_ids' => 'Pilih minimal satu admin tanpa duplikasi.']);
        $admins = User::whereIn('id', $selected)->where('role', 'admin')->where('status', 'approved')->orderBy('id')->get()->keyBy('id');
        if ($admins->count() !== count($selected)) throw ValidationException::withMessages(['admin_ids' => 'Penerima harus admin aktif. Muat ulang daftar admin.']);
        $oldIds = array_map('intval', json_decode($account->admin_ids, true)); sort($oldIds);
        if ($oldIds === $selected) throw ValidationException::withMessages(['split' => 'Daftar penerima belum berubah.']);
        $receipts = DB::table('cash_receipts')->where('order_id', $id)->orderBy('id')->get();
        $original = DB::table('cash_allocations')->whereIn('receipt_id', $receipts->pluck('id'))->orderBy('id')->get();
        if ((int) $receipts->sum('amount') !== (int) $account->received) throw ValidationException::withMessages(['split' => 'Total penerimaan tidak cocok dengan catatan order. Periksa catatan kas.']);
        $replacement = []; $rotation = 0;
        foreach ($receipts as $receipt) {
            $items = $original->where('receipt_id', $receipt->id);
            if ((int) $items->sum('amount') !== (int) $receipt->amount) throw ValidationException::withMessages(['split' => 'Total pembagian tidak cocok dengan penerimaan. Periksa catatan kas.']);
            $pool = (int) $items->where('kind', 'admin_share')->sum('amount');
            $count = count($selected); $share = intdiv($pool, $count); $remainder = $pool % $count;
            foreach ($selected as $index => $userId) {
                $amount = $share + (($index - $rotation % $count + $count) % $count < $remainder ? 1 : 0);
                if ($amount) $replacement[] = ['receipt_id' => $receipt->id, 'user_id' => $userId, 'name' => $admins[$userId]->name, 'kind' => 'admin_share', 'amount' => $amount];
            }
            $rotation++;
        }
        $before = $original->where('kind', 'admin_share')->groupBy('user_id')->map(fn ($rows) => (int) $rows->sum('amount'));
        $after = collect($replacement)->groupBy('user_id')->map(fn ($rows) => (int) $rows->sum('amount'));
        $ids = $before->keys()->merge($selected)->unique()->sort()->values();
        $people = User::whereIn('id', $ids)->get()->keyBy('id');
        $wallets = CuaninWallet::whereIn('user_id', $ids)->get()->keyBy('user_id'); $balances = [];
        foreach ($ids as $userId) {
            if (!isset($people[$userId])) throw ValidationException::withMessages(['split' => 'Penerima lama tidak ditemukan. Pulihkan identitas penerima sebelum koreksi.']);
            $delta = ($after[$userId] ?? 0) - ($before[$userId] ?? 0);
            $balance = (int) ($wallets->get($userId)?->balance ?? 0); $reserved = (int) ($wallets->get($userId)?->reserved ?? 0);
            $exception = $balance + $delta < 0 && self::allowsLailaDebt($account, $oldIds, $selected, (int) $userId, $wallets->get($userId), $delta);
            if ($balance + $delta < $reserved && !$exception) throw ValidationException::withMessages(['split' => 'Saldo tersedia '.$people[$userId]->name.' tidak cukup untuk koreksi '.Cuanin::format(-$delta).'. Periksa pencairan selesai atau yang masih ditahan; koreksi belum disimpan.']);
            $balances[$userId] = ['name' => $people[$userId]->name, 'old_share' => $before[$userId] ?? 0, 'new_share' => $after[$userId] ?? 0,
                'delta' => $delta, 'before' => $balance, 'reserved' => $reserved, 'after' => $balance + $delta, 'negative_exception' => $exception];
        }
        return ['order_id' => $id, 'order_number' => $order->order_number, 'total' => (int) $account->total, 'received' => (int) $account->received,
            'old_admin_ids' => $oldIds, 'admin_ids' => $selected, 'original' => $original->map(fn ($r) => (array) $r)->all(),
            'replacement' => $replacement, 'balances' => $balances, 'negative_exception' => collect($balances)->contains('negative_exception', true)];
    }

    public static function fingerprint(array $plan): string { return hash('sha256', json_encode($plan, JSON_THROW_ON_ERROR)); }

    public static function commit(array $draft, User $actor): void
    {
        DB::transaction(function () use ($draft, $actor) {
            Cuanin::lock(); abort_unless(CashSchema::active(), 409);
            if (DB::table('cash_split_changes')->where('request_key', $draft['key'])->exists()) return;
            $plan = self::plan($draft['order_id'], $draft['admin_ids']);
            if (!hash_equals($draft['fingerprint'], self::fingerprint($plan))) throw ValidationException::withMessages(['split' => 'Pembayaran, saldo, atau pembagian berubah. Buat pratinjau baru sebelum menyimpan.']);
            $changeId = DB::table('cash_split_changes')->insertGetId(['order_id' => $draft['order_id'], 'request_key' => $draft['key'],
                'snapshot' => json_encode($plan, JSON_THROW_ON_ERROR), 'reason' => $draft['reason'], 'actor_id' => $actor->id, 'actor_name' => $actor->name, 'created_at' => now()]);
            foreach ($plan['balances'] as $userId => $balance) {
                $wallet = CuaninWallet::firstOrCreate(['user_id' => $userId]);
                if ($balance['negative_exception']) {
                    // plan() has revalidated the exact one-off exception under the accounting lock.
                    DB::table('cuanin_entries')->insert(['wallet_id' => $wallet->id, 'amount' => $balance['delta'], 'kind' => 'split_correction',
                        'entry_key' => self::LAILA_EXCEPTION_KEY, 'description' => 'Koreksi khusus Laila: kekurangan Indra dipotong dari cuan berikutnya (riwayat #'.$changeId.')', 'created_at' => now()]);
                    $wallet->update(['balance' => $balance['after']]);
                } else {
                    Cuanin::entry($wallet, $balance['delta'], 'split_correction', 'split-correction:'.$changeId.':'.$userId,
                        'Koreksi pembagian '.$plan['order_number'].' (riwayat #'.$changeId.')');
                }
            }
            // Original rows are preserved in the immutable audit snapshot; current receipt details show the agreed allocation.
            DB::table('cash_allocations')->whereIn('id', collect($plan['original'])->where('kind', 'admin_share')->pluck('id'))->delete();
            if ($plan['replacement']) DB::table('cash_allocations')->insert($plan['replacement']);
            DB::table('cash_accounts')->where('order_id', $draft['order_id'])->update(['admin_ids' => json_encode($plan['admin_ids'])]);
        }, 3);
    }
}
