<?php

namespace App\Services\v1;

use App\Models\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

class TokenService
{
    /**
     * Create a signed URL for access.
     * 
     * @param string $routeName
     * @param array $routeParams
     * @return string
     */
    public function generateAccessRoute(string $routeName, array $routeParams): string
    {
        $env = config('app.env', 'local');
        $frontEndHost = config('app.front_end_host', 'http://localhost:5173');
        $durationInMinutes = config('custom.file.access_signed_url_duration', 5);
        $isURLAbsolute = $env !== 'local';

        $accessRoute = URL::signedRoute(
            $routeName,
            $routeParams,
            now()->addMinutes($durationInMinutes),
            $isURLAbsolute
        );

        if (!$isURLAbsolute) { // Dev environment
            return $frontEndHost . $accessRoute;
        }

        // Prod / Testing / Staging
        return $accessRoute;
    }

    /**
     * Create a short-lived stream token and store in cache.
     * 
     * @param \App\Models\File $file
     * @param string $ipAddress
     * @param string $userAgent
     * @return string
     */
    public function generateToken(File $file, string $ipAddress, string $userAgent): string
    {
        $token = $this->createTokenId();

        $tokenData = [
            'file_id' => $file->id,
            'ip_address' => $ipAddress,
            'ua_hash' => sha1($userAgent),
            // 'expires_at' => now()->addDay()
        ];

        $this->storeToken("file_access_token:{$token}", $tokenData);

        return $token;
    }

    /**
     * Generate token id.
     * 
     * @return string
     */
    public function createTokenId(): string
    {
        $tokenLength = config('custom.file.access_token_length', 64);

        return Str::random($tokenLength);
    }

    /**
     * Get underlying token payload data.
     * 
     * @param string $token
     * @return ?array
     */
    public function getTokenData(string $token): ?array
    {
        $cacheKey = "file_access_token:{$token}";

        return Cache::get($cacheKey, null);
    }

    /**
     * Extend token expiration for continuous media streaming tracking.
     * 
     * @param string $token
     * @param array $tokenData
     */
    public function extendTokenLife(string $token, array $tokenData): void
    {
        $durationInHours = config('custom.file.access_token_duration', 8); // In hours

        $cacheKey = "file_access_token:{$token}";

        Cache::put(
            $cacheKey,
            $tokenData,
            now()->addHours($durationInHours)
        );
    }

    /**
     * Check if asset qualifies for continuous stream tracking windows.
     * 
     * @param \App\Models\File $file
     * @return bool
     */
    public function isStreamable(File $file): bool
    {
        return in_array($file->category, ['audio', 'document', 'video']);
    }

    /**
     * Store token data in cache.
     * 
     * @param string $key
     * @param array $tokenData
     */
    public function storeToken(string $key, array $tokenData): void
    {
        $durationInHours = config('custom.file.file_access_token_duration', 8); // In hours

        Cache::put($key, $tokenData, now()->addHours($durationInHours));
    }

    /**
     * Verify token footprint payload against incoming headers.
     * 
     * @param \App\Models\File $file
     * @param string $ipAddress
     * @param string $userAgent
     * @return bool
     */
    public function verifyToken(File $file, string $ipAddress, string $userAgent, array $tokenData): bool
    {
        if ($tokenData['file_id'] !== $file->id) {
            return false;
        }

        if (IpUtils::checkIp($ipAddress, $tokenData['ip_address']) === false) {
            return false;
        }

        return hash_equals($tokenData['ua_hash'], sha1($userAgent));
    }

    /**
     * Invalidate token immediately for standard downloads.
     * 
     * @param string $token
     */
    public function invalidateToken(string $token): void
    {
        $cacheKey = "file_access_token:{$token}";

        Cache::forget($cacheKey);
    }
}
