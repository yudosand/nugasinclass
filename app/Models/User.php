<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nomor_hp',
        'email',
        'password',
        'role',
        'status',
        'rejection_reason',
        'handler_admin_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(ClientProfile::class);
    }

    // Keep legacy client links valid; all other roles use a stable account key.
    public function getEmailForPasswordReset(): string
    {
        return $this->role === 'client' ? $this->nomor_hp : $this->role.':'.$this->id;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function courses()
    {
        return $this->hasMany(ClientCourse::class);
    }

    public function studyCourses()
    {
        return $this->hasMany(StudyCourse::class);
    }

    public function handlerAdmin()
    {
        return $this->belongsTo(User::class, 'handler_admin_id');
    }

    public function handledClients()
    {
        return $this->hasMany(User::class, 'handler_admin_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMarketing(): bool { return $this->role === 'marketing'; }

    public function isStudent(): bool { return $this->role === 'student'; }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->profile && $this->profile->is_onboarding_completed;
    }
}
