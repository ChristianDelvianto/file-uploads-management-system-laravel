<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class FileUpdateVisibilityRequest extends FormRequest
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
        $user = $this->user('sanctum');

        return [
            'visibility' => [
                'required',
                'string',
                'in:private,public,shared'
            ],
            'emails' => [
                'required_if:visibility,=,shared',
                'array',
                'max:10',
                'min:1'
            ],
            'emails.*' => [
                'string',
                'email',
                "not_in:{$user->email}"
            ]
            // 'emails.*' => [
            //     'string',
            //     'email',
            //     'exists:users,email',
            //     "not_in:{$user->email}"
            // ]
        ];
    }
}
