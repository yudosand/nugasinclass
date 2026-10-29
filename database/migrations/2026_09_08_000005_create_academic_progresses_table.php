<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_course_id')->constrained('client_courses')->onDelete('cascade');
            $table->tinyInteger('session_number'); // 1 to 8
            $table->enum('discussion_status', ['Belum Dikerjakan', 'On Progress', 'Menunggu Nilai', 'Selesai'])->default('Belum Dikerjakan');
            $table->integer('discussion_score')->nullable();
            $table->enum('assignment_status', ['Belum Dikerjakan', 'On Progress', 'Menunggu Nilai', 'Selesai'])->default('Belum Dikerjakan');
            $table->integer('assignment_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['client_course_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_progresses');
    }
};
