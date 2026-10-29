<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyCourse extends Model
{
    protected $fillable = ['user_id', 'course_name', 'course_code', 'semester', 'active_session', 'target_grade'];

    protected $casts = [
        'semester' => 'integer',
        'active_session' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(StudySession::class)->orderBy('session_number');
    }
}
