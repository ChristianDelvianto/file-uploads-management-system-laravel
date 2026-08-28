<?php

namespace App\Http\Requests\v1;

use App\Rules\v1\DurationRule;
use App\Rules\v1\MimeCategoryRule;
use App\Rules\v1\ThumbnailRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadChunkableRequest extends FormRequest
{
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxChunkSize = config('filesystems.max_chunk_size_per_request');

        $bytesSize = (int) $this->input('bytes_size', 0);
        $expectedChunkCount = max(1, (int) ceil($bytesSize / $maxChunkSize));

        return [
            'extension' => [
                'required',
                'string',
                'max:40'
            ],
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'category' => [
                'required',
                new MimeCategoryRule($this->input('mime_type'))
            ],
            'mime_type' => [
                'required',
                'string',
                'max:100'
            ],
            'bytes_size' => [
                'required',
                'integer',
                'min:1'
            ],
            'chunk_count' => [
                'required',
                'integer',
                Rule::in([$expectedChunkCount])
            ],
            'duration' => [
                'nullable',
                'integer',
                'min:1',
                new DurationRule($this->input('mime_type'))
            ],
            'thumbnail' => [
                'nullable',
                'image',
                'max:1024',
                new ThumbnailRule($this->input('mime_type'))
            ],
        ];
    }
}
