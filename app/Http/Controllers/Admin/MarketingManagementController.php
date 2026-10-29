<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuaninWallet;
use App\Models\User;
use App\Support\Cuanin;
use App\Support\CuaninSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MarketingManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $ready = CuaninSchema::ready();
        $totalMarketing = User::where('role', 'marketing')->count();
        $query = User::where('role', 'marketing')->select(['id', 'name', 'email', 'status', 'created_at']);
        if ($ready) {
            $query->addSelect([
                'referral_code' => DB::table('referral_codes')->select('code')->whereColumn('user_id', 'users.id'),
                'successful_clients' => DB::table(\App\Support\CashLedger::conversionsTable())->selectRaw('COUNT(*)')->whereColumn('owner_id', 'users.id'),
            ]);
        }
        $query->when($search !== '', function ($query) use ($search, $ready) {
            $query->where(function ($sub) use ($search, $ready) {
                $sub->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
                if ($ready) $sub->orWhereExists(function ($code) use ($search) {
                    $code->selectRaw('1')->from('referral_codes')->whereColumn('user_id', 'users.id')->where('code', 'like', '%'.$search.'%');
                });
            });
        });
        $marketing = $query->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();
        return view('admin.marketing.index', compact('marketing', 'search', 'totalMarketing', 'ready'));
    }

    public function confirmDelete($id)
    {
        $marketing = User::where('role', 'marketing')->findOrFail($id);
        $ready = CuaninSchema::ready();
        $blockers = $ready ? $this->deletionBlockers($marketing) : ['Aktifkan Cuanin terlebih dahulu untuk memeriksa pembukuan akun.'];
        return view('admin.marketing.delete', compact('marketing', 'blockers'));
    }

    public function destroy(Request $request, $id)
    {
        $marketing = User::where('role', 'marketing')->findOrFail($id);
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirm_email' => ['required', 'string', Rule::in([$marketing->email])],
        ], ['confirm_email.in' => 'Ketik email marketing yang akan dihapus dengan tepat.', 'current_password.current_password' => 'Password admin Anda tidak sesuai.']);

        DB::transaction(function () use ($marketing, $request) {
            // Use the same lock as registration, approval, and withdrawals so no new liability can race deletion.
            Cuanin::lock();
            $marketing = User::where('role', 'marketing')->lockForUpdate()->findOrFail($marketing->id);
            if ($blockers = $this->deletionBlockers($marketing)) {
                throw ValidationException::withMessages(['marketing' => implode(' ', $blockers)]);
            }
            Password::broker()->deleteToken($marketing);
            DB::table('sessions')->where('user_id', $marketing->id)->delete();
            // Retain the financial identity and foreign keys; remove login access and the active marketing account.
            $marketing->forceFill([
                'role' => 'marketing_deleted', 'status' => 'rejected',
                'password' => Str::random(64), 'remember_token' => Str::random(60),
                'rejection_reason' => 'Akun marketing dihapus oleh admin #'.$request->user()->id.' pada '.now()->toIso8601String(),
            ])->save();
        }, 3);

        return redirect()->route('admin.marketing.index')->with('success', 'Marketing berhasil dihapus dari daftar aktif. Login dan kode referral dinonaktifkan; riwayat transaksi tetap tersimpan.');
    }

    private function deletionBlockers(User $marketing): array
    {
        $blockers = [];
        $wallet = CuaninWallet::where('user_id', $marketing->id)->first();
        if ($wallet && ($wallet->balance !== 0 || $wallet->reserved !== 0)) {
            $blockers[] = 'Selesaikan saldo Cuanin dan dana yang ditahan terlebih dahulu.';
        }
        if ($wallet && DB::table('cuanin_withdrawals')->where('wallet_id', $wallet->id)->where('status', 'pending')->exists()) {
            $blockers[] = 'Masih ada pengajuan pencairan yang belum selesai.';
        }
        if (DB::table(\App\Support\CashLedger::bonusesTable())->where('owner_id', $marketing->id)->whereColumn('paid', '<', 'amount')->exists()) {
            $blockers[] = 'Masih ada bonus marketing yang belum didanai.';
        }
        if (DB::table('order_referrals')->join('orders', 'orders.id', '=', 'order_referrals.order_id')->where('owner_id', $marketing->id)->where('orders.payment_status', '!=', 'approved')->exists()) {
            $blockers[] = 'Selesaikan order client referral yang belum disetujui sebelum menghapus marketing.';
        }
        return $blockers;
    }
}
