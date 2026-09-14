<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\UserResource;
use App\Services\v1\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class UserFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tokenService = app(TokenService::class);

        $storageURL = null;
        $thumbnailURL = null;

        if (is_string($this->thumbnail_name)) {
            $thumbnailURL = URL::route('api.v1.file.content.thumbnail', ['file' => $this->uuid]);
        }

        /**
         * For audios, documents and videos, we DO NOT generate a token here. The token will be generated ONLY when the user open the file viewer pop up / modal.
         * 
         * For images, we generate the token immediately right here because the frontend dashboard
         * needs to render the image element src attribute instantly.
         */
        if ($this->category === 'image') {
            $token = $tokenService->generateToken($this->resource, $request->ip(), $request->userAgent());

            $storageURL = $tokenService->generateAccessRoute('api.v1.file.content.show', [
                'file' => $this->uuid,
                'token' => $token
            ]);
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
            'deleted_at' => $this->when($this->trashed(), $this->deleted_at),
            'storage_url' => $storageURL,
            'thumbnail_url' => $thumbnailURL,
            'shared' => $this->whenLoaded('shared', UserResource::collection($this->shared), []),
            'user' => $this->whenLoaded('user', UserResource::make($this->user))
        ];
    }
}
