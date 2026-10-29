<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class AccountRecoveryController extends Controller
{
    public function show(int $id)
    {
        $account = User::whereIn('role', ['admin', 'client', 'marketing', 'student'])->findOrFail($id);
        return response()->view('admin.account-recovery', compact('account'))
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function issue(Request $request, int $id)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'identity_verified' => ['accepted'],
        ], ['identity_verified.accepted' => 'Verifikasi identitas pemilik akun terlebih dahulu.',
            'current_password.current_password' => 'Password admin Anda tidak sesuai.']);

        $link = DB::transaction(function () use ($id) {
            $account = User::whereIn('role', ['admin', 'client', 'marketing', 'student'])->lockForUpdate()->findOrFail($id);
            $token = Password::broker()->createToken($account);
            return rtrim(config('app.url'), '/').route('password.reset', ['token' => $token, 'account_id' => $account->id], false);
        });

        return redirect()->route('admin.accounts.reset', $id)->with('account_reset_link', $link)
            ->with('success', 'Tautan reset dibuat. Kirim secara pribadi kepada pemilik akun yang sudah diverifikasi.');
    }
}
