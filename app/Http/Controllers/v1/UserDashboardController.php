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
        $pagination = $this->userFileService->getFiles($request->user('sanctum'), $request->validated('category'), $request->validated('oldest'), $request->validated('cursor'));

        return response()->json([
            'has_more' => $pagination->hasMorePages(),
            'next_cursor' => optional($pagination->nextCursor())?->encode(),
            'items' => UserFileResource::collection($pagination->items())
        ]);
    }

    /**
     * Display a listing of the resource. (Others shared)
     * 
     * @param \App\Http\Requests\v1\UserGetSharedFilesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sharedWithUser(UserGetSharedFilesRequest $request): JsonResponse
    {
        $pagination = $this->userFileService->getSharedWithUser($request->user('sanctum'), $request->validated('oldest'), $request->validated('cursor'));

        return response()->json([
            'has_more' => $pagination->hasMorePages(),
            'next_cursor' => optional($pagination->nextCursor())?->encode(),
            'items' => UserFileResource::collection($pagination->items())
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
        $usedBytes = $this->userFileService->deleteTrashed($request->user('sanctum'), now());

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
        $pagination = $this->userFileService->getTrashedFiles($request->user('sanctum'), $request->validated('oldest'), $request->validated('cursor'));

        return response()->json([
            'has_more' => $pagination->hasMorePages(),
            'next_cursor' => optional($pagination->nextCursor())?->encode(),
            'items' => UserFileResource::collection($pagination->items())
        ]);
    }
}
