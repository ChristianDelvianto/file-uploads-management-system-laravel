<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\UserGetFilesRequest;
use App\Http\Requests\v1\UserGetSharedFilesRequest;
use App\Http\Requests\v1\UserGetTrashedFilesRequest;
use App\Http\Resources\v1\UserFileResource;
use App\Services\v1\UserFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __construct(
        protected UserFileService $userFileService
    ) {
        // 
    }

    /**
     * Display a listing of the resource.
     * 
     * @param \App\Http\Requests\v1\UserGetFilesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function files(UserGetFilesRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $items = $this->userFileService->getFiles($user, $request->validated('category'), $request->validated('oldest'), $request->validated('timestamp'));

        return response()->json([
            'items' => UserFileResource::collection($items)
        ]);
    }

    /**
     * Display a listing of the resource. (Others shared)
     * 
     * @param \App\Http\Requests\v1\UserGetSharedFilesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sharedToUser(UserGetSharedFilesRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $items = $this->userFileService->getSharedToUser($user, $request->validated('oldest'), $request->validated('timestamp'));

        return response()->json([
            'items' => UserFileResource::collection($items)
        ]);
    }

    /**
     * Permanently delete all trashed files.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearTrash(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        
        $timestamp = now();

        $usedBytes = $this->userFileService->deleteTrashed($user, $timestamp);

        return response()->json([
            'used_bytes' => $usedBytes
        ]);
    }

    /**
     * Display a listing of soft deleted resource.
     * 
     * @param \App\Http\Requests\v1\UserGetTrashedFilesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash(UserGetTrashedFilesRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $items = $this->userFileService->getTrashedFiles($user, $request->validated('oldest'), $request->validated('timestamp'));

        return response()->json([
            'items' => UserFileResource::collection($items)
        ]);
    }
}
