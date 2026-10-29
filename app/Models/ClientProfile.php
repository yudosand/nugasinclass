<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'universitas',
        'nim',
        'program_studi',
        'semester',
        'detail_akademik',
        'agreed_terms_at',
        'is_onboarding_completed',
    ];

    protected $casts = [
        'agreed_terms_at' => 'datetime',
        'is_onboarding_completed' => 'boolean',
        'semester' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
