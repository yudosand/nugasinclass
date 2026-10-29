<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdminChatSchema
{
    public static function ensureCreated(): void
    {
        if (Schema::hasTable('admin_chat_messages')) {
            return;
        }

        try {
            Schema::create('admin_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('sender_name');
                $table->uuid('client_message_id')->unique();
                $table->text('body');
                $table->timestamps();
            });
        } catch (QueryException $exception) {
            // Another admin may activate the same room at the same time.
            if (!Schema::hasTable('admin_chat_messages')) {
                throw $exception;
            }
        }
    }
}
