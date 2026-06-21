<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\FileUpdateVisibilityRequest;
use App\Http\Resources\v1\UserFileResource;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FileUpdateVisibilityController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {
        // 
    }

    /**
     * Update file visibility of the specified resource in storage.
     * 
     * @param \App\Http\Requests\v1\FileUpdateVisibilityRequest $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(FileUpdateVisibilityRequest $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('update', $file);

        $file = $this->fileService->updateVisibility($file, $request->validated('visibility'),$request->validated('emails'));

        return response()->json([
            'item' => UserFileResource::make($file)
        ]);
    }
}
