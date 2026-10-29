<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_name',
        'course_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function progresses()
    {
        return $this->hasMany(AcademicProgress::class)->orderBy('session_number');
    }
}
