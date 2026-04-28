<?php

namespace App\Services\v1;

use App\Models\File;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FileDestroyService
{
    /**
     * Permanently delele a trashed file and return user's used_bytes
     * 
     * @param \App\Models\User $user
     * @param \App\Models\File $file
     * @return int user's used_bytes
     */
    public function permanentlyDeleteTrashed(User $user, File $file): int
    {
        $fileLock = Cache::lock();
        $userLock = Cache::lock("user:{$user->id}:update", config('cache.cache_lock_duration'));

        try {
            if (!$fileLock->get()) {
                throw new Exception('File is currently busy, please try again.', 429);
            }

            $userLock->block(config('cache.cache_lock_timeout'));

            $newUsedBytes = DB::transaction(function () use ($file, $user) {
                                $user = User::where('id', $user->id)->lockForUpdate()->first();

                                $file = File::withTrashed()->where('id', $file->id)->lockForUpdate()->first();

                                if (!$file) {
                                    return $user->used_bytes;
                                }

                                if (!$file->trashed()) {
                                    throw new Exception('File is restored, please check your files.', 400);
                                }

                                $pruneDaysGap = config('filesystem.file_prune_days_gap');
                                $fileDeletedTimestamp = Carbon::parse($file->deleted_at);

                                // File already permanently deleted
                                if (Carbon::now()->diffInMicroseconds($fileDeletedTimestamp, true) > 0) {
                                    return $user->used_bytes;
                                }

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

            return $newUsedBytes;
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $userLock?->release();
            $fileLock?->release();
        }
    }

    /**
     * Restore trashed file
     * 
     * @param \App\Models\User $user
     * @param \App\Models\file $file
     * @return void
     */
    public function restoreTrashed(User $user, File $file): void
    {
        $lock = Cache::lock("file:{$file->id}:update", config('cache.cache_lock_duration'));

        try {
            $lock->block(config('cache.cache_lock_timeout'));

            DB::transaction(function () use ($file, $user) {
                $file = File::withTrashed()->where('id', $file->id)->lockForUpdate()->first();

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

                if (isset($user->last_delete_all_at) && Carbon::parse($user->last_delete_all_at)->diffInSeconds($fileDeletedTimestamp) > 0) {
                    throw new Exception('File not found.', 404);
                }
                
                $file->restore();

                $user->activities()->create(['action' => 'restore', 'file_id' => $file->id]);
            });
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Set file as trashed
     * 
     * @param \App\Models\User $user
     * @param \App\Models\file $file
     * @return void
     */
    public function setAsTrashed(User $user, File $file): void
    {
        $lock = Cache::lock("file:{$file->id}:update", config('cache.cache_lock_duration'));

        try {
            $lock->block(config('cache.cache_lock_timeout'));

            DB::transaction(function () use ($file, $user) {
                $file = File::withTrashed()->where('id', $file->id)->lockForUpdate()->first();

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
            $lock?->release();
        }
    }
}
