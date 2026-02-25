<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\FileDestroyRequest;
use App\Http\Requests\v1\FileStoreRequest;
use App\Http\Requests\v1\FileUpdateRequest;
use App\Models\File;
use App\Services\v1\FileService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(
        public FileService $fileService,
    ) {
        // 
    }

    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     //
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FileStoreRequest $request): JsonResponse
    {
        $file = $this->fileService->storeFileAndCreateRecord($request->user(), $request->file('file'), $request->file('thumbnail'));

        return response()->json([
            'file' => $file,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, File $file): JsonResponse
    {
        $file->load('user');

        return response()->json([
            'file' => $file,
        ]);
    }

    /**
     * Server file content (for streaming)
     */
    public function showContent(Request $request, File $file): BinaryFileResponse|StreamedResponse
    {
        $filePath = Storage::path("{$file->uuid}/{$file->name}");

        if ($file->category === 'video') {
            return response()->stream(function () use ($filePath) {
                $handle = fopen($filePath, 'rb');

                if ($handle === false) {
                    throw new Exception('Failed to open file stream.', 500);
                }

                while (!feof($handle)) {
                    echo fread($handle, 8192);

                    flush();
                }

                fclose($handle);
            });
        }

        return response()->file($filePath, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    /**
     * Serve file's thumbnail
     */
    public function showThumbnail(Request $request, File $file): BinaryFileResponse
    {
        if (!$file->thumbnail_path) {
            throw new Exception('Thumbnail not available for this file.', 404);
        }

        $thumbnailPath = Storage::path("{$file->uuid}/{$file->thumbnail_path}");

        return response()->file($thumbnailPath, [
            'Content-Type' => 'image/jpeg',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FileUpdateRequest $request, File $file): JsonResponse
    {
        $file = $this->fileService->updateFileRecord($file, $request->validated());

        return response()->json([
            'item' => $file,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FileDestroyRequest $request, File $file): JsonResponse
    {
        $this->fileService->softDeleteFile($file);

        return response()->json(null, 204);
    }

    /**
     * Download a file
     */
    public function download(Request $request, File $file): BinaryFileResponse
    {
        $filePath = Storage::path("{$file->uuid}/{$file->name}");

        return response()->download($filePath, $file->name);
    }
}
