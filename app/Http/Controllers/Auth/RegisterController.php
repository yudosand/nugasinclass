<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\CuaninSchema;
use App\Support\Cuanin;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        $packages = Package::where('slug','not like','uas-%')->where('is_active', true)->get();
        $selectedPackageId = $request->query('package_id');

        return view('auth.register', compact('packages', 'selectedPackageId'));
    }

    public function register(RegisterRequest $request)
    {
        // DDL must run before the registration transaction on MySQL.
        \App\Support\ServiceTerms::ensureSchema();
        [$user, $order] = DB::transaction(function () use ($request) {
        if (CuaninSchema::ready()) Cuanin::lock();
        $package = Package::where('slug','not like','uas-%')->where('is_active', true)->findOrFail($request->package_id);
        $referral = null;
        if ($request->filled('referral_code')) {
            if (!CuaninSchema::ready()) throw ValidationException::withMessages(['referral_code' => 'Referral belum diaktifkan.']);
            $referral = DB::table('referral_codes')->join('users', 'users.id', '=', 'referral_codes.user_id')->where('code', strtoupper(trim($request->referral_code)))->whereIn('users.role', ['admin', 'marketing'])->where('users.status', 'approved')->select('referral_codes.*')->first();
            if (!$referral) throw ValidationException::withMessages(['referral_code' => 'Kode referral tidak ditemukan atau tidak aktif.']);
        }

        $user = User::create([
            'name' => $request->name,
            'nomor_hp' => $request->nomor_hp,
            'password' => Hash::make($request->password),
            'role' => 'client',
            'status' => 'pending_payment',
        ]);

        DB::table('client_terms_acceptances')->insert([
            'user_id' => $user->id,
            'version' => \App\Support\ServiceTerms::VERSION,
            'content_hash' => \App\Support\ServiceTerms::contentHash(),
            'accepted_at' => now(),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
            'user_id' => $user->id,
            'package_id' => $package->id,
            'amount' => $package->price,
            'payment_status' => 'pending',
        ]);

        ClientProfile::create([
            'user_id' => $user->id,
            'is_onboarding_completed' => false,
        ]);
        if ($referral) DB::table('order_referrals')->insert(['order_id' => $order->id, 'owner_id' => $referral->user_id, 'client_id' => $user->id, 'code' => $referral->code, 'created_at' => now(), 'updated_at' => now()]);
        return [$user, $order];
        }, 3);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('payment.show', ['order' => $order->id])
            ->with('success', 'Pendaftaran berhasil! Silakan bayar melalui QRIS dan upload bukti pembayaran.');
    }
}
