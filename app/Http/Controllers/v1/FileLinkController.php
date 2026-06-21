<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\NonceService;
use App\Services\v1\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileLinkController extends Controller
{
    public function __construct(
        protected NonceService $nonceService,
        protected StorageService $storageService
    ) {
        // 
    }

    /**
     * Generate download link.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function download(Request $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('download', $file);

        $file->activities()->create(['action' => 'download', 'ip_address' => $request->ip(), 'user_id' => $user?->id ?? null]);

        $nonce = $this->nonceService->generateNonce($file, $request->ip(), $request->userAgent());

        $url = $this->storageService->generateAccessRoute(
                    'api.v1.file.content.download',
                    [
                        'file' => $file->uuid,
                        'nonce' => $nonce
                    ]
                );

        return response()->json([
            'url' => $url
        ]);
    }

    /**
     * Generate share link.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function share(Request $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        // @todo

        return response()->json([
            // 
        ]);
    }

    /**
     * Generate stream content link. (Only for `audio` and `video`)
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\File $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function stream(Request $request, File $file): JsonResponse
    {
        $user = $request->user('sanctum');

        Gate::forUser($user)->policy(File::class, FilePolicy::class)->authorize('view', $file);

        $url = null;

        // Only when file status is 'completed', then we will provide link access to file's content
        if ($file->status === 'completed') {
            $nonce = $this->nonceService->generateNonce($file, $request->ip(), $request->userAgent());

            $url = $this->storageService->generateAccessRoute(
                        'api.v1.file.content.stream',
                        [
                            'file' => $file->uuid,
                            'nonce' => $nonce
                        ]
                    );
        }

        return response()->json([
            'status' => $file->status,
            'url' => $url
        ]);
    }
}
