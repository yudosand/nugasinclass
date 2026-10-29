<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
class CashReport
{
    public static function data(): array
    {
        $state = DB::table('cash_state')->where('id', 1)->first();
        $incoming = (int) DB::table('cash_entries')->where('amount', '>', 0)->sum('amount');
        $outgoing = -(int) DB::table('cash_entries')->where('amount', '<', 0)->sum('amount');
        $outstanding = (int) ExamTestOrders::exclude(DB::table('orders'))->leftJoin('cash_accounts', 'cash_accounts.order_id', '=', 'orders.id')->selectRaw('COALESCE(SUM(orders.amount - COALESCE(cash_accounts.received, 0)), 0) as total')->value('total');
        $withdrawals = DB::table('cash_entries')->leftJoin('users', 'users.id', '=', 'cash_entries.user_id')->where('cash_entries.amount', '<', 0)
            ->groupBy('cash_entries.user_id')->selectRaw('cash_entries.user_id, COALESCE(MAX(users.name), MAX(cash_entries.party)) as party, -SUM(cash_entries.amount) as amount')->orderBy('party')->get();
        $history = DB::table('cash_entries')->leftJoin('cash_receipts', 'cash_receipts.id', '=', 'cash_entries.receipt_id')->leftJoin('orders', 'orders.id', '=', 'cash_receipts.order_id')
            ->select('cash_entries.*', 'orders.id as order_link')->orderByDesc('cash_entries.id')->paginate(20, ['*'], 'cash_page')->withQueryString();
        $debts = ExamTestOrders::exclude(DB::table('orders'))->join('users', 'users.id', '=', 'orders.user_id')->leftJoin('cash_accounts', 'cash_accounts.order_id', '=', 'orders.id')
            ->whereRaw('orders.amount > COALESCE(cash_accounts.received, 0)')
            ->selectRaw('orders.id, orders.order_number, users.name, orders.amount, COALESCE(cash_accounts.received, 0) as received, orders.amount - COALESCE(cash_accounts.received, 0) as outstanding')
            ->orderBy('orders.id')->paginate(10, ['*'], 'debt_page')->withQueryString();
        return compact('state', 'incoming', 'outgoing', 'outstanding', 'withdrawals', 'history', 'debts');
    }
}
