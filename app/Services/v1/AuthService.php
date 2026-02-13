<?php

namespace App\Services\v1;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Str;

class AuthService
{
    public function generateNewToken(User $user, ?array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): string
    {
        $tokenName = Str::random(config('sanctum.token_name_length'));
        $token = $user->createToken($tokenName, $abilities, $expiresAt);

        return $token->plainTextToken;
    }
}
