<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\FileUpdateNameRequest;
use App\Http\Resources\v1\UserFileResource;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FileUpdateNameController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {
        // 
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param \App\Http\Requests\v1\FileUpdateNameRequest $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(FileUpdateNameRequest $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('update', $file);

        $file = $this->fileService->updateName($file, $request->validated('name'));

        return response()->json([
            'item' => UserFileResource::make($file)
        ]);
    }
}
