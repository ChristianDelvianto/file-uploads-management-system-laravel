<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\PlanUserResource;
use App\Http\Resources\v1\UserResource;
use App\Models\PlanUser;
use App\Models\UserQuota;
use Illuminate\Http\Request;

class AuthAccountController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $userQuota = UserQuota::firstWhere('user_id', $request->user()->id);

        $userPlan = PlanUser::with(['plan'])->firstWhere('user_id', $request->user()->id);

        return response()->json([
            'plan' => PlanUserResource::make($userPlan),
            'profile' => UserResource::make($request->user()),
            'used_bytes' => $userQuota->used_bytes
        ]);
    }
}
