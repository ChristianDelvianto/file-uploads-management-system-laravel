<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\v1\FileStorageService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileContentController extends Controller
{
    public function __construct(
        private FileStorageService $fileStorageService
    ) {
        // 
    }

    /**
     * Serve file content for download.
     * 
     * @param \App\Models\File $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadContent(File $file): BinaryFileResponse|StreamedResponse
    {
        return response()->streamDownload(function () use ($file) {
            // 1. Force the output to the browser immediately (Memory Protection)
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            // 2. Open a stream from the private cloud
            $stream = Storage::disk($file->disk)->readStream("files/{$file->uuid}/{$file->storage_name}");

            // 3. Pipe the cloud data directly to the user
            fpassthru($stream);

            // 4. Securely close the connection
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $file->full_name);
    }

    /**
     * Serve file content for web access.
     * 
     * @param \App\Models\File $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function showContent(File $file): BinaryFileResponse|StreamedResponse
    {
        // Only for audio or video
        if (in_array($file->category, ['audio', 'video'])) {
            $filePath = $this->fileStorageService->getContentPath($file);

            return response()->file($filePath, [
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-store, no-cache, private, must-revalidate',
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => "inline; filename=\"{$file->full_name}\"",
            ]);
        }

        return response()->stream(function () use ($file) {
            $stream = Storage::disk($file->disk)->readStream("files/{$file->uuid}/$file->storage_name");

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
     * Serve file's thumbnail for web access (This method does not use Route Model Binding).
     * When file is trashed, the file owner can still see the file thumbnail in the trashed page.
     * 
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function showThumbnail(string $uuid): StreamedResponse
    {
        $file = File::withTrashed()->where('uuid', $uuid)->firstOrFail();

        return response()->stream(function () use ($file) {
            $stream = Storage::disk($file->disk)->readStream("files/{$file->uuid}/{$file->thumbnail_name}");

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        },
        200,
        [
            'Cache-Control' => 'no-store, no-cache, private, must-revalidate',
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => "inline; filename=\"{$file->thumbnail_name}\"",
            'X-Accel-Buffering' => 'no', // For Nginx/FastCGI to prevent proxy buffering
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }
}
