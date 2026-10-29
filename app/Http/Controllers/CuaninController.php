<?php
namespace App\Http\Controllers;

use App\Models\CuaninWallet;
use App\Models\CuaninWithdrawal;
use App\Support\Cuanin;
use App\Support\CuaninSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CuaninController extends Controller
{
    public function index(Request $request)
    {
        $ready = CuaninSchema::ready();
        if (!$ready) return view('cuanin.activate');
        $wallet = Cuanin::wallet($request->user());
        $code = DB::table('referral_codes')->where('user_id', $request->user()->id)->value('code');
        $entries = DB::table('cuanin_entries')->where('wallet_id', $wallet->id)->orderByDesc('id')->paginate(20, ['*'], 'history_page');
        $withdrawals = CuaninWithdrawal::where('wallet_id', $wallet->id)->latest()->paginate(10, ['*'], 'withdrawal_page');
        $pending = CuaninWithdrawal::where('wallet_id', $wallet->id)->where('status', 'pending')->first();
        $conversions = DB::table(\App\Support\CashLedger::conversionsTable())->where('owner_id', $request->user()->id)->count();
        $pendingBonus = (int) DB::table(\App\Support\CashLedger::bonusesTable())->where('owner_id', $request->user()->id)->selectRaw('COALESCE(SUM(amount - paid),0) as total')->value('total');
        return view('cuanin.index', compact('wallet', 'code', 'entries', 'withdrawals', 'pending', 'conversions', 'pendingBonus'));
    }
    public function activate(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->validate(['rules_accepted' => 'accepted', 'current_password' => 'required|current_password']);
        try { CuaninSchema::ensureCreated(); }
        catch (\Throwable $exception) {
            if (!CuaninSchema::ready()) return back()->with('error', 'Aktivasi belum selesai. Periksa izin CREATE/ALTER database dan coba lagi. Data lama tidak perlu dihapus.');
        }
        return redirect()->route('cuanin.index')->with('success', 'Cuanin dan pendaftaran marketing aktif. Pembagian berlaku pada pembayaran yang disetujui mulai sekarang.');
    }
    public function bank(Request $request)
    {
        $data = $request->validate(['bank_name' => 'required|string|max:100', 'account_name' => 'required|string|max:255', 'account_number' => ['required', 'string', 'regex:/^[0-9]{5,30}$/D'], 'current_password' => 'required|current_password']);
        DB::transaction(function () use ($request, $data) {
            Cuanin::lock(); $wallet = Cuanin::wallet($request->user());
            $wallet->update(collect($data)->only(['bank_name', 'account_name', 'account_number'])->all());
        }, 3);
        return back()->with('success', 'Data rekening tersimpan. Rekening pada pengajuan yang sudah dibuat tetap menggunakan data saat pengajuan.');
    }
    public function withdraw(Request $request)
    {
        abort_if(\App\Support\CashSchema::installed() && !\App\Support\CashSchema::active(), 409, 'Pencairan sementara ditunda sampai pencocokan kas awal selesai.');
        // Accept Indonesian thousands separators only; never strip arbitrary characters.
        $amount = $request->input('amount');
        if (is_string($amount) && preg_match('/^[1-9][0-9]{0,2}(?:\.[0-9]{3})+$/D', $amount)) {
            $request->merge(['amount' => str_replace('.', '', $amount)]);
        }
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'current_password' => 'required|current_password',
            'request_key' => 'required|uuid',
        ]);
        $withdrawal = DB::transaction(function () use ($request, $data) {
            Cuanin::lock(); $wallet = Cuanin::wallet($request->user());
            abort_if(\App\Support\CashSchema::installed() && !\App\Support\CashSchema::active(), 409, 'Selesaikan pencocokan kas awal terlebih dahulu.');
            if ($existing = CuaninWithdrawal::where('request_key', $data['request_key'])->first()) {
                abort_unless((int) $existing->wallet_id === (int) $wallet->id, 409); return $existing;
            }
            if (CuaninWithdrawal::where('wallet_id', $wallet->id)->where('status', 'pending')->exists()) throw ValidationException::withMessages(['withdrawal' => 'Pengajuan sebelumnya masih diproses.']);
            if (!$wallet->account_number || !$wallet->account_name || !$wallet->bank_name) throw ValidationException::withMessages(['bank' => 'Lengkapi rekening terlebih dahulu.']);
            $available = $wallet->available();
            if ($available <= 0) throw ValidationException::withMessages(['balance' => 'Belum ada saldo tersedia untuk dicairkan.']);
            $amount = (int) $data['amount'];
            if ($amount > $available) throw ValidationException::withMessages(['amount' => 'Nominal pencairan tidak boleh melebihi saldo tersedia.']);
            if (\App\Support\CashSchema::active()) {
                $cash = (int) DB::table('cash_state')->where('id', 1)->value('balance');
                $reserved = (int) DB::table('cuanin_wallets')->sum('reserved');
                if ($amount > $cash - $reserved) throw ValidationException::withMessages(['amount' => 'Kas tersedia belum mencukupi setelah memperhitungkan pengajuan tim lainnya.']);
            }
            $withdrawal = CuaninWithdrawal::create(['wallet_id' => $wallet->id, 'amount' => $amount, 'request_key' => $data['request_key'], 'bank_snapshot' => ['bank_name' => $wallet->bank_name, 'account_name' => $wallet->account_name, 'account_number' => $wallet->account_number]]);
            $wallet->reserved += $amount; $wallet->save(); return $withdrawal;
        }, 3);
        return redirect()->away($this->whatsapp($withdrawal));
    }
    public function withdrawal(Request $request, $id)
    {
        abort_unless(CuaninSchema::ready(), 409);
        $withdrawal = CuaninWithdrawal::with('wallet.user')->findOrFail($id);
        abort_unless((int) $withdrawal->wallet->user_id === (int) $request->user()->id || $request->user()->isAdmin(), 403);
        $bank = $withdrawal->bank_snapshot;
        $whatsapp = $this->whatsapp($withdrawal);
        return view('cuanin.withdrawal', compact('withdrawal', 'bank', 'whatsapp'));
    }
    private function whatsapp(CuaninWithdrawal $withdrawal): string
    {
        $bank = $withdrawal->bank_snapshot;
        $message = 'Halo, saya ingin mencairkan '.Cuanin::format($withdrawal->amount).' Cuan saya ke rekening ini ya: '.$bank['bank_name'].', '.$bank['account_number'].', a.n. '.$bank['account_name'].'. Pengajuan #CUAN-'.$withdrawal->id.'.';
        return 'https://wa.me/6285861015161?text='.rawurlencode($message);
    }
    public function requests()
    {
        abort_unless(CuaninSchema::ready(), 409);
        $requests = CuaninWithdrawal::with('wallet.user')->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest()->paginate(20);
        return view('cuanin.requests', compact('requests'));
    }
    public function process(Request $request, $id)
    {
        $data = $request->validate(['current_password' => 'required|current_password', 'action' => 'required|in:paid,rejected', 'payment_reference' => 'required|string|max:255', 'confirmed' => 'accepted']);
        DB::transaction(function () use ($request, $data, $id) {
            Cuanin::lock(); $withdrawal = CuaninWithdrawal::lockForUpdate()->findOrFail($id);
            if ($withdrawal->status !== 'pending') throw ValidationException::withMessages(['withdrawal' => 'Pengajuan sudah diproses sebelumnya.']);
            $wallet = CuaninWallet::lockForUpdate()->findOrFail($withdrawal->wallet_id);
            if ($wallet->reserved < $withdrawal->amount || $wallet->balance < $withdrawal->amount) throw ValidationException::withMessages(['balance' => 'Saldo pengajuan perlu diperiksa.']);
            $wallet->reserved -= $withdrawal->amount; $wallet->save();
            if ($data['action'] === 'paid') {
                Cuanin::entry($wallet, -$withdrawal->amount, 'withdrawal', 'withdrawal:'.$withdrawal->id, 'Pencairan #CUAN-'.$withdrawal->id);
                if (\App\Support\CashSchema::active()) \App\Support\CashLedger::entry(-$withdrawal->amount, 'withdrawal', 'withdrawal:'.$withdrawal->id, $wallet->user->name, 'Pencairan #CUAN-'.$withdrawal->id.' • '.$data['payment_reference'], $request->user()->id, withdrawal: $withdrawal->id, user: $wallet->user_id);
            }
            $withdrawal->update(['status' => $data['action'], 'processed_by' => $request->user()->id, 'processed_at' => now(), 'payment_reference' => $data['payment_reference']]);
        }, 3);
        return back()->with('success', $data['action'] === 'paid' ? 'Pencairan dicatat selesai; transaksi OUT telah dibukukan.' : 'Pengajuan ditolak; saldo yang ditahan kembali tersedia.');
    }
}
