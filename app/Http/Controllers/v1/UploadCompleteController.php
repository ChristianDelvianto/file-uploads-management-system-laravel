<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Policies\UploadPolicy;
use App\Services\v1\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UploadCompleteController extends Controller
{
    public function __construct(
        protected UploadService $uploadService
    ) {
        // 
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Upload $upload)
    {
        Gate::forUser($request->user())
            ->policy(Upload::class, UploadPolicy::class)
            ->authorize('complete', $upload);

        $usedBytes = $this->uploadService->complete($upload);

        return response()->json([
            'used_bytes' => $usedBytes
        ]);
    }
}
