<?php

namespace BookneticApp\Providers\Core\Exceptions;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    /**
     * @param array<string, string> $errors Map of field_name => error_message
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed', 422);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
