<?php
namespace App\Support;

use App\Models\CuaninWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Cuanin
{
    public static function rupiah($decimal): int
    {
        if (!preg_match('/^\d{1,10}(?:\.0{1,2})?$/D', (string) $decimal)) throw ValidationException::withMessages(['amount' => 'Pembagian Cuanin memerlukan harga dalam Rupiah bulat.']);
        return (int) $decimal;
    }
    public static function format(int $amount): string { return 'Rp'.number_format($amount, 0, ',', '.'); }
    public static function date($value): string { return \Illuminate\Support\Carbon::parse($value)->timezone('Asia/Jakarta')->format('d M Y H:i').' WIB'; }
    public static function lock(): void
    {
        abort_unless(CuaninSchema::ready(), 409, 'Aktifkan Cuanin terlebih dahulu.');
        DB::table('cuanin_state')->where('id', 1)->lockForUpdate()->first();
    }
    public static function wallet(User $user): CuaninWallet
    {
        abort_unless(in_array($user->role, ['admin', 'marketing'], true), 403);
        $wallet = CuaninWallet::firstOrCreate(['user_id' => $user->id]);
        if ($user->role === 'admin') DB::table('referral_codes')->insertOrIgnore(['user_id' => $user->id, 'code' => 'ADM-'.$user->id.'-'.strtoupper(Str::random(6)), 'created_at' => now(), 'updated_at' => now()]);
        return $wallet;
    }
    public static function entry(CuaninWallet $wallet, int $amount, string $kind, string $key, string $description): void
    {
        if ($amount === 0 || DB::table('cuanin_entries')->where('entry_key', $key)->exists()) return;
        $wallet->refresh();
        // Incoming earnings may gradually repay an existing correction debt; ordinary debits still cannot overdraw.
        if ($amount < 0 && $wallet->balance + $amount < 0) throw ValidationException::withMessages(['balance' => 'Saldo tidak mencukupi.']);
        DB::table('cuanin_entries')->insert(['wallet_id' => $wallet->id, 'amount' => $amount, 'kind' => $kind, 'description' => $description, 'entry_key' => $key, 'created_at' => now()]);
        $wallet->balance += $amount; $wallet->save();
    }
}
