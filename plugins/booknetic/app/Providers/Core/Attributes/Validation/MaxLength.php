<?php

namespace BookneticApp\Providers\Core\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class MaxLength implements ValidationRule
{
    public function __construct(
        public int $max,
        public string $errorMessage = ''
    ) {
    }

    // TODO: add mixed type hint on $value when PHP 7.4 support is dropped
    public function validate($value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || mb_strlen($value) > $this->max) {
            return $this->errorMessage ?: sprintf('%s must be at most %d characters', $propertyName, $this->max);
        }

        return null;
    }
}
