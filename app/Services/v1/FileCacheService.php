<?php

namespace App\Services\v1;

use App\Models\File;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

class FileCacheService
{
    /**
     * Get file's update lock.
     * 
     * @param \App\Models\File $file
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function getUpdateLock(File $file): Lock
    {
        $duration = config('cache.cache_lock_duration');

        $cacheKey = "file:{$file->id}:update";

        return Cache::lock($cacheKey, $duration);
    }

    /**
     * Wait for the lock to be released and acquire it.
     * If the lock cannot be acquired within the timeout duration, it will throw an exception.
     * 
     * @param \Illuminate\Contracts\Cache\Lock $lock
     * @param ?int $timeoutDuration
     * @return mixed
     */
    public function waitForLock(Lock $lock, ?int $timeoutDuration = 60): mixed
    {
        $defaultDuration = config('cache.cache_lock_timeout');

        return $lock->block($timeoutDuration ?? $defaultDuration);
    }
}
