<?php

namespace BookneticApp\Providers\Core\Attributes\Validation;

interface ValidationRule
{
    /**
     * @param mixed $value The value to validate (null if not present)
     * @param string $propertyName The snake_case property name (for error messages)
     * @return string|null Error message on failure, null on success
     * TODO: add mixed type hint on $value when PHP 7.4 support is dropped
     */
    public function validate($value, string $propertyName): ?string;
}
