<?php

namespace App\Services\v1;

use App\Models\Upload;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class StorageService
{
    /**
     * Create a signed URL for access.
     * 
     * @param string $routeName
     * @param array $routeParams
     * @return string
     */
    public function generateAccessRoute(string $routeName, array $routeParams): string
    {
        $baseDuration = config('filesystems.file_signed_url_duration');

        return URL::temporarySignedRoute($routeName, now()->addSeconds($baseDuration), $routeParams);
    }

    /**
     * Generate main content storage name.
     * 
     * @param string $extension
     * @return string
     */
    public function generateStorageName(string $extension): string
    {
        return uniqid('__-') . '__' . Str::random() . '.' . $extension;
    }

    /**
     * Generate thumbnail storage name.
     * 
     * @return string
     */
    public function generateThumbnailStorageName(): string
    {
        return '__thumbnail' . $this->generateStorageName('jpeg');
    }

    /**
     * Merge all chunks into a single file.
     * 
     * @param \App\Models\Upload $upload
     * @param string $fileName
     * @return void
     */
    public function mergeChunks(Upload $upload, string $fileName): void
    {
        $fileDirectoryFinalPath = "files/{$upload->uuid}/{$fileName}";

        $disk = Storage::disk($upload->disk);

        if (!$disk->exists($upload->directory_path)) {
            throw new Exception('Upload directory does not exists.', 500);
        }

        $filePath = $disk->path($fileDirectoryFinalPath);

        $file = fopen($filePath, 'ab');

        for ($index = 0; $index < $upload->chunk_count; $index++) {
            $chunkFileName = "_part_{$index}.{$upload->extension}";

            $chunkData = $disk->readStream("{$upload->directory_path}/chunks/{$chunkFileName}");

            if (!$chunkData) {
                throw new Exception("Chunk resource for index {$index} not found.", 500);
            }

            if (!stream_copy_to_stream($chunkData, $file)) {
                throw new Exception('Something error when write stream the file.', 500);
            }

            fclose($chunkData);
        }

        fclose($file);
    }
}
