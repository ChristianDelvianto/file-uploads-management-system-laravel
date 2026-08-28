<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Policies\FilePolicy;
use App\Services\v1\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileLinkStreamController extends Controller
{
    public function __construct(
        protected StorageService $storageService
    ) {
        // 
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, File $file)
    {
        Gate::forUser($request->user())
            ->policy(File::class, FilePolicy::class)
            ->authorize('view', $file);

        $url = null;

        // Only when file status is 'completed', then we will provide link access to file's content
        if ($file->status === 'completed') {
            $url = $this->storageService->generateAccessRoute('api.v1.file.content.stream', [
                'file' => $file->uuid
            ]);
        }

        return response()->json([
            'status' => $file->status,
            'url' => $url
        ]);
    }
}
