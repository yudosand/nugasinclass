<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClientManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'client')->with(['profile', 'orders.package']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nomor_hp', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($pq) use ($search) {
                      $pq->where('universitas', 'like', "%{$search}%")
                        ->orWhere('nim', 'like', "%{$search}%")
                        ->orWhere('program_studi', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $clients = $query->latest()->paginate(15);

        return view('admin.clients.index', compact('clients'));
    }

    public function show($id)
    {
        \App\Support\ClientHandlerSchema::ensure();
        $client = User::where('role', 'client')
            ->with(['profile', 'orders.package', 'courses.progresses', 'handlerAdmin:id,name'])
            ->findOrFail($id);

        $accessReady = \App\Support\ElearningSchema::ready();
        $hasAccess = $accessReady && \App\Models\ElearningCredential::where('user_id', $client->id)->exists();
        $termsAcceptance = \App\Support\ServiceTerms::acceptanceFor($client->id);
        return response()->view('admin.clients.show', compact('client', 'accessReady', 'hasAccess', 'termsAcceptance'))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate(['current_password' => ['required', 'current_password'], 'identity_verified' => ['accepted']], [
            'identity_verified.accepted' => 'Verifikasi identitas client terlebih dahulu.',
            'current_password.current_password' => 'Password admin Anda tidak sesuai.',
        ]);
        $result = DB::transaction(function () use ($id) {
            $client = User::where('role', 'client')->lockForUpdate()->findOrFail($id);
            return ['phone' => $client->nomor_hp, 'token' => Password::broker()->createToken($client)];
        });
        // The configured canonical URL avoids deriving sensitive reset links from an arbitrary Host header.
        $url = rtrim(config('app.url'), '/').route('password.reset', ['token' => $result['token'], 'nomor_hp' => $result['phone']], false);
        return redirect()->route('admin.clients.show', $id)->with('reset_link', $url)->with('success', 'Tautan reset dibuat. Kirim secara pribadi kepada pemilik akun yang sudah diverifikasi.');
    }

    public function confirmDelete($id)
    {
        $client = User::where('role', 'client')->withCount(['orders', 'courses'])->findOrFail($id);
        return view('admin.clients.delete', compact('client'));
    }

    public function destroy(Request $request, $id)
    {
        $client = User::where('role', 'client')->findOrFail($id);
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirm_phone' => ['required', Rule::in([$client->nomor_hp])],
        ], ['confirm_phone.in' => 'Ketik nomor HP client yang akan dihapus dengan tepat.', 'current_password.current_password' => 'Password admin Anda tidak sesuai.']);
        $paths = DB::transaction(function () use ($client) {
            if (\App\Support\CuaninSchema::ready()) \App\Support\Cuanin::lock();
            $client = User::where('role', 'client')->lockForUpdate()->findOrFail($client->id);
            if (\App\Support\CashSchema::installed() && !\App\Support\CashSchema::active()) throw \Illuminate\Validation\ValidationException::withMessages(['client' => 'Selesaikan pencocokan kas sebelum menghapus client.']);
            if (\App\Support\CashSchema::active()) {
                foreach ($client->orders()->with('cashAccount')->lockForUpdate()->get() as $order) {
                    // A rejected, never-paid order is a cancelled sale, not a collectible debt.
                    $discardable = $order->payment_status === 'rejected' && $order->received_amount === 0
                        && !DB::table('cash_receipts')->where('order_id', $order->id)->exists()
                        && !(\App\Support\CuaninSchema::ready() && DB::table('cuanin_settlements')->where('order_id', $order->id)->exists());
                    if ($order->outstanding_amount > 0 && !$discardable) throw \Illuminate\Validation\ValidationException::withMessages(['client' => 'Client masih memiliki sisa tagihan atau riwayat pembayaran yang perlu diselesaikan sebelum menghapus akun.']);
                    if ($discardable) DB::table('cash_accounts')->where('order_id', $order->id)->delete();
                }
            }
            $paths = $client->orders()->whereNotNull('payment_proof')->pluck('payment_proof')->unique()->all();
            Password::broker()->deleteToken($client);
            DB::table('sessions')->where('user_id', $client->id)->delete();
            $client->delete(); // Existing foreign keys cascade profile, orders, courses and progress.
            return $paths;
        });
        $failed = false;
        foreach ($paths as $path) {
            // Remove only this client's managed proof files; preserve any shared reference.
            if (!\App\Support\PaymentProofFiles::location($path) || \App\Models\Order::where('payment_proof', $path)->exists()) continue;
            if (\App\Support\CashSchema::installed() && DB::table('cash_receipts')->where('proof', $path)->exists()) continue;
            try {
                if (!\App\Support\PaymentProofFiles::delete($path)) $failed = true;
            } catch (\Throwable $exception) {
                $failed = true;
            }
        }
        $response = redirect()->route('admin.clients.index')->with('success', 'Client beserta profil, pesanan, mata kuliah, dan progresnya berhasil dihapus.');
        if ($failed) $response->with('error', 'Sebagian file bukti pembayaran belum dapat dihapus. Periksa permission folder payment_proofs di storage/app/public dan storage/app/private melalui File Manager.');
        return $response;
    }
}
