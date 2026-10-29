<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElearningCredential extends Model
{
    protected $table = 'client_elearning_credentials';
    protected $fillable = ['user_id', 'login', 'password', 'consented_at'];
    protected $hidden = ['login', 'password'];
    protected $casts = ['login' => 'encrypted', 'password' => 'encrypted', 'consented_at' => 'datetime'];

    public function revision(): string
    {
        return hash_hmac('sha256', json_encode([$this->id, $this->user_id, $this->getRawOriginal('login'), $this->getRawOriginal('password')], JSON_THROW_ON_ERROR), config('app.key'));
    }
}
