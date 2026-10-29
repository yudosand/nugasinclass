<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Cuanin;
use App\Support\CuaninSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketingController extends Controller
{
    public function index() { return view('marketing.index'); }
    public function registerForm() { return view('marketing.register', ['ready' => CuaninSchema::ready()]); }
    public function loginForm() { return view('marketing.login'); }
    public function register(Request $request)
    {
        abort_unless(CuaninSchema::ready(), 409, 'Pendaftaran marketing belum diaktifkan.');
        foreach (['referral_code', 'email'] as $field) {
            if (is_string($request->input($field))) $request->merge([$field => $field === 'email' ? strtolower(trim($request->input($field))) : strtoupper(trim($request->input($field)))]);
        }
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email', 'referral_code' => ['required', 'regex:/^[A-Z0-9][A-Z0-9_-]{3,23}$/D', 'unique:referral_codes,code'], 'password' => 'required|string|min:8|max:72']);
        $user = DB::transaction(function () use ($data) {
            Cuanin::lock();
            if (User::where('email', $data['email'])->exists() || DB::table('referral_codes')->where('code', $data['referral_code'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['referral_code' => 'Email atau kode referral sudah digunakan.']);
            }
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'nomor_hp' => null, 'password' => $data['password'], 'role' => 'marketing', 'status' => 'approved']);
            DB::table('referral_codes')->insert(['user_id' => $user->id, 'code' => $data['referral_code'], 'created_at' => now(), 'updated_at' => now()]);
            Cuanin::wallet($user);
            return $user;
        });
        Auth::login($user); $request->session()->regenerate();
        return redirect()->route('cuanin.index')->with('success', 'Selamat bergabung sebagai Nugasin Partner! Bagikan kode referral Anda.');
    }
    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:1024']);
        if (Auth::attempt(['email' => strtolower(trim($data['email'])), 'password' => $data['password'], 'role' => 'marketing', 'status' => 'approved'])) {
            $request->session()->regenerate(); return redirect()->route('cuanin.index');
        }
        return back()->withErrors(['email' => 'Email atau password tidak sesuai.'])->onlyInput('email');
    }
    public function clients(Request $request)
    {
        abort_unless($request->user()->role === 'marketing' && CuaninSchema::ready(), 403);
        $clients = DB::table('order_referrals')->join('users', 'users.id', '=', 'order_referrals.client_id')->join('orders', 'orders.id', '=', 'order_referrals.order_id')->join('packages', 'packages.id', '=', 'orders.package_id')
            ->where('order_referrals.owner_id', $request->user()->id)->where('users.role', 'client')
            ->select('users.name', 'orders.order_number', 'orders.payment_status', 'orders.amount', 'packages.name as package_name', 'order_referrals.created_at');
        if (\App\Support\CashSchema::active()) $clients->leftJoin('cash_accounts', 'cash_accounts.order_id', '=', 'orders.id')->selectRaw('COALESCE(cash_accounts.received, 0) as received');
        $clients = $clients->orderByDesc('order_referrals.id')->paginate(20);
        return view('marketing.clients', compact('clients'));
    }
}
