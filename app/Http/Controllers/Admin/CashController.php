<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Order, User};
use App\Support\{CashSchema, CashLedger, Cuanin, CuaninSchema};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashController extends Controller
{
    public function setup()
    {
        if (CashSchema::active()) return redirect()->route('admin.orders.index');
        $ready = CashSchema::installed();
        $sources = $ready ? CashLedger::sources() : [];
        $members = $ready ? User::whereIn('role', ['admin', 'marketing', 'marketing_deleted'])->orderBy('name')->get() : collect();
        $knownPaid = $ready ? CashLedger::paidTotals() : [];
        return view('admin.cash.setup', compact('ready', 'sources', 'members', 'knownPaid'));
    }
    public function prepare(Request $request)
    {
        $request->validate(['current_password' => 'required|current_password']);
        abort_unless(CuaninSchema::ready(), 409, 'Aktifkan Cuanin terlebih dahulu.');
        CashSchema::ensure();
        return redirect()->route('admin.cash.setup');
    }
    public function preview(Request $request)
    {
        abort_unless(CashSchema::installed() && !CashSchema::active(), 409);
        if (!$request->input('received') && !CashLedger::sources()) $request->merge(['received' => []]);
        $data = $request->validate(['received' => 'present|array', 'received.*' => 'required|integer|min:0|max:9999999999',
            'withdrawn' => 'present|array', 'withdrawn.*' => 'required|integer|min:0|max:9999999999', 'note' => 'nullable|string|max:2000']);
        // Capture plan and fingerprint together under the same accounting lock.
        [$plan, $fingerprint] = DB::transaction(function () use ($data) {
            Cuanin::lock(); return [CashLedger::plan($data['received'], $data['withdrawn']), CashLedger::fingerprint()];
        });
        $draft = [...$data, 'note' => $data['note'] ?? '', 'fingerprint' => $fingerprint, 'key' => (string) Str::uuid(), 'plan' => $plan];
        $request->session()->put('cash_reconciliation', $draft);
        return redirect()->route('admin.cash.review');
    }
    public function review(Request $request)
    {
        if (CashSchema::active()) return redirect()->route('admin.orders.index');
        $draft = $request->session()->get('cash_reconciliation');
        if (!$draft) return redirect()->route('admin.cash.setup');
        return view('admin.cash.preview', ['draft' => $draft, 'plan' => $draft['plan']]);
    }
    public function commit(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|current_password', 'confirmed' => 'accepted', 'draft_key' => 'required|uuid']);
        $draft = $request->session()->get('cash_reconciliation');
        abort_unless(is_array($draft) && hash_equals($draft['key'], $data['draft_key']), 409, 'Pratinjau tidak berlaku. Buat pratinjau lagi.');
        CashLedger::reconcile($draft, $request->user()->id);
        $request->session()->forget('cash_reconciliation');
        return redirect()->route('admin.orders.index')->with('success', 'Kas awal dan saldo Cuanin sudah dicocokkan. Pembayaran berikutnya dicatat sesuai nominal nyata.');
    }
    public function receipt(Request $request, int $id)
    {
        if (is_string($request->amount) && preg_match('/^[1-9][0-9]{0,2}(?:\.[0-9]{3})+$/D', $request->amount)) $request->merge(['amount' => str_replace('.', '', $request->amount)]);
        $data = $request->validate(['amount' => 'required|integer|min:1|max:9999999999', 'reference' => 'required|string|max:160',
            'received_at' => 'required|date_format:Y-m-d\TH:i', 'request_key' => 'required|uuid', 'verified' => 'accepted',
            'current_password' => 'required|current_password', 'notes' => 'nullable|string|max:2000', 'activate_client' => 'nullable|boolean']);
        $data['reference'] = strtoupper(trim($data['reference']));
        $data['received_at'] = \Illuminate\Support\Carbon::createFromFormat('Y-m-d\TH:i', $data['received_at'], 'Asia/Jakarta')->seconds(0)->utc();
        if ($data['received_at']->isFuture()) throw \Illuminate\Validation\ValidationException::withMessages(['received_at' => 'Tanggal pembayaran tidak boleh di masa depan.']);
        CashLedger::record(Order::findOrFail($id), $data, $request->user()->id);
        return back()->with('success', 'Pembayaran nyata dicatat. Kas, sisa tagihan, dan Cuanin sudah diperbarui.');
    }
    public function correctOrder(Request $request, int $id)
    {
        abort_unless(CashSchema::active(), 409, 'Cocokkan kas awal terlebih dahulu.');
        if (is_string($request->new_total) && preg_match('/^[1-9][0-9]{0,2}(?:\.[0-9]{3})+$/D', $request->new_total)) $request->merge(['new_total' => str_replace('.', '', $request->new_total)]);
        $data = $request->validate(['new_total' => 'required|integer|min:1|max:9999999999',
            'package_id' => 'required|integer|exists:packages,id', 'expected_total' => 'required|integer|min:1',
            'expected_package_id' => 'required|integer', 'reason' => 'required|string|min:5|max:2000',
            'request_key' => 'required|uuid', 'confirmed' => 'accepted', 'current_password' => 'required|current_password']);
        Order::findOrFail($id);
        // Schema preparation is outside the accounting transaction (MySQL DDL commits implicitly).
        CashSchema::ensureOrderChanges();
        CashLedger::correctOrder($id, $data, $request->user());
        return redirect()->route('admin.orders.show', $id)->with('success', 'Paket dan harga order dikoreksi. Uang masuk dan saldo Cuanin tidak berubah. Jika pembayaran belum tercatat, gunakan form Catat Pembayaran Diterima.');
    }

    public function note(Request $request)
    {
        abort_unless(CashSchema::active(), 409);
        $data = $request->validate(['note' => 'nullable|string|max:2000']);
        DB::transaction(function () use ($data) { Cuanin::lock(); DB::table('cash_state')->where('id', 1)->update(['note' => $data['note']]); });
        return back()->with('success', 'Catatan bendahara disimpan.');
    }
    public function opening()
    {
        abort_unless(CashSchema::active(), 404);
        $record = DB::table('cash_reconciliations')->first();
        abort_unless($record, 404);
        $snapshot = json_decode($record->snapshot, true, flags: JSON_THROW_ON_ERROR);
        return view('admin.cash.preview', ['plan' => $snapshot['plan'], 'draft' => [], 'recorded' => $record->created_at]);
    }
}
