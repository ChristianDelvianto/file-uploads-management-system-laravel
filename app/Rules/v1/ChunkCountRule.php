<?php

namespace App\Rules\v1;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ChunkCountRule implements ValidationRule
{
    public function __construct(
        protected int $bytesSize,
        protected int $maxChunkSize
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
        $expectedChunkCount = max(1, (int) ceil($this->bytesSize / $this->maxChunkSize));

        if ((int) $value !== $expectedChunkCount) {
            $fail("The {$attribute} must equal {$expectedChunkCount}.");
        }
    }
}
