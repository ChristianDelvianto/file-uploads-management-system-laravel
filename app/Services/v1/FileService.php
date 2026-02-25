<?php

namespace App\Services\v1;

use App\Models\File;
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
            $lock->get();

            DB::transaction(function () use ($file) {
                $file->delete();
            });
        } catch (Exception $e) {
            throw new Exception('Internal server error.', 500);
        } finally {
            $lock?->release();
        }
    }

    /**
     * Store file and create new table record
     */
    public function storeFileAndCreateRecord(User $user, UploadedFile $uploadedFile, null|UploadedFile $thumbnail = null): File
    {
        $lock = Cache::lock("user:{$user->id}:update", 10);

        try {
            $lock->get();

            return DB::transaction(function () use ($thumbnail, $uploadedFile, $user) {
                $uuid = Str::uuid()->toString();
                $mimeType = $uploadedFile->getMimeType();
                $fileName = $uploadedFile->getClientOriginalName();
                $fileExtension = explode('/', $mimeType)[1];
                $filePath = "{$fileName}.{$fileExtension}";
                $fileCategory = $this->getCategory($uploadedFile);
                $thumbnailPath = null;

                Storage::disk('public')->putFileAs($uuid, $uploadedFile, $filePath);

                if ($fileCategory === 'video') {
                    $thumbnailPath = 'thumbnail_' . Str::random() . '.jpeg';

                    Storage::disk('public')->putFileAs($uuid, $thumbnail, $thumbnailPath);
                }

                $file = File::create([
                            'uuid' => $uuid,
                            'category' => $fileCategory,
                            'extension' => $fileExtension,
                            'mime_type' => $mimeType,
                            'name' => $fileName . '.' . $fileExtension,
                            'size' => $uploadedFile->getSize(),
                            'thumbnail_path' => $thumbnailPath,
                            'storage_path' => $filePath,
                            'disk' => config('filesystems.default'),
                            'user_id' => $user->id,
                        ]);

                $user->increment('used_disk', $uploadedFile->getSize());

                return $file;
            });
        } catch (Exception $e) {
            throw new Exception('Internal server error.', 500);
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
            $lock->get();

            return DB::transaction(function () use ($file, $data) {
                $file->fill($data);

                if ($file->isDirty()) {
                    $file->save();
                }

                return $file;
            });
        } catch (Exception $e) {
            throw new Exception('Internal server error.', 500);
        } finally {
            $lock?->release();
        }
    }
}
