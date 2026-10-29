<?php
namespace App\Support;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CuaninSettlement
{
    // Called inside the same transaction as approval, after acquiring the global Cuanin lock.
    public static function distribute(Order $order): void
    {
        if (DB::table('cuanin_settlements')->where('order_id', $order->id)->exists()) return;
        $gross = Cuanin::rupiah($order->amount);
        $admins = User::where('role', 'admin')->where('status', 'approved')->orderBy('id')->lockForUpdate()->get();
        if ($admins->isEmpty()) throw ValidationException::withMessages(['order' => 'Tidak ada admin aktif untuk pembagian.']);
        $referral = DB::table('order_referrals')->where('order_id', $order->id)->first();
        $owner = $referral ? User::whereKey($referral->owner_id)->whereIn('role', ['admin', 'marketing'])->first() : null;
        if ($referral && !$owner) throw ValidationException::withMessages(['order' => 'Pemilik referral perlu diperiksa.']);
        $commission = $owner ? intdiv($gross, 10) : 0;
        $remaining = $gross - $commission;
        $allocations = [];
        if ($owner) {
            $wallet = Cuanin::wallet($owner);
            Cuanin::entry($wallet, $commission, 'referral', 'referral:'.$order->id, 'Komisi referral '.$order->order_number);
            $allocations[] = ['user_id' => $owner->id, 'name' => $owner->name, 'kind' => 'Komisi referral 10%', 'amount' => $commission];
            DB::table('referral_conversions')->insertOrIgnore(['owner_id' => $owner->id, 'client_id' => $order->user_id, 'created_at' => now()]);
            if ($owner->role === 'marketing') {
                $count = DB::table('referral_conversions')->where('owner_id', $owner->id)->count();
                $previous = 0;
                foreach (config('marketing.bonuses') as $threshold => $target) {
                    if ($count >= $threshold) DB::table('cuanin_bonus_awards')->insertOrIgnore(['owner_id' => $owner->id, 'threshold' => $threshold, 'amount' => $target - $previous, 'paid' => 0, 'created_at' => now()]);
                    $previous = $target;
                }
            }
        }
        // Fund earned bonuses from this and later net receipts. Never invent money or make balances negative.
        $bonus = 0;
        foreach (DB::table('cuanin_bonus_awards')->whereColumn('paid', '<', 'amount')->orderBy('id')->lockForUpdate()->get() as $award) {
            if ($remaining === 0) break;
            $partner = User::findOrFail($award->owner_id);
            $amount = min($remaining, $award->amount - $award->paid);
            Cuanin::entry(Cuanin::wallet($partner), $amount, 'bonus', 'bonus:'.$order->id.':'.$award->id, 'Bonus '.$award->threshold.' client • '.$order->order_number);
            DB::table('cuanin_bonus_awards')->where('id', $award->id)->update(['paid' => $award->paid + $amount]);
            $allocations[] = ['user_id' => $partner->id, 'name' => $partner->name, 'kind' => 'Bonus '.$award->threshold.' client', 'amount' => $amount];
            $remaining -= $amount; $bonus += $amount;
        }
        $adminCount = $admins->count();
        $share = intdiv($remaining, $adminCount);
        $remainder = $remaining % $adminCount;
        $rotation = DB::table('cuanin_settlements')->count() % $adminCount;
        foreach ($admins as $index => $admin) {
            $slot = ($index - $rotation + $adminCount) % $adminCount;
            $amount = $share + ($slot < $remainder ? 1 : 0);
            Cuanin::entry(Cuanin::wallet($admin), $amount, 'admin_share', 'admin:'.$order->id.':'.$admin->id, 'Bagian admin '.$order->order_number);
            $allocations[] = ['user_id' => $admin->id, 'name' => $admin->name, 'kind' => 'Bagian admin', 'amount' => $amount];
        }
        DB::table('cuanin_settlements')->insert(['order_id' => $order->id, 'order_number' => $order->order_number, 'gross' => $gross, 'commission' => $commission, 'bonus' => $bonus, 'admin_pool' => $remaining, 'admin_count' => $adminCount, 'referral_owner_id' => $owner?->id, 'referral_code' => $referral?->code, 'allocations' => json_encode($allocations, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
