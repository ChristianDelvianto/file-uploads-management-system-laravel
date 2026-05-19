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

        $nonceValue = Str::random(32);
        $encryptedNonce = encrypt($nonceValue);
        $hashedNonce = hash('sha256', $nonceValue);

        $data = [
            'file_id' => $file->id,
            'ip_address' => $ipAddress,
            'nonce' => $nonceValue,
            'should_refresh' => in_array($file->category, ['audio', 'video']),
            'one_time_access' => !in_array($file->category, ['audio', 'video']),
            'user_agent' => $userAgent
        ];

        $cacheKey = "file_access_nonce:{$hashedNonce}";

        Cache::put($cacheKey, $data, now()->addSeconds($duration));

        return $encryptedNonce;
    }

    /**
     * Get nonce data.
     * 
     * @param string $encryptedNonce
     * @return array|null
     */
    public function getNonceData(string $encryptedNonce): array|null
    {
        try {
            $hashedNonce = decrypt($encryptedNonce);

            $cacheKey = "file_access_nonce:{$hashedNonce}";

            return Cache::get($cacheKey, null);
        } catch (DecryptException $e) {
            report($e);

            return null;
        }
    }

    /**
     * Restore the data using same hashed nonce.
     * 
     * @param string $encryptedNonce
     * @param array $data
     * @return void
     */
    public function refreshNonce(string $encryptedNonce, array $data): void
    {
        $duration = config('filesystems.file_nonce_duration');

        try {
            $hashedNonce = decrypt($encryptedNonce);

            $cacheKey = "file_access_nonce:{$hashedNonce}";

            Cache::put($cacheKey, $data, now()->addSeconds($duration));
        } catch (DecryptException $e) {
            report($e);

            throw $e;
        }
    }

    /**
     * Verify the nonce against the provided data.
     * 
     * @param \App\Models\File $file
     * @param string $encryptedNonce
     * @param string $ipAddress
     * @param string $userAgent
     * @param array $data
     * @return bool
     */
    public function verifyNonce(
        File $file,
        string $encryptedNonce,
        string $ipAddress,
        string $userAgent,
        array $data
    ): bool {
        try {
            $hashedNonce = decrypt($encryptedNonce);

            if (hash('sha256', $data['nonce']) !== $hashedNonce) {
                return false;
            }

            if ($data['ip_address'] !== $ipAddress) {
                return false;
            }

            if ($data['user_agent'] !== $userAgent) {
                return false;
            }

            if ($data['file_id'] !== $file->id) {
                return false;
            }

            return true;
        } catch (DecryptException $e) {
            report($e);

            return false;
        }
    }

    /**
     * Remove nonce from cache to prevent multiple access.
     * 
     * @param string $hashedNonce
     * @return void
     */
    public function removeNonce(string $hashedNonce): void
    {
        $cacheKey = "file_access_nonce:{$hashedNonce}";

        Cache::forget($cacheKey);
    }
}
