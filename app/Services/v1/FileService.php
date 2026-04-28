<?php

namespace App\Services\v1;

use Illuminate\Support\Str;

class FileService
{
    /**
     * Generate file storage name
     * 
     * @param string $extension
     * @return string
     */
    public function generateStorageName(string $extension): string
    {
        return uniqid('__-') . '__' . Str::random() . '.' . $extension;
    }

    /**
     * Generate thumbnail storage name
     * 
     * @return string
     */
    public function generateThumbnailStorageName(): string
    {
        return 'thumbnail' . $this->generateStorageName('jpeg');
    }
}
