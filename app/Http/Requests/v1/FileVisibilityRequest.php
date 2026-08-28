<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class FileVisibilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('file')->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'visibility' => [
                'required',
                'string',
                'in:private,public,shared'
            ],
            'emails' => [
                'bail',
                'nullable',
                'array',
                'max:10',
                Rule::when($this->input('visibility') === 'shared', ['min:1']),
                Rule::requiredIf(fn () => $this->input('visibility') === 'shared')
            ],
            'emails.*' => [
                'string',
                'email',
                "not_in:{$user->email}",
                'exists:users,email'
            ]
        ];
    }
}
