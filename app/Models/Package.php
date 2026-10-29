<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'price_label',
        'features',
        'is_recommended',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_recommended' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
