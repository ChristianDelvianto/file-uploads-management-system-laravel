<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileTrashController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {
        // 
    }

    /**
     * Set a file as trash. Idempotency applied to reduce frontend complexity and prevent accidental multiple entries in activity log when user clicks "Move to Trash" multiple times due to network issues or impatience. The method will check if the file is already trashed before attempting to move it to trash again, ensuring that only one "move to trash" activity is logged per file regardless of how many times the user clicks the button.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('setAsTrash', $file);

        if (!$file->trashed()) {
            $this->fileService->putToTrash($file);
        }

        return response()->json(null, 204);
    }
}
