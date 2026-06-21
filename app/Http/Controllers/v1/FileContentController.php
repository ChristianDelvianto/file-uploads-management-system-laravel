<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\v1\StorageService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileContentController extends Controller
{
    public function __construct(
        protected StorageService $storageService
    ) {
        // 
    }

    /**
     * Serve file content for download.
     * 
     * @param \App\Models\File $file
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(File $file): StreamedResponse
    {
        return response()->streamDownload(function () use ($file) {
            $filePath = "{$file->directory_path}/{$file->storage_name}";

            // Force the output to the browser immediately (Memory Protection)
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $stream = Storage::disk($file->disk)->readStream($filePath);

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $file->full_name);
    }

    /**
     * Serve file content for web access.
     * 
     * @param \App\Models\File $file
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function show(File $file): StreamedResponse
    {
        return response()->stream(function () use ($file) {
            $filePath = "{$file->directory_path}/{$file->storage_name}";

            $stream = Storage::disk($file->disk)->readStream($filePath);

            // "Pipe" the source directly to the PHP output buffer
            fpassthru($stream);
            
            if (is_resource($stream)) {
                fclose($stream);
            }
        },
        200,
        [
            'Cache-Control' => 'no-store, no-cache, private, must-revalidate',
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => "inline; filename=\"{$file->full_name}\"",
            'X-Accel-Buffering' => 'no', // For Nginx/FastCGI to prevent proxy buffering
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }

    /**
     * Serve file's thumbnail for web access.
     * When file is trashed, the file owner can still see the file thumbnail in the trashed page.
     * 
     * @param \App\Models\File $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function showThumbnail(File $file): BinaryFileResponse
    {
        $thumbnailPath = Storage::disk($file->disk)->path("{$file->directory_path}/{$file->thumbnail_name}");

        return response()->file($thumbnailPath, [
            'Cache-Control' => 'no-store, no-cache, private, must-revalidate',
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => "inline; filename=\"{$file->thumbnail_name}\"",
            'X-Accel-Buffering' => 'no', // For Nginx/FastCGI to prevent proxy buffering
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }

    /**
     * Only for `audio` and `video`, stream file content for web access.
     * 
     * @param \App\Models\File $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function stream(File $file): BinaryFileResponse
    {
        $filePath = Storage::disk($file->disk)->path("{$file->directory_path}/{$file->storage_name}");

        return response()->file($filePath, [
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store, no-cache, private, must-revalidate',
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => "inline; filename=\"{$file->full_name}\"",
        ]);
    }
}
