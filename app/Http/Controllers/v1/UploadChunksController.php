<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\UploadChunksRequest;
use App\Models\Upload;
use App\Policies\UploadPolicy;
use App\Services\v1\UploadService;
use Illuminate\Support\Facades\Gate;

class UploadChunksController extends Controller
{
    public function __construct(
        protected UploadService $uploadService
    ) {
        // 
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(UploadChunksRequest $request, Upload $upload)
    {
        Gate::forUser($request->user())
            ->policy(Upload::class, UploadPolicy::class)
            ->authorize('putChunk', $upload);

        $this->uploadService->saveChunk($upload, $request->validated('index'), $request->file('chunk'));

        return response()->json(null, 204);
    }
}
