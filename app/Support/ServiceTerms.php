<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServiceTerms
{
    public const VERSION = '2026-10-15';
    public const VIEW = 'legal.terms-2026-10-15';

    public static function contentHash(): string
    {
        return hash_file('sha256', resource_path('views/legal/terms-2026-10-15.blade.php'));
    }

    public static function ensureSchema(): void
    {
        if (Schema::hasTable('client_terms_acceptances')) return;
        try {
            Schema::create('client_terms_acceptances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('version', 32);
                $table->char('content_hash', 64);
                $table->timestamp('accepted_at');
                $table->unique(['user_id', 'version']);
            });
        } catch (QueryException $exception) {
            // Another registration may have created it concurrently.
            if (!Schema::hasTable('client_terms_acceptances')) throw $exception;
        }
    }

    public static function acceptanceFor(int $userId): ?object
    {
        return Schema::hasTable('client_terms_acceptances')
            ? DB::table('client_terms_acceptances')->where('user_id', $userId)->latest('accepted_at')->first()
            : null;
    }
}
