<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class CwisEvidence
{
    public const UPLOAD_ACCEPT = '.pdf,.doc,.docx';

    public static function uploadRules(): array
    {
        return ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'];
    }

    public static function response(string $disk, string $path, ?string $name, bool $preview)
    {
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404);
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($preview && in_array($storage->mimeType($path), ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return $storage->response($path, $name, $headers, 'inline');
        }
        return $storage->download($path, $name, $headers);
    }
}
