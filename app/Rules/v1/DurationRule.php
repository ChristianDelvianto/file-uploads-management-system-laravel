<?php

namespace App\Rules\v1;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class DurationRule implements ValidationRule
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
        $requiresDuration = str_starts_with($this->mimeType, 'audio/') || str_starts_with($this->mimeType, 'video/');

        if ($requiresDuration && empty($value)) {
            $fail("The {$attribute} field is required.");
        }
    }
}
