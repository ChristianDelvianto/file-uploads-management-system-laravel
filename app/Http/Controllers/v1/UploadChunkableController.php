<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\UploadChunkableRequest;
use App\Models\Upload;
use App\Policies\UploadPolicy;
use App\Services\v1\UploadService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class UploadChunkableController extends Controller
{
    public function __construct(
        protected UploadService $uploadService
    ) {
        // 
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(UploadChunkableRequest $request)
    {
        Gate::forUser($request->user())
            ->policy(Upload::class, UploadPolicy::class)
            ->authorize('create', Upload::class);

        $upload = $this->uploadService->storeChunkable(
            $request->user(),
            $request->except('thumbnail'),
            $request->file('thumbnail')
        );

        $url = URL::temporarySignedRoute('api.v1.uploads.store.chunks', now()->addDays(3), ['upload' => $upload->uuid]);

        return response()->json([
            'url' => $url,
            'uuid' => $upload->uuid
        ], 201);
    }
}
