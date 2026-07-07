<?php

namespace BookneticApp\Providers\Core\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class In implements ValidationRule
{
    public function __construct(
        public array $values = [],
        public string $errorMessage = ''
    ) {
    }

    // TODO: add mixed type hint on $value when PHP 7.4 support is dropped
    public function validate($value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!in_array($value, $this->values, true)) {
            $allowed = implode(', ', $this->values);

            return $this->errorMessage ?: sprintf('%s must be one of: %s', $propertyName, $allowed);
        }

        return null;
    }
}
