<?php

namespace App\Http\Requests\v1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadChunksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('upload')->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxChunkSize = config('filesystems.max_chunk_size_per_request');

        $maxChunkIndex = $this->route('upload')->chunk_count - 1;

        return [
            'chunk' => [
                'required',
                'file',
                "max:{$maxChunkSize}",
                "min:1"
            ],
            'index' => [
                'required',
                'integer',
                'min:0',
                "max:{$maxChunkIndex}"
            ]
        ];
    }
}
