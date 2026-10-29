<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ExamTestOrders;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamTestOrderController extends Controller
{
    public function update(Request $request, int $id)
    {
        $data=$request->validate(['action'=>['required',Rule::in(['mark','approve','revoke'])],
            'current_password'=>'required|current_password', 'confirmed'=>'accepted',
            'reason'=>'required_if:action,mark|nullable|string|min:5|max:1000',
            'simulated_amount'=>'required_if:action,approve|nullable|integer|min:1|max:9999999999']);
        ExamTestOrders::change($id,$data['action'],$data,$request->user());
        return redirect()->route('admin.orders.show',$id)->with('success',match($data['action']) {
            'mark'=>'Order ditandai sebagai transaksi uji. Peserta dapat upload bukti contoh dan konfirmasi WhatsApp.',
            'approve'=>'Akses uji disetujui. Tidak ada uang masuk, piutang, atau saldo Cuanin yang ditambahkan.',
            'revoke'=>'Akses uji dicabut. Riwayat transaksi uji tetap tersimpan.',
        });
    }
}
