<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PaymentProofFiles
{
    public static function location(?string $reference): ?array
    {
        if (!$reference || !preg_match('~^(private/)?payment_proofs/[A-Za-z0-9_-][A-Za-z0-9._-]*\.(?:jpg|jpeg|png|pdf)$~iD', $reference)) return null;
        $private = str_starts_with($reference, 'private/');
        return ['disk' => $private ? 'local' : 'public', 'path' => $private ? substr($reference, 8) : $reference];
    }

    public static function resolve(?string $reference): ?string
    {
        $location = self::location($reference);
        if (!$location) return null;
        $disk = Storage::disk($location['disk']);
        $root = realpath($disk->path(''));
        $file = realpath($disk->path($location['path']));
        // Refuse traversal and symlinks pointing outside the configured disk root.
        if (!$root || !$file || !str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) || !is_file($file) || !is_readable($file)) return null;
        return $file;
    }

    public static function delete(string $reference): bool
    {
        $location = self::location($reference);
        if (!$location) return false;
        $disk = Storage::disk($location['disk']);
        if (!$disk->exists($location['path'])) return true;
        if (!self::resolve($reference)) return false;
        return $disk->delete($location['path']);
    }
}
