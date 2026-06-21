<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FileResource;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {
        // 
    }

    /**
     * Display the specified resource.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('view', $file);

        $file->load(['shared', 'user']);

        return response()->json([
            'item' => FileResource::make($file)
        ]);
    }

    /**
     * Permanently delete resource from storage. (This method will not use Route model binding).
     * If file already deleted, the method will return user's newest `used_bytes` without throwing error to prevent frontend complexity.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user('sanctum');

        $file = File::withTrashed()->where('uuid', $uuid)->first();

        if (!$file) {
            return response()->json([
                'used_bytes' => $user->used_bytes
            ]);
        }

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('forceDelete', $file);

        $usedBytes = $this->fileService->deleteTrash($user, $file);

        return response()->json([
            'used_bytes' => $usedBytes
        ]);
    }
}
