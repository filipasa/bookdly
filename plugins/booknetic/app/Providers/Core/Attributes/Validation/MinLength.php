<?php

namespace BookneticApp\Providers\Core\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class MinLength implements ValidationRule
{
    public function __construct(
        public int $min,
        public string $errorMessage = ''
    ) {
    }

    // TODO: add mixed type hint on $value when PHP 7.4 support is dropped
    public function validate($value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || mb_strlen($value) < $this->min) {
            return $this->errorMessage ?: sprintf('%s must be at least %d characters', $propertyName, $this->min);
        }

        return null;
    }
}
