<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CuaninWallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'reserved', 'bank_name', 'account_name', 'account_number'];
    protected $hidden = ['bank_name', 'account_name', 'account_number'];
    protected $casts = ['balance' => 'integer', 'reserved' => 'integer', 'bank_name' => 'encrypted', 'account_name' => 'encrypted', 'account_number' => 'encrypted'];
    public function user() { return $this->belongsTo(User::class); }
    public function available(): int { return $this->balance - $this->reserved; }
}
