<?php

namespace App\Services\v1;

use App\Http\Resources\PlanUserResource;
use App\Http\Resources\UserResource;
use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\User;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Generate a new access token with the right credentials
     */
    public function generateNewToken(User $user, ?array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): string
    {
        $tokenName = Str::random(config('sanctum.token_name_length'));
        $token = $user->createToken($tokenName, $abilities, $expiresAt);

        return $token->plainTextToken;
    }

    /**
     * Store new user and assign free plan, then generate access token
     */
    public function registerNewUser(array $userData): array
    {
        try {
            return DB::transaction(function () use ($userData): array {
                $freePlan = Plan::firstWhere('price', 0);

                $user = User::create(['role' => 'user', ...$userData]);

                $planUser = PlanUser::create(['plan_id' => $freePlan->id, 'user_id' => $user->id]);
                $planUser->setRelation('plan', $freePlan);

                $plainToken = $this->generateNewToken($user);

                // Send verification email

                return [
                    'plan' => PlanUserResource::make($planUser),
                    'profile' => UserResource::make($user),
                    'token' => $plainToken,
                ];
            });
        } catch (Exception $e) {
            throw new Exception('Internal server error.', 500);
        }
    }
}
