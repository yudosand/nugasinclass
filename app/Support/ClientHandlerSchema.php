<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClientHandlerSchema
{
    private static ?bool $ready = null;

    public static function ensure(): void
    {
        if (self::$ready === true) return;

        if (!Schema::hasColumn('users', 'handler_admin_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('handler_admin_id')->nullable()->index()->after('rejection_reason');
            });
        }

        self::$ready = true;
    }
}
