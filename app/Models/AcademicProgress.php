<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicProgress extends Model
{
    use HasFactory;

    protected $table = 'academic_progresses';

    protected $fillable = [
        'client_course_id',
        'session_number',
        'discussion_status',
        'discussion_score',
        'assignment_status',
        'assignment_score',
        'notes',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'discussion_score' => 'integer',
        'assignment_score' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(ClientCourse::class, 'client_course_id');
    }
}
