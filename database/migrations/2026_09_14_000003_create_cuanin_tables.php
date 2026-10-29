<?php
use App\Support\CuaninSchema;
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public $withinTransaction = false;
    public function up(): void { CuaninSchema::ensureCreated(); }
    public function down(): void { throw new RuntimeException('Rollback Cuanin memerlukan rencana pemulihan data keuangan; jangan menghapus ledger.'); }
};
