<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentDistribution extends Model
{
    protected $table = 'cuanin_settlements';
    public $timestamps = false;
    protected $casts = ['allocations' => 'array', 'gross' => 'integer', 'commission' => 'integer', 'bonus' => 'integer', 'admin_pool' => 'integer'];
}
