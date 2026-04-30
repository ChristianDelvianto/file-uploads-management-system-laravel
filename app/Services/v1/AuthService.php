<?php

namespace App\Services\v1;

use App\Models\PlanUser;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Generate new sanctum token
     * 
     * @param \App\Models\User $user
     * @param ?array $abilities
     * @param ?DateTimeInterface $expiresAt
     * @return string
     */
    public function generateNewToken(User $user, ?array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): string
    {
        $tokenName = Str::random(config('sanctum.token_name_length'));

        $token = $user->createToken($tokenName, $abilities, $expiresAt);

        return $token->plainTextToken;
    }

    /**
     * Get user's current plan
     * 
     * @param \App\Models\User $user
     * @return \App\Models\PlanUser with loaded plan relation
     */
    public function getUserPlan(User $user): PlanUser
    {
        return PlanUser::with(['plan'])->firstWhere('user_id', $user->id);
    }
}
