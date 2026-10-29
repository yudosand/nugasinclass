<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class CourseImport
{
    public static function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    public static function key(string $value): string
    {
        return mb_strtolower(self::normalize($value), 'UTF-8');
    }

    public static function parse(string $text): array
    {
        $courses = [];
        foreach (preg_split('/\R/u', $text) as $index => $line) {
            $line = self::normalize($line);
            if ($line === '') continue;
            $parts = explode('|', $line);
            $code = count($parts) === 2 ? self::normalize($parts[0]) : null;
            $name = self::normalize($parts[count($parts) === 2 ? 1 : 0]);
            if (count($parts) > 2 || $name === '' || mb_strlen($name) > 255 || ($code !== null && ($code === '' || mb_strlen($code) > 50))) {
                throw ValidationException::withMessages(['course_list' => 'Baris '.($index + 1).': gunakan nama mata kuliah (maksimal 255 karakter), atau KODE | Nama (kode maksimal 50 karakter).']);
            }
            $courses[] = ['course_name' => $name, 'course_code' => $code];
        }
        if (!$courses || count($courses) > 50) {
            throw ValidationException::withMessages(['course_list' => 'Masukkan 1–50 mata kuliah, satu mata kuliah per baris.']);
        }
        return $courses;
    }
}
