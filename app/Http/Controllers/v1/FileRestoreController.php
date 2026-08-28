<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileRestoreController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {
        // 
    }

    /**
     * Restore trashed file.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, File $file): JsonResponse
    {
        Gate::forUser($request->user())
            ->policy(File::class, FilePolicy::class)
            ->authorize('restore', $file);

        if ($file->trashed()) {
            $this->fileService->restoreTrash($request->user(), $file);
        }

        return response()->json(null, 204);
    }
}
