<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = [
            'nomor_hp' => $request->nomor_hp,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->isStudent()) {
                if (\App\Support\ExamSchema::ready() && \Illuminate\Support\Facades\DB::table('exam_purchases')->where('user_id',$user->id)->exists()) return redirect()->route('exams.mine');
                return redirect()->route('study.dashboard');
            }

            // Client logic
            if ($user->status === 'pending_payment') {
                $latestOrder = $user->orders()->latest()->first();
                if ($latestOrder) {
                    return redirect()->route('payment.show', ['order' => $latestOrder->id])
                        ->with('info', 'Silakan upload bukti pembayaran terlebih dahulu.');
                }
            }

            if ($user->status === 'pending_approval') {
                Auth::logout();
                return redirect()->route('login')->with('warning', 'Pembayaran Anda sedang dalam proses verifikasi oleh Admin. Silakan tunggu konfirmasi Admin.');
            }

            if ($user->status === 'rejected') {
                $reason = $user->rejection_reason ? ": {$user->rejection_reason}" : ".";
                Auth::logout();
                return redirect()->route('login')->with('error', "Pembayaran/Akun Anda telah ditolak oleh Admin{$reason}");
            }

            if ($user->status === 'approved') {
                if (!$user->hasCompletedOnboarding()) {
                    return redirect()->route('onboarding.show');
                }
                return redirect()->route('client.dashboard');
            }
        }

        return back()->withErrors([
            'nomor_hp' => 'Nomor HP atau Password yang Anda masukkan salah.',
        ])->onlyInput('nomor_hp');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing.index')->with('success', 'Anda telah berhasil logout.');
    }
}
