<?php
namespace App\Support;

use App\Models\{Order, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

class ExamTestOrders
{
    public static function ensure(): void
    {
        if (!Schema::hasTable('exam_test_orders')) Schema::create('exam_test_orders', function (Blueprint $t) {
            $t->foreignId('order_id')->primary()->constrained('orders')->cascadeOnDelete();
            $t->unsignedBigInteger('marked_by');
            $t->string('actor_name');
            $t->text('reason');
            $t->timestamp('created_at');
            $t->unsignedBigInteger('simulated_amount')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->unsignedBigInteger('revoked_by')->nullable();
            $t->timestamp('revoked_at')->nullable();
        });
    }

    public static function get(int $id): ?object
    {
        return Schema::hasTable('exam_test_orders') ? DB::table('exam_test_orders')->where('order_id', $id)->first() : null;
    }

    public static function exclude($query)
    {
        return Schema::hasTable('exam_test_orders') ? $query->whereNotIn('orders.id', DB::table('exam_test_orders')->select('order_id')) : $query;
    }

    public static function assertReal(Order $order): void
    {
        if (self::get($order->id)) throw ValidationException::withMessages(['order'=>'Order uji tidak dapat mencatat pembayaran nyata atau membagikan Cuanin. Gunakan persetujuan transaksi uji.']);
    }

    public static function change(int $id, string $action, array $data, User $actor): void
    {
        abort_unless($actor->isAdmin() && $actor->status === 'approved', 403);
        // DDL must run before the transaction: MySQL implicitly commits schema changes.
        self::ensure();
        DB::transaction(function () use ($id, $action, $data, $actor) {
            if (CuaninSchema::ready()) Cuanin::lock();
            $order = Order::lockForUpdate()->findOrFail($id);
            abort_unless(Exams::isOrder($id), 422, 'Transaksi uji hanya untuk Simulasi UAS.');
            $test = self::get($id);
            if ($action === 'mark') {
                if ($test) return;
                $hasMoney = $order->payment_status === 'approved';
                foreach (['cash_receipts','cuanin_settlements'] as $table) {
                    if (Schema::hasTable($table) && DB::table($table)->where('order_id',$id)->exists()) $hasMoney = true;
                }
                if (Schema::hasTable('cash_accounts') && DB::table('cash_accounts')->where('order_id',$id)->where('received','>',0)->exists()) $hasMoney = true;
                if ($hasMoney) throw ValidationException::withMessages(['order'=>'Order dengan pembayaran terverifikasi atau pembagian Cuanin tidak boleh diubah menjadi transaksi uji.']);
                DB::table('exam_test_orders')->insert(['order_id'=>$id,'marked_by'=>$actor->id,'actor_name'=>$actor->name,'reason'=>$data['reason'],'created_at'=>now()]);
                return;
            }
            abort_unless($test, 409, 'Tandai order sebagai transaksi uji terlebih dahulu.');
            if ($action === 'approve') {
                abort_if($test->revoked_at, 409, 'Akses uji ini sudah dicabut.');
                if ($test->approved_at) return;
                if ($order->payment_status !== 'paid' || !PaymentProofFiles::resolve($order->payment_proof)) {
                    throw ValidationException::withMessages(['order'=>'Upload bukti contoh melalui akun peserta terlebih dahulu.']);
                }
                if ((int)$data['simulated_amount'] > Cuanin::rupiah($order->amount)) throw ValidationException::withMessages(['simulated_amount'=>'Nominal simulasi tidak boleh melebihi harga paket.']);
                DB::table('exam_test_orders')->where('order_id',$id)->update(['simulated_amount'=>$data['simulated_amount'],'approved_by'=>$actor->id,'approved_at'=>now()]);
                $order->update(['payment_status'=>'approved']);
                return;
            }
            abort_unless($action === 'revoke', 422);
            if (!$test->revoked_at) DB::table('exam_test_orders')->where('order_id',$id)->update(['revoked_by'=>$actor->id,'revoked_at'=>now()]);
        }, 3);
    }
}
