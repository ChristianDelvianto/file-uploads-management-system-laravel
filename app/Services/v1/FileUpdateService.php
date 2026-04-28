<?php

namespace App\Services\v1;

use App\Models\File;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FileUpdateService
{
    /**
     * Update file name
     * 
     * @param \App\Models\User $user
     * @param \App\Models\File $file
     * @param string $name
     * @return \App\Models\File
     */
    public function updateName(User $user, File $file, string $name): File
    {
        $lock = Cache::lock("file:{$file->id}:update", config('cache.cache_lock_duration'));

        try {
            $lock->block(config('cache.cache_lock_timeout'));

            return DB::transaction(function () use ($file, $name, $user) {
                        $file = File::withTrashed()->where('id', $file->id)->lockForUpdate()->first();

                        if (!$file || $file->trashed()) {
                            throw new Exception('File not found.', 404);
                        }

                        $file->fill(['name' => $name]);

                        if ($file->isDirty()) {
                            $file->save();

                            $activity = $user->activities()->create(['action' => 'edit', 'file_id' => $file->id]);

                            $previousData = collect($file->getPrevious());

                            $activity->edit()->create([
                                'action' => 'update',
                                'field' => 'name',
                                'old_value' => $previousData->get('name'),
                                'new_value' => $name
                            ]);
                        }

                        return $file;
                    });
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Update file visibility
     * 
     * @param \App\Models\User $user
     * @param \App\Models\File $file
     * @return string $visibility
     * @return \App\Models\File $file
     */
    public function updateVisibility(User $user, File $file, string $visibility, ?array $emailsAdded = null, ?array $emailRemoved = null): File
    {
        $lock = Cache::lock("file:{$file->id}:update", config('cache.cache_lock_duration'));

        try {
            $lock->block(config('cache.cache_lock_timeout'));

            return DB::transaction(function () use ($file, $user, $visibility, $emailsAdded, $emailRemoved) {
                        $file = File::withTrashed()->where('id', $file->id)->lockForUpdate()->first();

                        // File deleted permanent or trashed
                        if (!$file || $file->trashed()) {
                            throw new Exception('File not found.', 404);
                        }

                        $file->fill(['visibility' => $visibility]);

                        if ($file->isDirty()) {
                            $file->save();

                            $activity = $user->activities()->create(['action' => 'edit', 'file_id' => $file->id]);

                            $previousData = collect($file->getPrevious());
                            
                            $activity->edit()->create([
                                'action' => 'update',
                                'field' => 'visibility',
                                'old_value' => $previousData->get('visibility'),
                                'new_value' => $visibility
                            ]);
                        }

                        return $file;
                    });
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $lock?->release();
        }
    }
}
