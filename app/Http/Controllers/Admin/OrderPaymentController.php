<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\Cuanin;
use App\Support\CuaninSchema;
use App\Support\CuaninSettlement;

class OrderPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user.profile', 'package']);
        $cashReady = \App\Support\CashSchema::active();
        $cash = $cashReady ? \App\Support\CashReport::data() : null;
        if ($cashReady) $query->with('cashAccount');
        elseif (CuaninSchema::ready()) $query->with('distribution');

        if ($request->filled('status')) {
            if ($cashReady && $request->status === 'partial') $query->whereHas('cashAccount', fn ($q) => $q->where('received', '>', 0)->whereColumn('received', '<', 'total'));
            else $query->where('payment_status', $request->status);
        }

        $orders = $query->latest()->paginate(15);

        return view('admin.orders.index', compact('orders', 'cashReady', 'cash'));
    }

    public function show($id)
    {
        $order = Order::with(['user.profile', 'package'])->findOrFail($id);

        $cuaninReady = CuaninSchema::ready();
        $distribution = $cuaninReady ? $order->distribution : null;
        $referral = $cuaninReady ? DB::table('order_referrals')->where('order_id', $order->id)->first() : null;
        $cashReady = \App\Support\CashSchema::active();
        $receipts = $cashReady ? DB::table('cash_receipts')->where('order_id', $order->id)->orderBy('id')->get() : collect();
        $allocations = $cashReady ? DB::table('cash_allocations')->whereIn('receipt_id', $receipts->pluck('id'))->get() : collect();
        $packages = $cashReady ? \App\Models\Package::where('is_active', true)->orWhere('id', $order->package_id)->orderBy('name')->get() : collect();
        $orderChanges = \Illuminate\Support\Facades\Schema::hasTable('cash_order_changes')
            ? DB::table('cash_order_changes')->where('order_id', $id)->orderByDesc('id')->get() : collect();
        $splitAdmins = $cashReady ? \App\Models\User::where('role', 'admin')->where('status', 'approved')->orderBy('name')->get(['id', 'name']) : collect();
        $splitChanges = \Illuminate\Support\Facades\Schema::hasTable('cash_split_changes') ? DB::table('cash_split_changes')->where('order_id', $id)->orderByDesc('id')->get() : collect();
        return view('admin.orders.show', compact('order', 'cuaninReady', 'distribution', 'referral', 'cashReady', 'receipts', 'allocations', 'packages', 'orderChanges', 'splitAdmins', 'splitChanges'));
    }

    public function approve(Request $request, $id)
    {
        if (\App\Support\CashSchema::active()) return app(CashController::class)->receipt($request, (int) $id);
        abort_if(\App\Support\CashSchema::installed(), 409, 'Selesaikan pencocokan kas awal terlebih dahulu.');
        $request->validate(['notes' => 'nullable|string|max:2000']);
        $order = DB::transaction(function () use ($request, $id) {
            Cuanin::lock();
            abort_if(\App\Support\CashSchema::installed(), 409, 'Pencatatan kas sudah disiapkan. Muat ulang order dan gunakan form pembayaran diterima.');
            $order = Order::lockForUpdate()->findOrFail($id);
            if ($order->payment_status === 'approved') return $order;
            if ($order->payment_status !== 'paid' || !$order->payment_proof) throw ValidationException::withMessages(['order' => 'Pembayaran harus sudah memiliki bukti dan menunggu verifikasi.']);
            CuaninSettlement::distribute($order);
            $order->update(['payment_status' => 'approved', 'notes' => $request->input('notes') ?: 'Pembayaran disetujui oleh Admin.']);
            $order->user->update(['status' => 'approved', 'rejection_reason' => null]);
            return $order;
        }, 3);

        return back()->with('success', \App\Support\Exams::isOrder($order->id) ? 'Pembayaran disetujui. Pembeli dapat membuka paket Simulasi UAS melalui Paket Saya.' : "Pembayaran untuk order {$order->order_number} berhasil di-APPROVE. Client sekarang dapat login & melengkapi data onboarding.");
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Alasan penolakan pembayaran wajib diisi.',
        ]);

        $order = DB::transaction(function () use ($request, $id) {
            if (CuaninSchema::ready()) Cuanin::lock();
            $order = Order::lockForUpdate()->findOrFail($id);
            if ($order->payment_status === 'approved') throw ValidationException::withMessages(['order' => 'Pembayaran yang sudah disetujui tidak dapat ditolak ulang.']);
            if (\App\Support\CashSchema::active() && $order->received_amount > 0) {
                if ($order->payment_status !== 'paid') throw ValidationException::withMessages(['order' => 'Tidak ada bukti pembayaran baru untuk ditolak. Pembayaran terverifikasi tidak dibatalkan.']);
                $order->update(['payment_status' => 'pending', 'notes' => $request->rejection_reason]);
                return $order;
            }
            $order->update(['payment_status' => 'rejected', 'notes' => $request->rejection_reason]);
            if (!\App\Support\Exams::isOrder($order->id)) $order->user->update(['status' => 'rejected', 'rejection_reason' => $request->rejection_reason]);
            return $order;
        }, 3);

        return back()->with('success', "Pembayaran untuk order {$order->order_number} telah DI-REJECT.");
    }
}
