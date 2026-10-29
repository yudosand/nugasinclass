<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ElearningSchema
{
    public static function ensureChangeLog(): void
    {
        if (!Schema::hasTable('elearning_change_logs')) Schema::create('elearning_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
        });
    }

    public static function ready(): bool
    {
        return Schema::hasTable('client_elearning_credentials') && Schema::hasTable('elearning_access_logs');
    }

    public static function ensureCreated(): void
    {
        // Idempotent for the authenticated web activation and normal migrations.
        if (!Schema::hasTable('client_elearning_credentials')) {
            Schema::create('client_elearning_credentials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->text('login');
                $table->text('password');
                $table->timestamp('consented_at');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('elearning_access_logs')) {
            Schema::create('elearning_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at');
            });
        }
    }
}
