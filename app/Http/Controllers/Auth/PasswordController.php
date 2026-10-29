<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function forgot()
    {
        return view('auth.forgot-password');
    }

    public function edit()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed', 'different:current_password'],
        ], ['current_password.current_password' => 'Password lama tidak sesuai.']);
        DB::transaction(function () use ($request, $data) {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            if (!Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Password telah berubah. Silakan masuk kembali.']);
            }
            $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
            Password::broker()->deleteToken($user);
        });
        $loginRoute = $request->user()->isMarketing() ? 'marketing.login' : ($request->user()->isStudent() ? 'study.login' : 'login');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route($loginRoute)->with('success', 'Password berhasil diubah. Silakan masuk dengan password baru.');
    }

    public function resetForm(Request $request, string $token)
    {
        $data = $request->validate(['account_id' => ['nullable', 'integer', 'min:1']]);
        $accountId = $data['account_id'] ?? null;
        $minimum = $accountId && User::whereKey($accountId)->whereIn('role', ['admin', 'marketing', 'student'])->exists() ? 8 : 12;
        return response()->view('auth.reset-password', ['token' => $token, 'phone' => $request->query('nomor_hp', ''), 'accountId' => $accountId, 'minimum' => $minimum])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'integer', 'min:1'],
            'nomor_hp' => ['required_without:account_id', 'nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);
        // Serialize issuance and consumption for the target; never trust a submitted role.
        $loginRoute = 'login';
        $status = DB::transaction(function () use ($data, &$loginRoute) {
            $account = !empty($data['account_id'])
                ? User::whereKey($data['account_id'])->whereIn('role', ['admin', 'client', 'marketing', 'student'])->lockForUpdate()->first()
                : User::where('role', 'client')->where('nomor_hp', $data['nomor_hp'])->lockForUpdate()->first();
            if (!$account || !Password::broker()->tokenExists($account, $data['token'])) return Password::INVALID_TOKEN;
            if ($account->role === 'client' && mb_strlen($data['password']) < 12) {
                throw ValidationException::withMessages(['password' => 'Password client minimal 12 karakter.']);
            }
            $loginRoute = $account->isMarketing() ? 'marketing.login' : ($account->isStudent() ? 'study.login' : 'login');
            return Password::broker()->reset(['id' => $account->id, 'token' => $data['token'], 'password' => $data['password']], function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            });
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['token' => 'Tautan reset tidak valid atau sudah kedaluwarsa. Hubungi admin untuk tautan baru.'])->withInput($request->only('nomor_hp'));
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route($loginRoute)->with('success', 'Password berhasil direset. Silakan masuk dengan password baru.');
    }
}
