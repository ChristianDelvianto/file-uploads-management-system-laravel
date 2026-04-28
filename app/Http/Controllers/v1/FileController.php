<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\FileUpdateNameRequest;
use App\Http\Requests\v1\FileUpdateVisibilityRequest;
use App\Http\Resources\v1\FileResource;
use App\Models\File;
use App\Services\v1\FileDestroyService;
use App\Services\v1\FileUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileController extends Controller
{
    public function __construct(
        public FileDestroyService $fileDestroyService,
        public FileUpdateService $fileUpdateService
    ) {
        // 
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->authorize('view', $file);

        $file->load(['user']);

        return response()->json([
            'item' => FileResource::make($file)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateName(FileUpdateNameRequest $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->authorize('update', $file);

        $file = $this->fileUpdateService->updateName($user, $file, $request->validated('name'));

        return response()->json([
            'item' => FileResource::make($file)
        ]);
    }

    /**
     * Update file's visibility of the specified resource in storage.
     */
    public function updateVisibility(FileUpdateVisibilityRequest $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');
        
        Gate::forUser($user)->authorize('update', $file);

        $file = $this->fileUpdateService->updateVisibility(
                    $user,
                    $file,
                    $request->validated('visibility'),
                    $request->validated('emails')
                );

        return response()->json([
            'item' => FileResource::make($file)
        ]);
    }

    /**
     * Permanently delete resource from storage.
     */
    public function destroyPermanently(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user('sanctum');

        $usedBytes = 0;

        $file = File::withTrashed()->where('uuid', $uuid)->first();

        if (!$file) {
            $usedBytes = $user->used_bytes;
        } else {
            Gate::forUser($user)->authorize('delete', $file);

            $usedBytes = $this->fileDestroyService->permanentlyDeleteTrashed($user, $file);
        }

        return response()->json([
            'used_bytes' => $usedBytes
        ]);
    }

    /**
     * Restore soft deleted file.
     */
    public function restore(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user('sanctum');

        $file = File::withTrashed()->where('uuid', $uuid)->first();

        if (!$file) {
            abort(404, 'File already deleted permanently.');
        }

        // @todo - not tested
        Gate::forUser($user)->authorize('restore', $file);

        if ($file->trashed()) {
            $this->fileDestroyService->restoreTrashed($user, $file);
        }

        return response()->json(null, 204);
    }

    /**
     * Soft delete file.
     */
    public function trash(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user('sanctum');

        $file = File::withTrashed()->where('uuid', $uuid)->first();

        if ($file) {
            Gate::forUser($user)->authorize('delete', $file);
        }

        if (!$file?->trashed()) {
            $this->fileDestroyService->setAsTrashed($user, $file);
        }

        return response()->json(null, 204);
    }
}
