<?php

namespace App\Services\v1;

use App\Models\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\IpUtils;

class TokenService
{
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
        $duration = config('filesystems.file_token_duration', 300); // 5 min base window

        $token = Str::random(64);
        $cacheKey = "file_stream_token:{$token}";

        $tokenData = [
            'file_id' => $file->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_streamable' => $this->isFileStreamable($file) // Allows continuous chunked reads
        ];

        Cache::put($cacheKey, $tokenData, $duration);

        // Returning raw secure token is standard industry practice.
        return $token;
    }

    /**
     * Get underlying token payload data.
     * 
     * @param string $token
     * @return ?array
     */
    public function getTokenData(string $token): ?array
    {
        return Cache::get("file_stream_token:{$token}");
    }

    /**
     * Extend token expiration for continuous media streaming tracking.
     * 
     * @param string $token
     * @param array $tokenData
     */
    public function extendTokenLife(string $token, array $tokenData): void
    {
        $duration = config('filesystems.file_token_duration', 300);

        Cache::put("file_stream_token:{$token}", $tokenData, $duration);
    }

    /**
     * Check if asset qualifies for continuous stream tracking windows.
     * 
     * @param \App\Models\File $file
     * @return bool
     */
    public function isFileStreamable(File $file): bool
    {
        return in_array($file->category, ['audio', 'video']);
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

        return $this->verifyUserAgent($userAgent, $tokenData['user_agent']);
    }

    /**
     * Invalidate token immediately for standard downloads.
     * 
     * @param string $token
     */
    public function invalidateToken(string $token): void
    {
        Cache::forget("file_stream_token:{$token}");
    }

    /**
     * Strict Browser footprint verification wrapper.
     * 
     * @param string $requestUserAgent
     * @param string $tokenUserAgent
     * @return bool
     */
    private function verifyUserAgent(string $requestUserAgent, string $tokenUserAgent): bool
    {
        $agent1 = new Agent();
        $agent1->setUserAgent($requestUserAgent);

        $agent2 = new Agent();
        $agent2->setUserAgent($tokenUserAgent);

        return $agent1->browser() === $agent2->browser()
            && $agent1->platform() === $agent2->platform()
            && $agent1->device() === $agent2->device();
    }
}
