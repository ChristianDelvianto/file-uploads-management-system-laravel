<?php

namespace App\Services\v1;

use App\Models\File;
use App\Models\PlanUser;
use App\Models\User;
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
            $category = 'image';
        } elseif (Str::startsWith($mimeType, 'video/')) {
            $category = 'video';
        } elseif (Str::startsWith($mimeType, 'audio/')) {
            $category = 'audio';
        } elseif (in_array($mimeType, $documentMimes)) {
            $category = 'document';
        } else {
            $category = 'other';
        }

        return $category;
    }

    /**
     * Soft delete file and update user used disk
     */
    public function softDeleteFile(File $file): void
    {
        $lock = Cache::lock("file:{$file->id}:update", 10);

        try {
            $lock->block(10);

            DB::transaction(function () use ($file) {
                $file->refresh();

                if (!$file->trashed()) {
                    $file->delete();
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
        $fileName = $uploadedFile->getClientOriginalName();
        $fileExtension = $uploadedFile->getClientOriginalExtension();
        $fileCategory = $this->getCategory($uploadedFile);
        $thumbnailPath = null; // Placeholder for thumbnail path

        $lock = Cache::lock("user:{$user->id}:update", 10);

        try {
            $lock->block(10);

            Storage::putFileAs($uuid, $uploadedFile, $uploadedFile->getClientOriginalName());

            if ($fileCategory === 'video') {
                $thumbnailPath = 'thumbnail_' . Str::random() . '.jpeg';

                Storage::putFileAs($uuid, $thumbnail, $thumbnailPath);
            }

            return DB::transaction(function () use ($user, $uploadedFile, $uuid, $fileCategory, $fileExtension, $mimeType, $fileName, $thumbnail, &$thumbnailPath) {
                $planUser = PlanUser::with(['plan'])->firstWhere('user_id', $user->id);

                $lockUser = User::where('id', $user->id)->lockForUpdate()->first();

                if ($lockUser->used_bytes + $uploadedFile->getSize() > $planUser->plan->limit_bytes) {
                    throw new Exception('The uploaded file exceeds your remaining storage limit.', 400);
                }

                $file = File::create([
                            'uuid' => $uuid,
                            'category' => $fileCategory,
                            'extension' => $fileExtension,
                            'mime_type' => $mimeType,
                            'name' => $fileName,
                            'size' => $uploadedFile->getSize(),
                            'thumbnail_path' => $thumbnailPath,
                            'storage_path' => $uploadedFile->getClientOriginalName(),
                            'disk' => config('filesystems.default'),
                            'user_id' => $user->id,
                        ]);

                $lockUser->increment('used_bytes', $uploadedFile->getSize());

                return $file;
            });
        } catch (Exception $e) {
            Storage::deleteDirectory($uuid);

            report($e);

            throw $e;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Update file table record
     */
    public function updateFileRecord(File $file, array $data): File
    {
        $lock = Cache::lock("file:{$file->id}:update", 10);

        try {
            $lock->block(10);

            return DB::transaction(function () use ($file, $data) {
                $file->refresh();

                if ($file->trashed()) {
                    throw new Exception('Cannot update a deleted file.', 400);
                }

                $file->fill($data);

                if ($file->isDirty()) {
                    $file->save();
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
