<?php

namespace App\Services\v1;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileService
{
    public function getCategory(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        $documentMimes = config('filesystems.type.document');

        if (Str::startsWith($mimeType, 'image/')) {
            $category = 'image';
        } elseif (Str::startsWith($mimeType, 'video/')) {
            $category = 'video';
        } elseif (Str::startsWith($mimeType, 'audio/')) {
            $category = 'audio';
        } elseif (in_array($mimeType, $documentMimes)) {
            $category = 'document';
        } else {
            $category = 'others';
        }

        return $category;
    }
}
