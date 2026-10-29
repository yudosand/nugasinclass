<?php
namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashSchema
{
    public static function ensureOrderChanges(): void
    {
        if (!Schema::hasTable('cash_order_changes')) Schema::create('cash_order_changes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('order_id')->index(); $t->string('order_number');
            $t->unsignedBigInteger('old_total'); $t->unsignedBigInteger('new_total'); $t->unsignedBigInteger('received');
            $t->unsignedBigInteger('old_package_id'); $t->unsignedBigInteger('new_package_id');
            $t->string('old_package'); $t->string('new_package');
            $t->text('reason'); $t->unsignedBigInteger('actor_id'); $t->string('actor_name');
            $t->string('request_key', 100)->unique(); $t->timestamp('created_at');
        });
    }

    public static function installed(): bool { return Schema::hasTable('cash_state'); }
    public static function active(): bool { return self::installed() && (bool) DB::table('cash_state')->where('id', 1)->value('active'); }

    // Explicit, authenticated setup only. DDL runs outside the reconciliation transaction.
    public static function ensure(): void
    {
        if (!Schema::hasTable('cash_accounts')) Schema::create('cash_accounts', function (Blueprint $t) {
            $t->unsignedBigInteger('order_id')->primary(); $t->string('order_number');
            $t->unsignedBigInteger('client_id')->nullable(); $t->string('client_name');
            $t->unsignedBigInteger('total'); $t->unsignedBigInteger('received')->default(0);
            $t->json('admin_ids'); $t->unsignedBigInteger('referral_owner_id')->nullable(); $t->string('referral_code')->nullable();
        });
        if (!Schema::hasTable('cash_receipts')) Schema::create('cash_receipts', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('order_id')->index(); $t->unsignedBigInteger('amount');
            $t->string('request_key', 100)->unique(); $t->string('reference', 160)->unique(); $t->string('proof')->nullable();
            $t->unsignedBigInteger('actor_id'); $t->timestamp('received_at'); $t->timestamp('created_at');
        });
        if (!Schema::hasTable('cash_allocations')) Schema::create('cash_allocations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('receipt_id')->index(); $t->unsignedBigInteger('user_id')->index();
            $t->string('name'); $t->string('kind', 40); $t->unsignedBigInteger('amount');
        });
        if (!Schema::hasTable('cash_entries')) Schema::create('cash_entries', function (Blueprint $t) {
            $t->id(); $t->string('entry_key', 120)->unique(); $t->bigInteger('amount'); $t->bigInteger('balance_after');
            $t->string('kind', 40); $t->string('party'); $t->string('description');
            $t->unsignedBigInteger('receipt_id')->nullable(); $t->unsignedBigInteger('withdrawal_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable()->index(); $t->unsignedBigInteger('actor_id');
            $t->timestamp('occurred_at'); $t->timestamp('created_at');
        });
        if (!Schema::hasTable('cash_conversions')) Schema::create('cash_conversions', function (Blueprint $t) {
            $t->unsignedBigInteger('client_id')->primary(); $t->unsignedBigInteger('owner_id')->index(); $t->timestamp('created_at');
        });
        if (!Schema::hasTable('cash_bonuses')) Schema::create('cash_bonuses', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('owner_id'); $t->unsignedInteger('threshold');
            $t->unsignedBigInteger('amount'); $t->unsignedBigInteger('paid')->default(0); $t->timestamp('created_at'); $t->unique(['owner_id', 'threshold']);
        });
        if (!Schema::hasTable('cash_reconciliations')) Schema::create('cash_reconciliations', function (Blueprint $t) {
            $t->id(); $t->string('request_key')->unique(); $t->json('snapshot'); $t->unsignedBigInteger('actor_id'); $t->timestamp('created_at');
        });
        if (!Schema::hasTable('cash_state')) Schema::create('cash_state', function (Blueprint $t) {
            $t->unsignedInteger('id')->primary(); $t->boolean('active')->default(false); $t->unsignedBigInteger('balance')->default(0);
            $t->unsignedBigInteger('rotation')->default(0); $t->text('note')->nullable(); $t->timestamp('activated_at')->nullable();
        });
        DB::table('cash_state')->insertOrIgnore(['id' => 1, 'active' => false, 'balance' => 0, 'rotation' => 0]);
    }
}
