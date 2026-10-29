<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentUploadRequest;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Support\PaymentProofFiles;
use App\Support\Cuanin;
use App\Support\CuaninSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function proof($order_id)
    {
        $order = Order::findOrFail($order_id);
        abort_unless((int) Auth::id() === (int) $order->user_id || Auth::user()->isAdmin(), 403, 'Akses ditolak.');
        $file = PaymentProofFiles::resolve($order->payment_proof);
        if (!$file) return response()->view('payment.proof-missing', compact('order'), 404)->header('Cache-Control', 'no-store, private');
        return $this->proofResponse($file, $order->id);
    }

    public function receiptProof(int $id)
    {
        abort_unless(\App\Support\CashSchema::active(), 404);
        $receipt = DB::table('cash_receipts')->where('id', $id)->first();
        abort_unless($receipt, 404);
        $account = DB::table('cash_accounts')->where('order_id', $receipt->order_id)->first();
        abort_unless(Auth::user()->isAdmin() || ($account && (int) $account->client_id === (int) Auth::id()), 403);
        $file = PaymentProofFiles::resolve($receipt->proof);
        abort_unless($file, 404, 'Bukti pembayaran tidak tersedia.');
        return $this->proofResponse($file, $id);
    }

    private function proofResponse(string $file, int $id)
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        abort_unless(isset($extensions[$mime]), 415, 'Format bukti pembayaran tidak didukung.');
        $response = response()->file($file, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
        // PDFs are downloaded; uploaded active content must not execute in the app origin.
        return $response->setContentDisposition($mime === 'application/pdf' ? 'attachment' : 'inline', 'bukti-pembayaran-'.$id.'.'.$extensions[$mime]);
    }

    public function showPaymentPage($order_id)
    {
        $order = Order::with('package', 'user')->findOrFail($order_id);

        if (Auth::check() && Auth::id() !== $order->user_id && !Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        return view('payment.show', compact('order'));
    }

    public function uploadProof(PaymentUploadRequest $request, $order_id)
    {
        $order = Order::findOrFail($order_id);

        if (Auth::check() && Auth::id() !== $order->user_id && !Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        if ($request->hasFile('payment_proof')) {
            abort_if($order->payment_status === 'approved', 409, 'Pembayaran sudah disetujui.');
            $path = $request->file('payment_proof')->store('payment_proofs', 'local');
            if (!$path) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Bukti pembayaran belum berhasil disimpan. Silakan coba upload kembali.',
                ]);
            }

            try {
                DB::transaction(function () use ($order, $path) {
                    if (CuaninSchema::ready()) Cuanin::lock();
                    $order = Order::lockForUpdate()->findOrFail($order->id);
                    abort_if($order->payment_status === 'approved', 409, 'Pembayaran sudah disetujui.');
                    $order->update([
                'payment_proof' => 'private/'.$path,
                'payment_status' => 'paid',
                'payment_date' => now(),
            ]);

            if (!\App\Support\Exams::isOrder($order->id) && !(\App\Support\CashSchema::active() && $order->received_amount > 0)) $order->user->update([
                'status' => 'pending_approval',
            ]);
                }, 3);
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($path);
                throw $exception;
            }
        }

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Bukti pembayaran berhasil di-upload! Silakan konfirmasi melalui WhatsApp.');
            return response()->json(['redirect' => route('payment.show', $order->id)]);
        }
        return redirect()->route('payment.show', ['order' => $order->id])
            ->with('success', 'Bukti pembayaran berhasil di-upload! Silakan klik tombol konfirmasi WhatsApp untuk menghubungi admin.');
    }
}
