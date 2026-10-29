<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminChatMessage extends Model
{
    protected $fillable = ['user_id', 'sender_name', 'client_message_id', 'body'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
