<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileContentDownloadController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, File $file)
    {
        $disk = Storage::disk($file->disk);
        $filePath = "{$file->directory_path}/{$file->storage_name}";

        if (!$disk->exists($filePath)) {
            abort(404, 'File content not found.');
        }

        return response()->streamDownload(function () use ($disk, $file, $filePath) {
            // Force the output to the browser immediately (Memory Protection)
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $stream = $disk->readStream($filePath);

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $file->full_name);
    }
}
