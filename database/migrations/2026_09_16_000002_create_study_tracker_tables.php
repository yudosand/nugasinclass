<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('study_courses')) {
            Schema::create('study_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('course_name');
                $table->string('course_code', 50)->nullable();
                $table->unsignedTinyInteger('semester')->nullable();
                $table->unsignedTinyInteger('active_session')->default(1);
                $table->string('target_grade', 5)->nullable();
                $table->timestamps();
                $table->index(['user_id', 'active_session']);
            });
        }

        if (!Schema::hasTable('study_sessions')) {
            Schema::create('study_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('study_course_id')->constrained('study_courses')->cascadeOnDelete();
                $table->unsignedTinyInteger('session_number');
                $table->boolean('attendance_done')->default(false);
                $table->string('discussion_status', 30)->default('Belum Dikerjakan');
                $table->boolean('assignment_enabled')->default(false);
                $table->string('assignment_status', 30)->default('Belum Ada');
                $table->date('assignment_deadline')->nullable();
                $table->unsignedTinyInteger('discussion_score')->nullable();
                $table->unsignedTinyInteger('assignment_score')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['study_course_id', 'session_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
        Schema::dropIfExists('study_courses');
    }
};
