<?php
namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExamSchema
{
    public static function ready(): bool
    {
        if (request()->attributes->get('exam_schema_ready',false)) return true;
        $ready=Schema::hasTable('exam_attempts') && Schema::hasTable('exam_catalog') && Schema::hasTable('exam_purchases') && Schema::hasTable('exam_sessions') && Schema::hasTable('exam_question_edits') && Schema::hasTable('exam_members');
        if ($ready) request()->attributes->set('exam_schema_ready',true);
        return $ready;
    }
    public static function ensure(): void
    {
        if (!Schema::hasTable('exam_members')) Schema::create('exam_members', function(Blueprint $t) {
            $t->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete(); $t->timestamp('created_at');
        });
        if (!Schema::hasTable('exam_catalog')) Schema::create('exam_catalog', function(Blueprint $t) {
            $t->id(); $t->foreignId('package_id')->unique()->constrained('packages')->restrictOnDelete();
            $t->string('code',40)->unique(); $t->string('title'); $t->boolean('published')->default(false);
            $t->json('documents'); $t->unsignedInteger('duration_minutes')->default(60);
            $t->unsignedInteger('questions_per_attempt')->default(40); $t->timestamps();
        });
        if (!Schema::hasTable('exam_purchases')) Schema::create('exam_purchases', function(Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('catalog_id')->constrained('exam_catalog')->restrictOnDelete();
            $t->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $t->timestamp('created_at'); $t->unique(['user_id','catalog_id']);
        });
        if (!Schema::hasTable('exam_sessions')) Schema::create('exam_sessions', function(Blueprint $t) {
            $t->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $t->char('token_hash',64); $t->timestamp('expires_at');
        });
        if (!Schema::hasTable('exam_attempts')) Schema::create('exam_attempts', function(Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('catalog_id')->constrained('exam_catalog')->restrictOnDelete();
            $t->json('questions'); $t->json('answers'); $t->timestamp('started_at'); $t->timestamp('ends_at');
            $t->timestamp('finished_at')->nullable(); $t->unsignedInteger('correct')->nullable();
            $t->index(['user_id','catalog_id']);
        });
        if (!Schema::hasTable('exam_question_edits')) Schema::create('exam_question_edits', function(Blueprint $t) {
            $t->id(); $t->foreignId('catalog_id')->constrained('exam_catalog')->cascadeOnDelete();
            $t->string('asset',100); $t->json('payload'); $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->unique(['catalog_id','asset']);
        });
    }
}
