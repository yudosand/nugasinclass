<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('universitas')->nullable();
            $table->string('nim')->nullable();
            $table->string('program_studi')->nullable();
            $table->integer('semester')->nullable();
            $table->text('detail_akademik')->nullable();
            $table->timestamp('agreed_terms_at')->nullable();
            $table->boolean('is_onboarding_completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
