<?php

namespace BookneticApp\Providers\Core\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Regex implements ValidationRule
{
    public function __construct(
        public string $pattern = '',
        public string $errorMessage = ''
    ) {
    }

    // TODO: add mixed type hint on $value when PHP 7.4 support is dropped
    public function validate($value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || !preg_match($this->pattern, $value)) {
            return $this->errorMessage ?: sprintf('%s format is invalid', $propertyName);
        }

        return null;
    }
}
