<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\AuthNewTokenRequest;
use App\Http\Requests\v1\AuthNewUserRequest;
use App\Http\Resources\PlanUserResource;
use App\Http\Resources\UserResource;
use App\Models\PlanUser;
use App\Models\User;
use App\Services\v1\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        public AuthService $authService,
    ) {
        // 
    }

    /**
     * Send user info, (this is when app first load)
     */
    public function userInfo(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $planUser = PlanUser::with(['plan'])->firstWhere('user_id', $user->id);

        return response()->json([
            'plan' => PlanUserResource::make($planUser),
            'profile' => UserResource::make($user),
            'role' => $user->role,
            'used_bytes' => $user->used_bytes,
        ]);
    }

    /**
     * Revoke user's current access token
     */
    public function revokeCurrentToken(Request $request): JsonResponse
    {
        $request->user('sanctum')->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * Generate a new access token with the right credentials
     */
    public function newToken(AuthNewTokenRequest $request): JsonResponse
    {
        $user = User::firstWhere('email', $request->validated('email'));

        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.']
            ]);
        }

        $planUser = PlanUser::with(['plan'])->firstWhere('user_id', $user->id);

        $plainToken = $this->authService->generateNewToken($user);

        return response()->json([
            'plan' => PlanUserResource::make($planUser),
            'profile' => UserResource::make($user),
            'role' => $user->role,
            'token' => $plainToken,
            'used_bytes' => $user->used_bytes,
        ]);
    }

    /**
     * Store new user record
     */
    public function newUser(AuthNewUserRequest $request): JsonResponse
    {
        $responseData = $this->authService->registerNewUser($request->validated());

        return response()->json($responseData);
    }
}
