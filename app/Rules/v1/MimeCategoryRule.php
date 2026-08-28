<?php

namespace App\Rules\v1;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MimeCategoryRule implements ValidationRule
{
    public function __construct(
        protected string $mimeType
    ) {
        // 
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $expected = match (true) {
            str_starts_with($this->mimeType, 'audio/') => 'audio',
            str_starts_with($this->mimeType, 'image/') => 'image',
            str_starts_with($this->mimeType, 'video/') => 'video',
            default => 'document'
        };

        if ($expected !== $value) {
            $fail("The {$attribute} must be '{$expected}' for MIME type '{$this->mimeType}'.");
        }
    }

}
