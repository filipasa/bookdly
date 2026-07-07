<?php

namespace BookneticApp\Providers\Core\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ArrayType implements ValidationRule
{
    public function __construct(
        public ?string $itemType = null,
        public int $minCount = 0,
        public int $maxCount = PHP_INT_MAX,
        public string $errorMessage = ''
    ) {
    }

    // TODO: add mixed type hint on $value when PHP 7.4 support is dropped
    public function validate($value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return $this->errorMessage ?: sprintf('%s must be an array', $propertyName);
        }

        $count = count($value);

        if ($count < $this->minCount) {
            return $this->errorMessage ?: sprintf('%s must have at least %d items', $propertyName, $this->minCount);
        }

        if ($count > $this->maxCount) {
            return $this->errorMessage ?: sprintf('%s must have at most %d items', $propertyName, $this->maxCount);
        }

        return null;
    }
}
