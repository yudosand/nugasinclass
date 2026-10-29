<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { \App\Support\ExamTestOrders::ensure(); }
    public function down(): void { Schema::dropIfExists('exam_test_orders'); }
};
