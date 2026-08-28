<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\AuthLoginRequest;
use App\Http\Resources\v1\PlanUserResource;
use App\Http\Resources\v1\UserResource;
use App\Models\PlanUser;
use App\Models\User;
use App\Models\UserQuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthLoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AuthLoginRequest $request)
    {
        $user = User::firstWhere('email', $request->validated('email'));

        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.']
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $userQuota = UserQuota::firstWhere('user_id', $user->id);

        $userPlan = PlanUser::with(['plan'])->firstWhere('user_id', $user->id);

        return response()->json([
            'plan' => PlanUserResource::make($userPlan),
            'profile' => UserResource::make($user),
            'used_bytes' => $userQuota->used_bytes
        ]);
    }
}
