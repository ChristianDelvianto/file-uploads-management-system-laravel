<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Policies\UploadPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UploadCancelController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Upload $upload)
    {
        Gate::forUser($request->user())
            ->policy(Upload::class, UploadPolicy::class)
            ->authorize('cancel', $upload);

        if ($upload->status !== 'canceled') { // failed or started
            $upload->update(['status' => 'canceled']);
        }

        return response()->json(null, 204);
    }
}
