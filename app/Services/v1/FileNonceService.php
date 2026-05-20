<?php

namespace App\Services\v1;

use App\Models\File;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FileNonceService
{
    /**
     * Create a nonce and store in cache.
     *
     * @param \App\Models\File $file
     * @param string $ipAddress
     * @param string $userAgent
     * @return string
     */
    public function createNonce(File $file, string $ipAddress, string $userAgent): string
    {
        $duration = config('filesystems.file_nonce_duration');

        $nonce = Str::random(32);
        $encryptedNonce = encrypt($nonce);
        $cacheKey = "file_access_nonce:{$nonce}";

        $nonceData = [
            'file_id' => $file->id,
            'ip_address' => $ipAddress,
            'one_time_access' => !in_array($file->category, ['audio', 'video']),
            'should_refresh' => in_array($file->category, ['audio', 'video']),
            'user_agent' => $userAgent
        ];

        Cache::put($cacheKey, $nonceData, now()->addSeconds($duration));

        return $encryptedNonce;
    }

    /**
     * @param string $encryptedNonce
     */
    public function decryptNonce(string $encryptedNonce): string|null
    {
        try {
            return decrypt($encryptedNonce);
        } catch (DecryptException $e) {
            report($e);

            return null;
        }
    }

    /**
     * Get nonce data.
     * 
     * @param string $nonce
     * @return array|null
     */
    public function getNonceData(string $nonce): array|null
    {
        $cacheKey = "file_access_nonce:{$nonce}";

        return Cache::get($cacheKey, null);
    }

    /**
     * Refresh nonce duration.
     * 
     * @param array $nonceData
     * @return void
     */
    public function refreshNonce(string $nonce, array $nonceData): void
    {
        $duration = config('filesystems.file_nonce_duration');

        $cacheKey = "file_access_nonce:{$nonce}";

        Cache::put($cacheKey, $nonceData, now()->addSeconds($duration));
    }

    /**
     * Verify the nonce data against the provided data.
     * 
     * @param \App\Models\File $file
     * @param string $ipAddress
     * @param string $userAgent
     * @param array $nonceData
     * @return bool
     */
    public function verifyNonce(
        File $file,
        string $ipAddress,
        string $userAgent,
        array $nonceData
    ): bool {
        if ($nonceData['ip_address'] !== $ipAddress) {
            return false;
        }

        if ($nonceData['user_agent'] !== $userAgent) {
            return false;
        }

        if ($nonceData['file_id'] !== $file->id) {
            return false;
        }

        return true;
    }

    /**
     * Remove nonce from cache to prevent multiple access.
     * 
     * @param string $nonce
     * @return void
     */
    public function removeNonce(string $nonce): void
    {
        $cacheKey = "file_access_nonce:{$nonce}";

        Cache::forget($cacheKey);
    }
}
