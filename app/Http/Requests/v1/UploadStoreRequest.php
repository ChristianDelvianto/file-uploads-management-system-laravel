<?php

namespace App\Http\Requests\v1;

use App\Services\v1\AuthService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\RequiredIf;

class UploadStoreRequest extends FormRequest
{
    public function __construct(
        public AuthService $authService
    ) {
        // 
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxChunkSizePerRequest = config('filesystems.max_chunk_size_per_request');

        $user = $this->user('sanctum');

        $maxChunkCount = ceil($this->input('bytes_size') / $maxChunkSizePerRequest);

        $userPlan = $this->authService->getUserPlan($user);

        // $remainingSpace = $userPlan->plan->limit_bytes - $user->used_bytes;

        return [
            'extension' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:,audio,document,image,other,video'],
            'mime_type' => ['required', 'string', 'max:100'],
            'chunk_count' => [
                'required',
                'integer',
                'min:1',
                'max:' . $maxChunkCount
            ],
            'bytes_size' => [
                'required',
                'integer',
                'min:1',
                'max:' . $userPlan->plan->limit_bytes
            ],
            'duration' => [
                'nullable', 
                'integer',
                'min:1',
                new RequiredIf(function () {
                    return $this->isAudio() || $this->isVideo();
                })
            ],
            'thumbnail' => [
                'nullable',
                'image',
                'max:2048', // 2 MB
                new RequiredIf(function () {
                    return $this->isVideo();
                })
            ]
        ];
    }

    /**
     * Determine if file is an audio or video based on mime type
     * 
     * @return bool
     */
    public function isAudio(): bool
    {
        $mimeType = $this->input('mime_type');

        return str_starts_with($mimeType, 'audio/');
    }

    /**
     * Determine if file is a video based on mime type
     * 
     * @return bool
     */
    public function isVideo(): bool
    {
        $mimeType = $this->input('mime_type');

        return str_starts_with($mimeType, 'video/');
    }
}
