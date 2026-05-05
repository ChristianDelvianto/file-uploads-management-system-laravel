<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\AuthNewTokenRequest;
use App\Http\Requests\v1\AuthNewUserRequest;
use App\Http\Resources\v1\PlanUserResource;
use App\Http\Resources\v1\UserResource;
use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\User;
use App\Services\v1\AuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        public AuthService $authService
    ) {
        // 
    }

    /**
     * Send user info
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userInfo(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $userPlan = $this->authService->getUserPlan($user);

        return response()->json([
            'plan' => PlanUserResource::make($userPlan),
            'profile' => UserResource::make($user),
            'role' => $user->role,
            'used_bytes' => $user->used_bytes
        ]);
    }

    /**
     * Revoke user's current access token
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeCurrentToken(Request $request): JsonResponse
    {
        $request->user('sanctum')->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * Generate a new access token with the right credentials
     * 
     * @param \App\Http\Requests\v1\AuthNewTokenRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newToken(AuthNewTokenRequest $request): JsonResponse
    {
        $user = User::firstWhere('email', $request->validated('email'));

        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.']
            ]);
        }

        $userPlan = $this->authService->getUserPlan($user);

        $plainToken = $this->authService->generateNewToken($user);

        return response()->json([
            'plan' => PlanUserResource::make($userPlan),
            'profile' => UserResource::make($user),
            'role' => $user->role,
            'token' => $plainToken,
            'used_bytes' => $user->used_bytes
        ]);
    }

    /**
     * Store new user record
     * 
     * @param \App\Http\Requests\v1\AuthNewUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newUser(AuthNewUserRequest $request): JsonResponse
    {
        $freePlan = Plan::firstWhere('price_cents', 0);

        if (!$freePlan) {
            $exception = new Exception('Free plan not found.', 500);

            report($exception);

            throw $exception;
        }

        // Password will be hashed by User model's cast
        $user = User::create(['role' => 'user', ...$request->validated()]);

        $userPlan = PlanUser::create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);
        $userPlan->setRelation('plan', $freePlan); // For response

        $plainToken = $this->authService->generateNewToken($user);

        return response()->json([
            'plan' => PlanUserResource::make($userPlan),
            'profile' => UserResource::make($user),
            'role' => $user->role,
            'token' => $plainToken,
            'used_bytes' => $user->used_bytes
        ], 201);
    }
}
