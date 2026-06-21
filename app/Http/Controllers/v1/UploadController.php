<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Http\Requests\v1\UploadStoreRequest;
use App\Http\Requests\v1\UploadStoreChunkRequest;
use App\Http\Resources\v1\UploadResource;
use App\Policies\UploadPolicy;
use App\Services\v1\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UploadController extends Controller
{
    public function __construct(
        protected UploadService $uploadService
    ) {
        // 
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param \App\Http\Requests\v1\UploadStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UploadStoreRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('create', Upload::class);

        $upload = $this->uploadService->store($user, $request->except('thumbnail'), $request->file('thumbnail'));

        return response()->json([
            'uuid' => $upload->uuid
        ], 201);
    }

    /**
     * Display the specified resource.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Upload $upload
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Upload $upload): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('view', $upload);

        return response()->json([
            'item' => UploadResource::make($upload)
        ]);
    }

    /**
     * Store a chunk of the upload.
     * 
     * @param \App\Http\Requests\v1\UploadStoreChunkRequest $request
     * @param \App\Models\Upload $upload
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeChunk(UploadStoreChunkRequest $request, Upload $upload): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('putChunk', $upload);

        $this->uploadService->saveChunk($upload, $request->validated('index'), $request->file('chunk'));

        return response()->json(null, 204);
    }

    /**
     * Cancel an upload.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Upload $upload
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, Upload $upload): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('cancel', $upload);

        $upload->update(['status' => 'canceled']);

        return response()->json(null, 204);
    }

    /**
     * Complete upload and return user's `used_bytes`.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Upload $upload
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request, Upload $upload): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('complete', $upload);

        $usedBytes = $this->uploadService->complete($user, $upload);

        return response()->json([
            'used_bytes' => $usedBytes
        ]);
    }
}
