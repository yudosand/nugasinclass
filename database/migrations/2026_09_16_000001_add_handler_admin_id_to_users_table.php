<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'handler_admin_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('handler_admin_id')->nullable()->index()->after('rejection_reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'handler_admin_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['handler_admin_id']);
                $table->dropColumn('handler_admin_id');
            });
        }
    }
};
