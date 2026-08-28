<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\AuthSignupRequest;
use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\User;
use App\Models\UserQuota;
use Exception;
use Illuminate\Support\Facades\DB;

class AuthSignupController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AuthSignupRequest $request)
    {
        try {
            $freePlan = Plan::where('price_cents', 0)->firstOr(function () {
                throw new Exception('Free plan not found.', 500);
            });

            DB::transaction(function () use ($freePlan, $request) {
                // Password will be hashed by User model's cast
                $user = User::create($request->validated());

                UserQuota::create(['user_id' => $user->id]);

                PlanUser::create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);
            });

            return response()->json(null, 204);
        } catch (Exception $e) {
            report($e);

            throw $e;
        }
    }
}
