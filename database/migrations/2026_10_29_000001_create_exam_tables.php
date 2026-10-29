<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { \App\Support\ExamSchema::ensure(); }
    public function down(): void { foreach(['exam_question_edits','exam_attempts','exam_sessions','exam_purchases','exam_catalog','exam_members'] as $table) Schema::dropIfExists($table); }
};
