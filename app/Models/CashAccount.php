<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CashAccount extends Model
{
    protected $table = 'cash_accounts';
    protected $primaryKey = 'order_id';
    public $timestamps = false;
    protected $casts = ['total' => 'integer', 'received' => 'integer', 'admin_ids' => 'array'];
}
