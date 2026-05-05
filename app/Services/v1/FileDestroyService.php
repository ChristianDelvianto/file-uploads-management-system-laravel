<?php

namespace App\Services\v1;

use App\Models\File;
use App\Models\User;
use App\Services\v1\FileCacheService;
use App\Services\v1\UserCacheService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FileDestroyService
{
    public function __construct(
        public FileCacheService $fileCacheService,
        public UserCacheService $userCacheService
    ) {
        // 
    }

    /**
     * Permanently delele a trashed file and return user's used_bytes
     * 
     * @param \App\Models\User $user
     * @param \App\Models\File $file
     * @return int user's used_bytes
     */
    public function permanentlyDeleteTrash(User $user, File $file): int
    {
        $fileCacheLock = $this->fileCacheService->getUpdateLock($file);
        $userCacheLock = $this->userCacheService->getUpdateLock($user);

        try {
            if (!$fileCacheLock->get()) {
                throw new Exception('File is being updated by another process. Please try again later.', 423);
            }

            if (!$userCacheLock->get()) {
                throw new Exception('User data is being updated by another process. Please try again later.', 423);
            }

            $usedBytes = DB::transaction(function () use ($file, $user): int {
                            $user = User::where('id', $user->id)->lockForUpdate()->first();

                            $file = File::withTrashed()->where('uuid', $file->uuid)->lockForUpdate()->first();

                            if (!$file) {
                                return $user->used_bytes;
                            }

                            if (!$file->trashed()) {
                                throw new Exception('File is restored, please check your files.', 400);
                            }

                            // $pruneDaysGap = config('filesystem.file_prune_days_gap');
                            // $fileDeletedTimestamp = Carbon::parse($file->deleted_at);

                            // if (Carbon::now()->diffInMicroseconds($fileDeletedTimestamp, true) > 0) {
                            //     return $user->used_bytes;
                            // }

                            // Get total new user's used_bytes
                            $newUsedBytes = $user->used_bytes - $file->bytes_size;

                            if ($newUsedBytes < 0) {
                                throw new Exception('Something went wrong, please try again.', 500);
                            }

                            $user->update(['used_bytes' => $newUsedBytes]);

                            $file->forceDelete();

                            return $newUsedBytes;
                        });

            Storage::disk($file->disk)->deleteDirectory("files/{$file->uuid}");

            return $usedBytes;
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $userCacheLock?->release();
            $fileCacheLock?->release();
        }
    }

    /**
     * Set file as trashed
     * 
     * @param \App\Models\User $user
     * @param \App\Models\file $file
     * @return void
     */
    public function putToTrash(User $user, File $file): void
    {
        $fileCacheLock = $this->fileCacheService->getUpdateLock($file);

        try {
            if (!$fileCacheLock->get()) {
                throw new Exception('File is being updated by another process. Please try again later.', 423);
            }

            DB::transaction(function () use ($file, $user) {
                $file = File::withTrashed()->where('uuid', $file->uuid)->lockForUpdate()->first();

                // File already deleted permanently
                if (!$file) {
                    throw new Exception('File not found.', 404);
                }

                // File already trashed
                if ($file->trashed()) {
                    return;
                }
                
                $file->delete();

                $user->activities()->create(['action' => 'trash', 'file_id' => $file->id]);
            });
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $fileCacheLock?->release();
        }
    }

    /**
     * Restore trashed file
     * 
     * @param \App\Models\User $user
     * @param \App\Models\file $file
     * @return void
     */
    public function restoreFromTrash(User $user, File $file): void
    {
        $fileCacheLock = $this->fileCacheService->getUpdateLock($file);

        try {
            if (!$fileCacheLock->get()) {
                throw new Exception('File is being updated by another process. Please try again later.', 423);
            }

            DB::transaction(function () use ($file, $user) {
                $file = File::withTrashed()->where('uuid', $file->uuid)->lockForUpdate()->first();

                 // File already deleted permanently
                if (!$file) {
                    throw new Exception('File not found.', 404);
                }
                
                // File already restored
                if (!$file->trashed()) {
                    return;
                }

                $user = User::where('id', $user->id)->lockForUpdate()->first();

                // @todo - not tested
                // If file deleted_at older than user's last_delete_all_at, it means the file permanently deleted by user's delete all action, so we should return not found
                $fileDeletedTimestamp = Carbon::parse($file->deleted_at);

                if (isset($user->last_delete_all_at) && Carbon::parse($user->last_delete_all_at)->diffInMicroseconds($fileDeletedTimestamp) > 0) {
                    throw new Exception('File not found.', 404);
                }
                
                $file->restore();

                $user->activities()->create(['action' => 'restore', 'file_id' => $file->id]);
            });
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $fileCacheLock?->release();
        }
    }
}
