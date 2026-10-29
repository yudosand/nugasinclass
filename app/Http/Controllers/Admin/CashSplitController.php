<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\{CashSplitCorrection, Cuanin, CashSchema};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashSplitController extends Controller
{
    public function preview(Request $request, int $id)
    {
        $data = $request->validate(['admin_ids' => 'required|array|min:1|max:100', 'admin_ids.*' => 'required|integer|distinct', 'split_reason' => 'required|string|min:5|max:2000']);
        $plan = DB::transaction(function () use ($id, $data) { Cuanin::lock(); return CashSplitCorrection::plan($id, $data['admin_ids']); });
        $request->session()->put('cash_split_draft', ['order_id' => $id, 'admin_ids' => $plan['admin_ids'], 'reason' => $data['split_reason'],
            'key' => (string) Str::uuid(), 'fingerprint' => CashSplitCorrection::fingerprint($plan), 'plan' => $plan]);
        return redirect()->route('admin.orders.split.review', $id);
    }

    public function review(Request $request, int $id)
    {
        $draft = $request->session()->get('cash_split_draft');
        if (!$draft || $draft['order_id'] !== $id) return redirect()->route('admin.orders.show', $id);
        return view('admin.cash.split-preview', ['draft' => $draft, 'plan' => $draft['plan']]);
    }

    public function commit(Request $request, int $id)
    {
        abort_unless(CashSchema::active(), 409);
        $data = $request->validate(['draft_key' => 'required|uuid', 'confirmed' => 'accepted', 'current_password' => 'required|current_password']);
        $draft = $request->session()->get('cash_split_draft');
        abort_unless($draft && $draft['order_id'] === $id && hash_equals($draft['key'], $data['draft_key']), 409, 'Buat pratinjau koreksi baru.');
        if (!empty($draft['plan']['negative_exception'])) $request->validate(['confirmed_laila_debt' => 'accepted']);
        CashSplitCorrection::ensure();
        CashSplitCorrection::prepareLailaDebtColumn($draft);
        CashSplitCorrection::commit($draft, $request->user());
        $request->session()->forget('cash_split_draft');
        return redirect()->route('admin.orders.show', $id)->with('success', 'Pembagian order dan saldo Cuanin sudah dikoreksi. Pembayaran berikutnya memakai penerima baru. Total kas tidak berubah.');
    }
}
