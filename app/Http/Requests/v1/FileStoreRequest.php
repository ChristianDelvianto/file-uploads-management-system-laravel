<?php

namespace App\Http\Requests\v1;

use App\Models\PlanUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileStoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxFileSize = config('filesystems.max_file_size');

        $user = $this->user();
    
        $planUser = PlanUser::with(['plan'])->firstWhere('user_id', $user->id);
        
        $remainingSpace = $planUser->plan->limit_bytes - $user->used_bytes;

        return [
            'file' => [
                'required',
                'file',
                'max:' . $maxFileSize,
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
