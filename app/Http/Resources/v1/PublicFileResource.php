<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\UserResource;
use App\Models\File;
use App\Services\v1\FileNonceService;
use App\Services\v1\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicFileResource extends JsonResource
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
        if ($this->status === 'completed' && !in_array($this->category, ['audio', 'video'])) {
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
            'is_scanned' => $this->is_scanned,
            'category' => $this->category,
            'extension' => $this->extension,
            'name' => $this->name,
            'duration' => $this->duration,
            'bytes_size' => $this->bytes_size,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'thumbnail_url' => $thumbnailURL,
            'storage_url' => $storageURL,
            'user' => new UserResource($this->whenLoaded('user'))
        ];
    }
}
