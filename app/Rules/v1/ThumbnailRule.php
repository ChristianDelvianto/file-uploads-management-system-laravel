<?php

namespace App\Rules\v1;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ThumbnailRule implements ValidationRule
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
        if (str_starts_with($this->mimeType, 'video/') && empty($value)) {
            $fail("The {$attribute} field is required.");
        }
    }
}
