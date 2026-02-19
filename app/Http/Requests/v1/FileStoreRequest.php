<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'user';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
    
        $plan = $user->subscription;
        
        $remainingSpace = $plan - $user->used_disk;

        return [
            'file' => [
                'required',
                'file',
                'max:51200',
                function ($attribute, $value, $fail) use ($remainingSpace) {
                    $fileSizeBytes = $value->getSize();

                    $remainingBytes = $remainingSpace * 1024;

                    if ($fileSizeBytes > $remainingBytes) {
                        $fail('The uploaded file exceeds your remaining storage limit.');
                    }
                },
            ],
            'thumbnail' => [
                Rule::when($this->isVideoFile(), ['required'], ['nullable']),
                'image', 
                'max:5120',
            ],
        ];
    }

    /**
     * Check if file is a video type
     * 
     * @return bool
     */
    protected function isVideoFile(): bool
    {
        if (!$this->hasFile('file')) {
            return false;
        }
        
        $mimeType = $this->file('file')->getMimeType();

        return str_starts_with($mimeType, 'video/');
    }
}
