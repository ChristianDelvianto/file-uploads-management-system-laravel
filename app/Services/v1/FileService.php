<?php

namespace App\Services\v1;

use App\Models\File;
use App\Models\PlanUser;
use App\Models\User;
use App\Models\UserLog;
use App\Models\UserLogEdit;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Get file category
     */
    public function getCategory(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        $documentMimes = config('filesystems.type.document');

        if (Str::startsWith($mimeType, 'image/')) {
            return 'image';
        }
        
        if (Str::startsWith($mimeType, 'video/')) {
            return 'video';
        }
        
        if (Str::startsWith($mimeType, 'audio/')) {
            return 'audio';
        }
        
        if (in_array($mimeType, $documentMimes)) {
            return 'document';
        }

        return 'other';
    }

    /**
     * Restore trashed file
     */
    public function restoreTrashedFile(User $user, File $file): void
    {
        $lock = Cache::lock("file:{$file->id}:update", config('cache.lock_duration'));

        try {
            $lock->block(config('cache.lock_timeout'));

            DB::transaction(function () use ($file, $user) {
                $file = File::withTrashed()->where('uuid', $file->uuid)->lockForUpdate()->first();

                 // File already deleted permanently by cron
                if (!$file) {
                    throw new Exception('File not found.', 404);
                }

                $user = User::where('id', $user->id)->lockForUpdate()->first();

                // @todo - not tested
                // If file deleted_at older than user's delete_all_at, it means the file permanently deleted by user's delete all action, so we should return not found
                if ($user->delete_all_at && Carbon::createFromTimestamp($user->delete_all_at)->diffInSeconds(Carbon::parse($file->deleted_at)) > 0) {
                    throw new Exception('File not found.', 404);
                }
                
                if ($file->trashed()) {
                    $file->restore();

                    UserLog::create([
                        'action' => 'restore',
                        'file_id' => $file->id,
                        'user_id' => $user->id,
                    ]);
                }
            });
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Soft delete file
     */
    public function softDeleteFile(File $file): void
    {
        $lock = Cache::lock("file:{$file->id}:update", config('cache.lock_duration'));

        try {
            $lock->block(config('cache.lock_timeout'));

            DB::transaction(function () use ($file) {
                $file = File::where('uuid', $file->uuid)->lockForUpdate()->first();

                if (!$file) { // File already deleted permanently by cron
                    throw new Exception('File not found.', 404);
                }
                
                if (!$file->trashed()) { // Check if file already trashed
                    $file->delete();

                    UserLog::create([
                        'action' => 'delete',
                        'file_id' => $file->id,
                        'user_id' => $file->user_id,
                    ]);
                }
            });
        } catch (Exception $e) {
            report($e);

            throw $e;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Store file and create new table record
     */
    public function storeFileAndCreateRecord(User $user, UploadedFile $uploadedFile, null|UploadedFile $thumbnail = null): File
    {
        $uuid = Str::uuid()->toString();
        $mimeType = $uploadedFile->getMimeType();
        $fileExtension = $uploadedFile->getClientOriginalExtension();
        $fileName = $uploadedFile->getClientOriginalName(); // Slug the file name
        $fileCategory = $this->getCategory($uploadedFile);
        $thumbnailName = null; // Placeholder for thumbnail name

        $lock = Cache::lock("user:{$user->id}:update", config('cache.lock_duration'));

        try {
            $lock->block(config('cache.lock_timeout'));

            $file = DB::transaction(function () use ($user, $uploadedFile, $uuid, $fileCategory, $fileExtension, $mimeType, $fileName, $thumbnail, &$thumbnailName) {
                // Get user's plan
                $planUser = PlanUser::with(['plan'])->firstWhere('user_id', $user->id);

                $user = User::where('id', $user->id)->lockForUpdate()->first();

                // Validate storage limit
                if (($user->used_bytes + $uploadedFile->getSize()) > $planUser->plan->limit_bytes) {
                    throw new Exception('The uploaded file exceeds your remaining storage limit.', 400);
                }

                $fileStorageName = Str::uuid()->toString() . '.' . $fileExtension;

                // If video, generate thumbnail name
                if ($fileCategory === 'video' && $thumbnail) {
                    $thumbnailName = 'thumbnail_' . Str::random() . '.jpg';
                }

                $file = File::create([
                            'uuid' => $uuid,
                            'visibility' => 'private',
                            'category' => $fileCategory,
                            'extension' => $fileExtension,
                            'mime_type' => $mimeType,
                            'name' => $fileName,
                            'bytes_size' => $uploadedFile->getSize(),
                            'thumbnail_name' => $thumbnailName,
                            'storage_name' => $fileStorageName,
                            'disk' => config('filesystems.default'),
                            'user_id' => $user->id,
                        ]);

                // We do not include thumbnail size in used_bytes calculation since it's generated by us and usually small in size.
                // If needed, we can include it by adding $thumbnail->getSize() to the calculation and updating used_bytes accordingly.
                $user->increment('used_bytes', $uploadedFile->getSize());

                UserLog::create([
                    'action' => 'upload',
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                ]);

                return $file;
            });

            // Store file in storage
            Storage::putFileAs($uuid, $uploadedFile, $file->storage_name);

            // If video, store thumbnail
            if ($fileCategory === 'video' && $thumbnail) {
                Storage::putFileAs($uuid, $thumbnail, $file->thumbnail_name);
            }

            return $file;
        } catch (Exception $e) {
            // If any exception occurs, we need to clean up the stored file to prevent orphan files in storage
            Storage::deleteDirectory($uuid);

            report($e);

            throw $e;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Update file name
     */
    public function updateFile(File $file, array $data, ?array $emailsAdded = null, ?array $emailRemoved = null): File
    {
        $lock = Cache::lock("file:{$file->id}:update", config('cache.lock_duration'));

        try {
            $lock->block(config('cache.lock_timeout'));

            return DB::transaction(function () use ($data, $file) {
                $file = File::where('uuid', $file->uuid)->lockForUpdate()->first();

                if ($file->trashed()) {
                    throw new Exception('File not found.', 404);
                }

                $file->fill($data);

                if ($file->isDirty()) {
                    $file->save();

                    $log = UserLog::create([
                        'action' => 'edit',
                        'file_id' => $file->id,
                        'user_id' => $file->user_id,
                    ]);

                    $previousData = collect($file->getPrevious());

                    if ($previousData->has('name')) {
                        UserLogEdit::create([
                            'action' => 'update',
                            'field' => 'name',
                            'old_value' => $previousData->get('name'),
                            'new_value' => $data['name'],
                            'user_log_id' => $log->id,
                        ]);
                    }

                    if ($previousData->has('visibility')) {
                        UserLogEdit::create([
                            'action' => 'update',
                            'field' => 'visibility',
                            'old_value' => $previousData->get('visibility'),
                            'new_value' => $data['visibility'],
                            'user_log_id' => $log->id,
                        ]);
                    }
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
     */
    public function updateFileVisibility(File $file, string $visibility, ?array $emailsAdded = null, ?array $emailRemoved = null): File
    {
        $lock = Cache::lock("file:{$file->id}:update", config('cache.lock_duration'));

        try {
            $lock->block(config('cache.lock_timeout'));

            return DB::transaction(function () use ($file, $visibility, $emails) {
                $file = File::where('uuid', $file->uuid)->lockForUpdate()->first();

                if ($file->trashed()) {
                    throw new Exception('File not found.', 404);
                }

                $file->fill(['visibility' => $visibility]);

                if ($file->isDirty('visibility')) {
                    $file->save();

                    $log = UserLog::create([
                        'action' => 'edit',
                        'file_id' => $file->id,
                        'user_id' => $file->user_id,
                    ]);

                    UserLogEdit::create([
                        'action' => 'update',
                        'field' => 'visibility',
                        'old_value' => $file->getPrevious()['visibility'],
                        'new_value' => $visibility,
                        'user_log_id' => $log->id,
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
