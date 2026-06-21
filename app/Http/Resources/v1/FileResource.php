<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\UserResource;
use App\Services\v1\NonceService;
use App\Services\v1\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $storageService = app(StorageService::class);
        $nonceService = app(NonceService::class);

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        $storageURL = null;
        $thumbnailURL = null;

        if (isset($this->thumbnail_name)) {
            $thumbnailURL = URL::route('api.v1.file.content.thumbnail', ['file' => $this->uuid]);
        }

        if ($this->status === 'completed') {
            $nonce = $nonceService->generateNonce($this->resource, $ipAddress, $userAgent);

            $storageURL = $storageService->generateAccessroute('api.v1.file.content.main', [
                            'file' => $this->uuid,
                            'nonce' => $nonce
                        ]);
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
            'shared' => $this->whenHas('shared', fn () => UserResource::collection($this->shared), []),
            'user' => new UserResource($this->whenLoaded('user'))
        ];
    }
}
