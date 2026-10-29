<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudySession extends Model
{
    protected $fillable = [
        'study_course_id',
        'session_number',
        'attendance_done',
        'discussion_status',
        'assignment_enabled',
        'assignment_status',
        'assignment_deadline',
        'discussion_score',
        'assignment_score',
        'notes',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'attendance_done' => 'boolean',
        'assignment_enabled' => 'boolean',
        'assignment_deadline' => 'date',
        'discussion_score' => 'integer',
        'assignment_score' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(StudyCourse::class, 'study_course_id');
    }
}
