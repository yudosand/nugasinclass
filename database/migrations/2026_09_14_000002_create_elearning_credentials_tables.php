<?php

use App\Support\ElearningSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { ElearningSchema::ensureCreated(); }
    public function down(): void
    {
        Schema::dropIfExists('elearning_access_logs');
        Schema::dropIfExists('client_elearning_credentials');
    }
};
