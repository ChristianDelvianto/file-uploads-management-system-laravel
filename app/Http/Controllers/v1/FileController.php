<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\FileUpdateNameRequest;
use App\Http\Requests\v1\FileUpdateVisibilityRequest;
use App\Http\Resources\v1\PublicFileResource;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\FileDestroyService;
use App\Services\v1\FileUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileController extends Controller
{
    public function __construct(
        private FileDestroyService $fileDestroyService,
        private FileUpdateService $fileUpdateService
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

        $file->load(['shares', 'user']);

        return response()->json([
            'item' => PublicFileResource::make($file)
        ]);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param \App\Http\Requests\v1\FileUpdateNameRequest $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateName(FileUpdateNameRequest $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('update', $file);

        $file = $this->fileUpdateService->updateName($user, $file, $request->validated('name'));

        return response()->json([
            'item' => PublicFileResource::make($file)
        ]);
    }

    /**
     * Update file's visibility of the specified resource in storage.
     * 
     * @param \App\Http\Requests\v1\FileUpdateVisibilityRequest $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVisibility(FileUpdateVisibilityRequest $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('update', $file);

        $file = $this->fileUpdateService->updateVisibility(
                    $user,
                    $file,
                    $request->validated('visibility'),
                    $request->validated('emails')
                );

        return response()->json([
            'item' => PublicFileResource::make($file)
        ]);
    }

    /**
     * Permanently delete resource from storage. (This method will not use Route model binding).
     * If file already deleted, the method will return user's newest used_bytes without throwing error to prevent frontend complexity and accidental multiple entries in activity log when user clicks "Delete Permanently" multiple times due to network issues or impatience. Idempotency applied to ensure that only one "permanent delete" activity is logged per file regardless of how many times the user clicks the button.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyTrash(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user('sanctum');
        $usedBytes = $user->used_bytes;

        $file = File::withTrashed()->where('uuid', $uuid)->first();

        if ($file) {
            Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('forceDelete', $file);

            $usedBytes = $this->fileDestroyService->permanentlyDeleteTrash($user, $file);
        }

        return response()->json([
            'used_bytes' => $usedBytes
        ]);
    }

    /**
     * Set a file as trash (This method will not use Route model binding for idempotency).
     * Idempotency applied to reduce frontend complexity and prevent accidental multiple entries in activity log when user clicks "Move to Trash" multiple times due to network issues or impatience. The method will check if the file is already trashed before attempting to move it to trash again, ensuring that only one "move to trash" activity is logged per file regardless of how many times the user clicks the button.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsTrash(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user('sanctum');

        $file = File::withTrashed()->where('uuid', $uuid)->first();

        if ($file) {
            Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('setAsTrash', $file);

            if (!$file->trashed()) {
                $this->fileDestroyService->putToTrash($user, $file);
            }
        }

        return response()->json(null, 204);
    }

    /**
     * Restore trashed file (This method will not use Route model binding).
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreFromTrash(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user('sanctum');

        $file = File::withTrashed()->where('uuid', $uuid)->firstOrFail();

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('restore', $file);

        if ($file->trashed()) {
            $this->fileDestroyService->restoreFromTrash($user, $file);
        }

        return response()->json(null, 204);
    }
}
