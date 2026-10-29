<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CuaninWithdrawal extends Model
{
    protected $fillable = ['wallet_id', 'request_key', 'amount', 'status', 'bank_snapshot', 'processed_by', 'processed_at', 'payment_reference'];
    protected $hidden = ['bank_snapshot'];
    protected $casts = ['amount' => 'integer', 'bank_snapshot' => 'encrypted:array', 'processed_at' => 'datetime'];
    public function wallet() { return $this->belongsTo(CuaninWallet::class); }
}
