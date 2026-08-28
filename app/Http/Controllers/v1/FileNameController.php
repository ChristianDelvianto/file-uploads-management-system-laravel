<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\FileNameRequest;
use App\Http\Resources\v1\UserFileResource;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FileNameController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {
        // 
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param \App\Http\Requests\v1\FileNameRequest $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(FileNameRequest $request, File $file): JsonResponse
    {
        Gate::forUser($request->user())
            ->policy(File::class, FilePolicy::class)
            ->authorize('update', $file);

        $file = $this->fileService->updateName($file, $request->validated('name'));

        return response()->json([
            'item' => UserFileResource::make($file)
        ]);
    }
}
