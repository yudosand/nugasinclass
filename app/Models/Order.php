<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'package_id',
        'amount',
        'payment_status',
        'payment_proof',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
    public function distribution() { return $this->hasOne(PaymentDistribution::class); }
    public function cashAccount() { return $this->hasOne(CashAccount::class, 'order_id'); }
    public function getReceivedAmountAttribute(): ?int
    {
        if (!$this->relationLoaded('cashAccount') && !\App\Support\CashSchema::active()) return null;
        return (int) ($this->cashAccount?->received ?? 0);
    }
    public function getOutstandingAmountAttribute(): int { return max(0, \App\Support\Cuanin::rupiah($this->amount) - ($this->received_amount ?? 0)); }
    public function getPaymentLabelAttribute(): string
    {
        $received = $this->received_amount;
        if ($received !== null) {
            if ($this->outstanding_amount === 0) return 'Lunas';
            if ($received > 0) return 'Dibayar Sebagian';
        }
        return ['approved' => 'Approved', 'paid' => 'Perlu Verifikasi', 'rejected' => 'Ditolak', 'pending' => 'Belum Dibayar'][$this->payment_status] ?? $this->payment_status;
    }
}
