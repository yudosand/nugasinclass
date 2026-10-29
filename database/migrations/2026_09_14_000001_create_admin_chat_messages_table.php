<?php

use App\Support\AdminChatSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        AdminChatSchema::ensureCreated();
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_chat_messages');
    }
};
