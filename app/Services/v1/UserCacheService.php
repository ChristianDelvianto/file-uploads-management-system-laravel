<?php

namespace App\Services\v1;

use App\Models\User;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

class UserCacheService
{
    /**
     * [Description here]
     * 
     * @param \App\Models\User $user
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function getUpdateLock(User $user): Lock
    {
        $duration = config('cache.cache_lock_duration');

        $cacheKey = "user:{$user->id}:update";

        return Cache::lock($cacheKey, $duration);
    }

    /**
     * [Description here]
     * 
     * @param \Illuminate\Contracts\Cache\Lock $lock
     * @return mixed
     */
    public function waitForLock(Lock $lock): mixed
    {
        $duration = config('cache.cache_lock_timeout');

        return $lock->block($duration);
    }
}
