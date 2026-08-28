<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Policies\UploadPolicy;
use App\Services\v1\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UploadDirectController extends Controller
{
    public function __construct(
        protected UploadService $uploadService
    ) {
        // 
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Gate::forUser($request->user())
            ->policy(Upload::class, UploadPolicy::class)
            ->authorize('create', Upload::class);

        $usedBytes = $this->uploadService->storeDirectly(
            $request->user(),
            $request->except(['file', 'thumbnail']),
            $request->file('file'),
            $request->file('thumbnail')
        );

        return response()->json([
            'used_bytes' => $usedBytes
        ], 201);
    }
}
