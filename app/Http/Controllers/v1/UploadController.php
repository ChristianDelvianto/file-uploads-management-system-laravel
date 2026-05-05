<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Http\Requests\v1\UploadStoreRequest;
use App\Http\Requests\v1\UploadStoreChunkRequest;
use App\Http\Resources\v1\UploadResource;
use App\Policies\UploadPolicy;
use App\Services\v1\UploadCompleteService;
use App\Services\v1\UploadStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UploadController extends Controller
{
    public function __construct(
        public UploadCompleteService $uploadCompleteService,
        public UploadStoreService $uploadStoreService
    ) {
        // 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UploadStoreRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('create', Upload::class);

        $upload = $this->uploadStoreService->storeAndReturnModel($user, $request->except('thumbnail'), $request->file('thumbnail'));

        return response()->json([
            'uuid' => $upload->uuid
        ], 201);
    }

    /**
     * Store a chunk of the upload.
     */
    public function storeChunk(UploadStoreChunkRequest $request, Upload $upload): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('putFileChunk', $upload);

        $this->uploadStoreService->saveChunk($upload, $request->validated('index'), $request->file('chunk'));

        return response()->json(null, 204);
    }

    /**
     * Display the specified resource.
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
     * Cancel an upload.
     */
    public function cancel(Request $request, Upload $upload): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('cancel', $upload);

        $upload->update(['status' => 'canceled']);

        return response()->json(null, 204);
    }

    /**
     * Complete upload and return user's used_bytes.
     */
    public function complete(Request $request, Upload $upload): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(Upload::class, UploadPolicy::class)->authorize('complete', $upload);

        $usedBytes = $this->uploadCompleteService->completeAndReturnUsedBytes($user, $upload);

        return response()->json([
            'used_bytes' => $usedBytes
        ], 201);
    }
}
