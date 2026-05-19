<?php

namespace App\Http\Resources\v1;

use App\Models\File;
use App\Services\v1\FileNonceService;
use App\Services\v1\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFileResource extends JsonResource
{
    private FileStorageService $fileStorageService;
    private FileNonceService $fileNonceService;

    public function __construct(
        File $resource
    ) {
        parent::__construct($resource);

        $this->fileStorageService = app(FileStorageService::class);
        $this->fileNonceService = app(FileNonceService::class);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        $thumbnailURL = null;
        $storageURL = null;

        if (isset($this->thumbnail_name)) {
            $nonce = $this->fileNonceService->createNonce($this->resource, $ipAddress, $userAgent);

            $thumbnailURL = $this->fileStorageService->createAccessSignedURL(
                                'api.v1.files.content.thumbnail',
                                [
                                    'file' => $this->uuid,
                                    'nonce' => $nonce
                                ]
                            );
        }

        // For audio and video, user need to request a stream link first
        // When user in trashed page, they can see the file but cannot access to the file content, so no storage URL provided
        if (!$this->trashed() && ($this->status === 'completed' && !in_array($this->category, ['audio', 'video']))) {
            $nonce = $this->fileNonceService->createNonce($this->resource, $ipAddress, $userAgent);

            $storageURL = $this->fileStorageService->createAccessSignedURL(
                            'api.v1.files.content.main',
                            [
                                'file' => $this->uuid,
                                'nonce' => $nonce
                            ]
                        );
        }

        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'category' => $this->category,
            'extension' => $this->extension,
            'name' => $this->name,
            'duration' => $this->duration,
            'bytes_size' => $this->bytes_size,
            'created_at' => $this->created_at,
            'thumbnail_url' => $thumbnailURL,
            'storage_url' => $storageURL,
            'last_action' => $this->when(!$this->trashed(), function () {
                return $this->last_action;
            }),
            'last_action_at' => $this->when(!$this->trashed(), function () {
                return $this->last_action_at;
            }),
        ];
    }
}
