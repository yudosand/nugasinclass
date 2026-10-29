<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CuaninSchema
{
    public static function ready(): bool
    {
        return Schema::hasTable('cuanin_state') && (int) DB::table('cuanin_state')->where('id', 1)->value('version') === 1;
    }

    public static function ensureCreated(): void
    {
        if (self::ready()) return;
        // SQLite rebuilds users during ALTER; foreign key toggles cannot work inside an existing transaction.
        if (DB::getDriverName() === 'sqlite' && DB::transactionLevel() > 0 && DB::table('users')->exists()) {
            throw new \RuntimeException('Aktivasi skema SQLite harus dijalankan di luar transaksi.');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('client')->change();
            $table->string('nomor_hp')->nullable()->change();
        });
        if (!Schema::hasTable('cuanin_wallets')) Schema::create('cuanin_wallets', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('balance')->default(0); $table->unsignedBigInteger('reserved')->default(0);
            $table->text('bank_name')->nullable(); $table->text('account_name')->nullable(); $table->text('account_number')->nullable(); $table->timestamps();
        });
        if (!Schema::hasTable('referral_codes')) Schema::create('referral_codes', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('code', 32)->unique(); $table->timestamps();
        });
        if (!Schema::hasTable('order_referrals')) Schema::create('order_referrals', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete(); $table->unsignedBigInteger('client_id')->index();
            $table->string('code', 32); $table->timestamps();
        });
        if (!Schema::hasTable('referral_conversions')) Schema::create('referral_conversions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('owner_id')->index(); $table->unsignedBigInteger('client_id')->unique(); $table->timestamp('created_at');
        });
        if (!Schema::hasTable('cuanin_bonus_awards')) Schema::create('cuanin_bonus_awards', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('owner_id')->index(); $table->unsignedInteger('threshold');
            $table->unsignedBigInteger('amount'); $table->unsignedBigInteger('paid')->default(0); $table->timestamp('created_at'); $table->unique(['owner_id', 'threshold']);
        });
        if (!Schema::hasTable('cuanin_settlements')) Schema::create('cuanin_settlements', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('order_id')->unique(); $table->string('order_number');
            $table->unsignedBigInteger('gross'); $table->unsignedBigInteger('commission'); $table->unsignedBigInteger('bonus');
            $table->unsignedBigInteger('admin_pool'); $table->unsignedInteger('admin_count');
            $table->unsignedBigInteger('referral_owner_id')->nullable(); $table->string('referral_code', 32)->nullable();
            $table->json('allocations'); $table->timestamp('created_at');
        });
        if (!Schema::hasTable('cuanin_entries')) Schema::create('cuanin_entries', function (Blueprint $table) {
            $table->id(); $table->foreignId('wallet_id')->constrained('cuanin_wallets')->restrictOnDelete();
            $table->bigInteger('amount'); $table->string('kind', 40); $table->string('description');
            $table->string('entry_key', 150)->unique(); $table->timestamp('created_at');
        });
        if (!Schema::hasTable('cuanin_withdrawals')) Schema::create('cuanin_withdrawals', function (Blueprint $table) {
            $table->id(); $table->foreignId('wallet_id')->constrained('cuanin_wallets')->restrictOnDelete(); $table->uuid('request_key')->unique();
            $table->unsignedBigInteger('amount'); $table->string('status', 20)->default('pending'); $table->text('bank_snapshot');
            $table->unsignedBigInteger('processed_by')->nullable(); $table->timestamp('processed_at')->nullable(); $table->string('payment_reference')->nullable(); $table->timestamps();
        });
        if (!Schema::hasTable('cuanin_state')) Schema::create('cuanin_state', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary(); $table->unsignedInteger('version'); $table->timestamp('activated_at');
        });
        DB::table('cuanin_state')->insertOrIgnore(['id' => 1, 'version' => 1, 'activated_at' => now()]);
    }
}
