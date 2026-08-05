<?php

namespace App\Services\v1;

use App\Exceptions\v1\QuotaMismatchException;
use App\Jobs\UserDeleteAllTrashed;
use App\Models\File;
use App\Models\User;
use App\Models\UserQuota;
use App\Services\v1\UserCacheService;
use Exception;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserFileService
{
    public function __construct(
        protected UserCacheService $userCacheService
    ) {
        // 
    }

    /**
     * Permanently delete all trashed files.
     * 
     * @param \App\Models\User $user
     * @param \Illuminate\Support\Carbon $timestamp
     * @return int user's `used_bytes`
     */
    public function deleteTrashed(User $user, Carbon $timestamp): int
    {
        $userLock = $this->userCacheService->getUpdateLock($user);

        try {
            if (!$userLock->get()) {
                throw new Exception('Another process is currently running, please try again.', 423);
            }

            return DB::transaction(function () use ($timestamp, $user) {
                        $userQuota = UserQuota::where('user_id', $user->id)->lockForUpdate()->first();

                        // User's clear_trash_at bigger than current timestamp request
                        if ($userQuota->clear_trash_at && Carbon::parse($timestamp)->diffInMicroseconds($userQuota->clear_trash_at) > 0) {
                            return $userQuota->used_bytes;
                        }

                        $trashUsedBytes = File::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '<=', $timestamp)->sum('bytes_size');

                        if ($trashUsedBytes < 1) {
                            // Just update user's clear_trash_at
                            $userQuota->update(['clear_trash_at' => $timestamp]);

                            return $userQuota->used_bytes;
                        }

                        $newUsedBytes = $userQuota->used_bytes - $trashUsedBytes;

                        if ($newUsedBytes < 0) {
                            throw new QuotaMismatchException('Internal server error.', 500);
                        }

                        // We use update() here
                        $userQuota->update(['used_bytes' => $newUsedBytes, 'clear_trash_at' => $timestamp]);

                        UserDeleteAllTrashed::dispatch($user->id, $timestamp)
                                            ->onQueue('high')
                                            ->afterCommit();

                        return $newUsedBytes;
                    });
        } catch (QuotaMismatchException $e) {
            report($e);

            // We could dispatch a queue to re-count user's used_bytes

            throw $e;
        } catch (Exception $e) {
            report ($e);

            throw $e;
        } finally {
            $userLock?->release();
        }
    }

    /**
     * Get user's files.
     * 
     * @param \App\Models\User $user
     * @param string $category
     * @param bool $fromOldest
     * @param ?string $cursor
     * @return \Illuminate\Contracts\Pagination\CursorPaginator
     */
    public function getFiles(User $user, string $category = 'all', bool $fromOldest = false, ?string $cursor = null): CursorPaginator
    {
        return File::query()
                ->with([
                    'shared:id,photo,name,email'
                ])
                ->where('user_id', $user->id)
                ->when(
                    $category !== 'all',
                    fn ($query) => $query->where('category', $category)
                )
                ->when(
                    $fromOldest,
                    fn ($query) => $query->oldest('created_at'),
                    fn ($query) => $query->latest('created_at')
                )
                ->when(
                    $fromOldest,
                    function ($query) use ($cursor) {
                        if ($cursor) {
                            $query->where('created_at', '>', $cursor);
                        }
                    },
                    function ($query) use ($cursor) {
                        if ($cursor) {
                            $query->where('created_at', '<', $cursor);
                        }
                    }
                )
                ->cursorPaginate(20);
    }

    /**
     * Get files that shared to user.
     * 
     * @param \App\Models\User $user
     * @param bool $fromOldest
     * @param ?string $cursor
     * @return \Illuminate\Contracts\Pagination\CursorPaginator
     */
    public function getSharedWithUser(User $user, bool $fromOldest = false, ?string $cursor = null): CursorPaginator
    {
        return File::query()
                ->with([
                    'shared:id,photo,name',
                    'user:id,photo,name'
                ])
                ->select(
                    'files.*',
                    'file_shared.created_at AS shared_at'
                )
                ->join('file_shared', 'files.id', '=', 'file_shared.file_id')
                ->where('file_shared.user_id', '=', $user->id)
                ->when(
                    $fromOldest,
                    fn ($query) => $query->oldest('file_shared.created_at'),
                    fn ($query) => $query->latest('file_shared.created_at')
                )
                ->when(
                    $fromOldest,
                    function ($query) use ($cursor) {
                        if ($cursor) {
                            $query->where('file_shared.created_at', '>', $cursor);
                        }
                    },
                    function ($query) use ($cursor) {
                        if ($cursor) {
                            $query->where('file_shared.created_at', '<', $cursor);
                        }
                    }
                )
                ->cursorPaginate(20);
    }

    /**
     * Get user's trashed files. (Soft deleted files)
     * 
     * @param \App\Models\User $user
     * @param bool $fromOldest
     * @param ?string $cursor
     * @return \Illuminate\Contracts\Pagination\CursorPaginator
     */
    public function getTrashedFiles(User $user, bool $fromOldest = false, ?string $cursor = null): CursorPaginator
    {
        $userQuota = UserQuota::firstWhere('user_id', $user->id);

        return File::query()
                ->onlyTrashed()
                ->where('user_id', $user->id)
                ->when(
                    $fromOldest,
                    fn ($query) => $query->oldest('deleted_at'),
                    fn ($query) => $query->latest('deleted_at')
                )
                ->when(
                    $fromOldest,
                    function ($query) use ($cursor, $userQuota) {
                        if ($cursor) {
                            $query->where('deleted_at', '>', $cursor);
                        } else if (isset($userQuota->clear_trash_at)) {
                            $query->where('deleted_at', '>', $userQuota->clear_trash_at);
                        }
                    },
                    function ($query) use ($cursor, $userQuota) {
                        if ($cursor) {
                            $query->where('deleted_at', '<', $cursor);
                        } else if (isset($userQuota->clear_trash_at)) {
                            $query->where('deleted_at', '>', $userQuota->clear_trash_at);
                        }
                    }
                )
                ->cursorPaginate(20);

                // ->when(
                //     $timestamp,
                //     function ($query) use ($fromOldest, $timestamp, $userQuota) {
                //         $timestamp = Carbon::createFromTimestampMs($timestamp);

                //         if ($fromOldest && isset($userQuota->clear_trash_at)) {
                //             $query->whereBetween('deleted_at', [$timestamp, $userQuota->clear_trash_at]);
                //         } else if ($fromOldest) {
                //             $query->where('deleted_at', '>', $timestamp);
                //         } else {
                //             $query->where('deleted_at', '<', $timestamp);
                //         }
                //     },
                //     function ($query) use ($userQuota) {
                //         if (isset($userQuota->clear_trash_at)) {
                //             $query->where('deleted_at', '>', $userQuota->clear_trash_at);
                //         }
                //     }
                // )
                // ->limit(20)
                // ->get();
    }
}
