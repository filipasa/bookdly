<?php

namespace BookneticApp\Providers\Core\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Range implements ValidationRule
{
    /**
     * @param int|float|null $min TODO: add int|float|null union type hint when PHP 7.4 support is dropped
     * @param int|float|null $max TODO: add int|float|null union type hint when PHP 7.4 support is dropped
     */
    public function __construct(
        public $min = null,
        public $max = null,
        public string $errorMessage = ''
    ) {
    }

    // TODO: add mixed type hint on $value when PHP 7.4 support is dropped
    public function validate($value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return $this->errorMessage ?: sprintf('%s must be a number', $propertyName);
        }

        $numValue = $value + 0;

        if ($this->min !== null && $numValue < $this->min) {
            return $this->errorMessage ?: sprintf('%s must be at least %s', $propertyName, $this->min);
        }

        if ($this->max !== null && $numValue > $this->max) {
            return $this->errorMessage ?: sprintf('%s must be at most %s', $propertyName, $this->max);
        }

        return null;
    }
}
