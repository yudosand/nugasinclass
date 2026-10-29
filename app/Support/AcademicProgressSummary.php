<?php

namespace App\Support;

use Illuminate\Support\Collection;

class AcademicProgressSummary
{
    public static function forCourses(Collection $courses): array
    {
        $items = $courses->map(function ($course) {
            $rows = $course->progresses->keyBy('session_number');
            $latest = 0;
            $completed = 0;
            for ($session = 1; $session <= 8; $session++) {
                $row = $rows->get($session);
                if ($row && ($row->discussion_status !== 'Belum Dikerjakan' || $row->assignment_status !== 'Belum Dikerjakan' || $row->discussion_score !== null || $row->assignment_score !== null)) $latest = $session;
                if ($completed === $session - 1 && $row && $row->discussion_status === 'Selesai' && $row->assignment_status === 'Selesai') $completed = $session;
            }
            return ['name' => $course->course_name, 'latest' => $latest, 'completed' => $completed];
        });
        return ['latest' => $items->max('latest') ?? 0, 'completed' => $items->min('completed') ?? 0, 'courses' => $items->all()];
    }
}
