<?php

namespace BookneticApp\Providers\Core\Dto;

use BookneticApp\Providers\Core\Attributes\Validation\ArrayType;
use BookneticApp\Providers\Core\Attributes\Validation\ValidationRule;
use BookneticApp\Providers\Core\Exceptions\ValidationException;
use ReflectionClass;
use ReflectionProperty;

class DtoValidator
{
    /**
     * Validate a hydrated DTO instance using its property attributes.
     *
     * @throws ValidationException if any validation fails
     */
    public static function validate(object $dto): void
    {
        $reflection = new ReflectionClass($dto);
        $errors = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $camelName = $property->getName();
            $snakeName = DtoHydrator::camelToSnake($camelName);

            $value = $property->isInitialized($dto) ? $property->getValue($dto) : null;

            if (method_exists($property, 'getAttributes')) {
                foreach ($property->getAttributes() as $attribute) {
                    $attrInstance = $attribute->newInstance();

                    if (!$attrInstance instanceof ValidationRule) {
                        continue;
                    }

                    $error = $attrInstance->validate($value, $snakeName);

                    if ($error !== null) {
                        $errors[$snakeName] = $error;
                        break;
                    }
                }
            }

            if ($value !== null && is_object($value) && !$value instanceof \JsonSerializable) {
                try {
                    self::validate($value);
                } catch (ValidationException $e) {
                    foreach ($e->getErrors() as $nestedField => $nestedError) {
                        $errors[$snakeName . '.' . $nestedField] = $nestedError;
                    }
                }
            }

            if ($value !== null && is_array($value) && method_exists($property, 'getAttributes')) {
                $arrayTypeAttrs = $property->getAttributes(ArrayType::class);

                if (!empty($arrayTypeAttrs)) {
                    $arrayType = $arrayTypeAttrs[0]->newInstance();

                    if ($arrayType->itemType !== null && class_exists($arrayType->itemType)) {
                        foreach ($value as $index => $item) {
                            if (is_object($item)) {
                                try {
                                    self::validate($item);
                                } catch (ValidationException $e) {
                                    foreach ($e->getErrors() as $nestedField => $nestedError) {
                                        $errors[$snakeName . '.' . $index . '.' . $nestedField] = $nestedError;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Validate a DTO using cached metadata (no reflection on attributes).
     *
     * @throws ValidationException
     */
    public static function validateFromCache(object $dto, array $propertyMeta): void
    {
        $errors = [];
        $reflection = new ReflectionClass($dto);

        foreach ($propertyMeta as $camelName => $meta) {
            $snakeName = $meta['mapFrom'];
            $property = $reflection->getProperty($camelName);
            $value = $property->isInitialized($dto) ? $property->getValue($dto) : null;

            foreach ($meta['rules'] as $rule) {
                $error = self::runCachedRule($rule, $value, $snakeName);

                if ($error !== null) {
                    $errors[$snakeName] = $error;
                    break;
                }
            }

            if ($value !== null && is_object($value)) {
                try {
                    self::validate($value);
                } catch (ValidationException $e) {
                    foreach ($e->getErrors() as $nestedField => $nestedError) {
                        $errors[$snakeName . '.' . $nestedField] = $nestedError;
                    }
                }
            }

            if ($value !== null && is_array($value) && !empty($meta['arrayItemType'])) {
                foreach ($value as $index => $item) {
                    if (is_object($item)) {
                        try {
                            self::validate($item);
                        } catch (ValidationException $e) {
                            foreach ($e->getErrors() as $nestedField => $nestedError) {
                                $errors[$snakeName . '.' . $index . '.' . $nestedField] = $nestedError;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param mixed $value
     */
    private static function runCachedRule(array $rule, $value, string $propertyName): ?string
    {
        $type = $rule['type'];
        $msg = $rule['errorMessage'] ?? '';

        switch ($type) {
            case 'Required':
                return $value === null ? ($msg ?: sprintf('%s is required', $propertyName)) : null;
            case 'MinLength':
                return self::validateCachedMinLength($value, $rule, $propertyName);
            case 'MaxLength':
                return self::validateCachedMaxLength($value, $rule, $propertyName);
            case 'Range':
                return self::validateCachedRange($value, $rule, $propertyName);
            case 'Email':
                return ($value !== null && (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false))
                    ? ($msg ?: sprintf('%s must be a valid email address', $propertyName))
                    : null;
            case 'In':
                return ($value !== null && !in_array($value, $rule['values'] ?? [], true))
                    ? ($msg ?: sprintf('%s must be one of: %s', $propertyName, implode(', ', $rule['values'] ?? [])))
                    : null;
            case 'Regex':
                return ($value !== null && (!is_string($value) || !preg_match($rule['pattern'] ?? '//', $value)))
                    ? ($msg ?: sprintf('%s format is invalid', $propertyName))
                    : null;
            case 'ArrayType':
                return self::validateCachedArrayType($value, $rule, $propertyName);
            default:
                return null;
        }
    }

    /**
     * @param mixed $value
     */
    private static function validateCachedMinLength($value, array $rule, string $name): ?string
    {
        if ($value === null) {
            return null;
        }

        $msg = $rule['errorMessage'] ?? '';
        $min = $rule['min'] ?? 0;

        if (!is_string($value) || mb_strlen($value) < $min) {
            return $msg ?: sprintf('%s must be at least %d characters', $name, $min);
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private static function validateCachedMaxLength($value, array $rule, string $name): ?string
    {
        if ($value === null) {
            return null;
        }

        $msg = $rule['errorMessage'] ?? '';
        $max = $rule['max'] ?? PHP_INT_MAX;

        if (!is_string($value) || mb_strlen($value) > $max) {
            return $msg ?: sprintf('%s must be at most %d characters', $name, $max);
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private static function validateCachedRange($value, array $rule, string $name): ?string
    {
        if ($value === null) {
            return null;
        }

        $msg = $rule['errorMessage'] ?? '';

        if (!is_numeric($value)) {
            return $msg ?: sprintf('%s must be a number', $name);
        }

        $numValue = $value + 0;

        if (isset($rule['min']) && $numValue < $rule['min']) {
            return $msg ?: sprintf('%s must be at least %s', $name, $rule['min']);
        }

        if (isset($rule['max']) && $numValue > $rule['max']) {
            return $msg ?: sprintf('%s must be at most %s', $name, $rule['max']);
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private static function validateCachedArrayType($value, array $rule, string $name): ?string
    {
        if ($value === null) {
            return null;
        }

        $msg = $rule['errorMessage'] ?? '';

        if (!is_array($value)) {
            return $msg ?: sprintf('%s must be an array', $name);
        }

        $count = count($value);
        $min = $rule['minCount'] ?? 0;
        $max = $rule['maxCount'] ?? PHP_INT_MAX;

        if ($count < $min) {
            return $msg ?: sprintf('%s must have at least %d items', $name, $min);
        }

        if ($count > $max) {
            return $msg ?: sprintf('%s must have at most %d items', $name, $max);
        }

        return null;
    }
}
