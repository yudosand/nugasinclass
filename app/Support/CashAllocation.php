<?php
namespace App\Support;

use Illuminate\Validation\ValidationException;

// Pure integer arithmetic shared by reconciliation previews and actual receipts.
class CashAllocation
{
    public static function split(array $account, int $amount, array $people, array &$state): array
    {
        $allocations = [];
        $owner = $account['referral_owner_id'];
        $before = (int) $account['received'];
        $commission = $owner ? intdiv($before + $amount, 10) - intdiv($before, 10) : 0;
        $remaining = $amount - $commission;
        if ($owner) {
            if (!isset($people[$owner])) throw ValidationException::withMessages(['cash' => 'Pemilik referral tidak ditemukan. Periksa data penerima.']);
            if ($commission) $allocations[] = self::allocation($people, $owner, 'referral', $commission);
            // One successful referral per unique client, only after full payment.
            if ($before + $amount === (int) $account['total']) {
                $client = $account['client_id'];
                if (!$client) throw ValidationException::withMessages(['cash' => 'Order referral lama yang dihapus tidak memiliki identitas client. Pulihkan data order sebelum mencocokkan kas.']);
                if (!isset($state['conversions'][$client])) {
                    $state['conversions'][$client] = (int) $owner;
                    if (in_array($people[$owner]['role'], ['marketing', 'marketing_deleted'], true)) {
                        $count = count(array_filter($state['conversions'], fn ($id) => $id === (int) $owner));
                        $previous = 0;
                        foreach (config('marketing.bonuses') as $threshold => $target) {
                            if ($count >= $threshold) $state['bonuses'][$owner.':'.$threshold] ??= ['owner_id' => $owner, 'threshold' => $threshold, 'amount' => $target - $previous, 'paid' => 0];
                            $previous = $target;
                        }
                    }
                }
            }
        }
        // A price correction may complete a referral without receiving new money.
        if ($amount === 0) return [];
        foreach ($state['bonuses'] as &$bonus) {
            if (!$remaining) break;
            $funded = min($remaining, $bonus['amount'] - $bonus['paid']);
            if ($funded > 0) {
                $allocations[] = self::allocation($people, $bonus['owner_id'], 'bonus', $funded);
                $bonus['paid'] += $funded; $remaining -= $funded;
            }
        }
        unset($bonus);
        $admins = $account['admin_ids'];
        if (!$admins) throw ValidationException::withMessages(['cash' => 'Tidak ada admin untuk pembagian pembayaran.']);
        $count = count($admins); $share = intdiv($remaining, $count); $remainder = $remaining % $count;
        $rotation = $state['rotation'] % $count;
        foreach ($admins as $index => $id) {
            $part = $share + (($index - $rotation + $count) % $count < $remainder ? 1 : 0);
            if ($part) $allocations[] = self::allocation($people, $id, 'admin_share', $part);
        }
        $state['rotation']++;
        return $allocations;
    }

    private static function allocation(array $people, int $id, string $kind, int $amount): array
    {
        if (!isset($people[$id])) throw ValidationException::withMessages(['cash' => 'Penerima pembagian lama tidak ditemukan. Periksa daftar admin/marketing.']);
        return ['user_id' => $id, 'name' => $people[$id]['name'], 'kind' => $kind, 'amount' => $amount];
    }
}
