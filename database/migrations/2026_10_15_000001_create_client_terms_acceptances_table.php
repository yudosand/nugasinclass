<?php

use App\Support\ServiceTerms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { ServiceTerms::ensureSchema(); }
    public function down(): void { Schema::dropIfExists('client_terms_acceptances'); }
};
