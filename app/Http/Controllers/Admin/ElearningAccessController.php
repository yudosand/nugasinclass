<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElearningCredential;
use App\Models\User;
use App\Support\ElearningSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElearningAccessController extends Controller
{
    public function activate()
    {
        try { ElearningSchema::ensureCreated(); }
        catch (\Throwable $exception) {
            // Concurrent activation is safe once both tables exist.
            if (!ElearningSchema::ready()) return back()->with('error', 'Penyimpanan akses belum dapat diaktifkan. Periksa izin CREATE tabel database.');
        }
        return back()->with('success', 'Penyimpanan akses e-learning telah aktif.');
    }

    public function show($id)
    {
        $client = User::where('role', 'client')->findOrFail($id);
        abort_unless(ElearningSchema::ready(), 409, 'Aktifkan penyimpanan akses terlebih dahulu.');
        $credential = ElearningCredential::where('user_id', $client->id)->first();
        $hasAccess = $credential !== null;
        $revision = $credential?->revision();
        $changes = \Illuminate\Support\Facades\Schema::hasTable('elearning_change_logs')
            ? DB::table('elearning_change_logs')->leftJoin('users', 'users.id', '=', 'elearning_change_logs.admin_id')
                ->where('client_id', $client->id)->select('elearning_change_logs.created_at', 'users.name')->orderByDesc('elearning_change_logs.id')->limit(10)->get() : collect();
        return view('admin.elearning-access', compact('client', 'hasAccess', 'revision', 'changes'));
    }

    public function update(Request $request, $id)
    {
        abort_unless($request->expectsJson(), 406);
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'elearning_password' => ['required', 'string', 'max:1024', 'confirmed'],
            'elearning_password_confirmation' => ['required', 'string', 'max:1024'],
            'revision' => ['required', 'string', 'size:64'], 'confirmed' => ['accepted'],
        ]);
        abort_unless(ElearningSchema::ready(), 409);
        User::where('role', 'client')->findOrFail($id);
        abort_unless(ElearningCredential::where('user_id', $id)->exists(), 404);
        // DDL outside the password/audit transaction, for upgrades through the web.
        ElearningSchema::ensureChangeLog();
        $revision = DB::transaction(function () use ($data, $request, $id) {
            User::where('role', 'client')->whereKey($id)->lockForUpdate()->firstOrFail();
            $credential = ElearningCredential::where('user_id', $id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($credential->revision(), $data['revision']), 409, 'Akses sudah berubah. Muat ulang halaman sebelum mengoreksi password.');
            $credential->update(['password' => $data['elearning_password']]);
            DB::table('elearning_change_logs')->insert(['admin_id' => $request->user()->id, 'client_id' => $id, 'created_at' => now()]);
            return $credential->revision();
        });
        return response()->json(['message' => 'Password e-learning tersimpan sudah diperbarui. NIM/email tetap. Verifikasi ulang untuk membuka akses terbaru.', 'revision' => $revision])->header('Cache-Control', 'no-store, private');
    }

    public function reveal(Request $request, $id)
    {
        // Never redirect/flash a credential response into HTML or session storage.
        abort_unless($request->expectsJson(), 406);
        $request->validate(['current_password' => ['required', 'string', 'current_password']]);
        $client = User::where('role', 'client')->findOrFail($id);
        abort_unless(ElearningSchema::ready(), 409);
        $credential = ElearningCredential::where('user_id', $client->id)->firstOrFail();
        try {
            $payload = ['login' => $credential->login, 'password' => $credential->password];
        } catch (\Illuminate\Contracts\Encryption\DecryptException $exception) {
            return response()->json(['message' => 'Akses tidak dapat dibuka. Periksa kunci enkripsi server.'], 503)->header('Cache-Control', 'no-store');
        }
        // Audit must succeed before releasing credentials; log contains no secret values.
        DB::table('elearning_access_logs')->insert(['admin_id' => $request->user()->id, 'client_id' => $client->id, 'created_at' => now()]);
        return response()->json($payload)->header('Cache-Control', 'no-store, private');
    }
}
