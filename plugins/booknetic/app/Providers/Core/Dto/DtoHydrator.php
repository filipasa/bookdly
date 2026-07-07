<?php

namespace BookneticApp\Providers\Core\Dto;

use BookneticApp\Providers\Core\Attributes\Validation\ArrayType;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class DtoHydrator
{
    /**
     * Hydrate a DTO from request data.
     *
     * @param string $dtoClass Fully qualified class name
     * @param array $data Associative array (snake_case keys from request)
     * @return object Hydrated DTO instance
     */
    public static function hydrate(string $dtoClass, array $data): object
    {
        $reflection = new ReflectionClass($dtoClass);
        $instance = $reflection->newInstanceWithoutConstructor();
        $defaults = $reflection->getDefaultProperties();

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $camelName = $property->getName();
            $snakeName = self::camelToSnake($camelName);

            $hasValue = array_key_exists($snakeName, $data);
            $rawValue = $hasValue ? $data[$snakeName] : null;

            if (!$hasValue && array_key_exists($camelName, $defaults)) {
                continue;
            }

            if (!$hasValue) {
                continue;
            }

            $value = self::castValue($rawValue, $property);

            $property->setValue($instance, $value);
        }

        return $instance;
    }

    /**
     * Hydrate a DTO from cached metadata (no reflection on DTO class).
     *
     * @param string $dtoClass Fully qualified class name
     * @param array $data Associative array (snake_case keys from request)
     * @param array $propertyMeta Cached property metadata
     * @return object Hydrated DTO instance
     */
    public static function hydrateFromCache(string $dtoClass, array $data, array $propertyMeta): object
    {
        $instance = (new ReflectionClass($dtoClass))->newInstanceWithoutConstructor();

        foreach ($propertyMeta as $camelName => $meta) {
            $snakeName = $meta['mapFrom'];
            $hasValue = array_key_exists($snakeName, $data);
            $rawValue = $hasValue ? $data[$snakeName] : null;

            if (!$hasValue && $meta['hasDefault']) {
                continue;
            }

            if (!$hasValue) {
                continue;
            }

            $value = self::castValueFromCache($rawValue, $meta);

            $ref = new ReflectionProperty($dtoClass, $camelName);
            $ref->setValue($instance, $value);
        }

        return $instance;
    }

    /**
     * @param mixed $rawValue
     * @return mixed
     */
    private static function castValue($rawValue, ReflectionProperty $property)
    {
        if ($rawValue === null) {
            return null;
        }

        $type = $property->getType();

        if (!$type instanceof ReflectionNamedType) {
            return $rawValue;
        }

        $typeName = $type->getName();

        if (!$type->isBuiltin() && is_array($rawValue) && class_exists($typeName)) {
            return self::hydrate($typeName, $rawValue);
        }

        if ($typeName === 'array' && is_array($rawValue) && method_exists($property, 'getAttributes')) {
            $arrayTypeAttrs = $property->getAttributes(ArrayType::class);

            if (!empty($arrayTypeAttrs)) {
                $arrayType = $arrayTypeAttrs[0]->newInstance();

                if ($arrayType->itemType !== null) {
                    return self::castArrayItems($rawValue, $arrayType->itemType);
                }
            }

            return $rawValue;
        }

        if ($typeName === 'array' && is_array($rawValue)) {
            return $rawValue;
        }

        return self::coerceScalar($rawValue, $typeName);
    }

    /**
     * @param mixed $rawValue
     * @return mixed
     */
    private static function castValueFromCache($rawValue, array $meta)
    {
        if ($rawValue === null) {
            return null;
        }

        $typeName = $meta['type'];

        if (
            !in_array($typeName, ['string', 'int', 'float', 'bool', 'array', 'mixed'], true)
            && is_array($rawValue)
            && class_exists($typeName)
        ) {
            return self::hydrate($typeName, $rawValue);
        }

        if ($typeName === 'array' && is_array($rawValue) && !empty($meta['arrayItemType'])) {
            return self::castArrayItems($rawValue, $meta['arrayItemType']);
        }

        return self::coerceScalar($rawValue, $typeName);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function castArrayItems(array $items, string $itemType): array
    {
        if (class_exists($itemType)) {
            return array_map(
                fn ($item) => is_array($item) ? self::hydrate($itemType, $item) : $item,
                $items
            );
        }

        return array_map(
            fn ($item) => self::coerceScalar($item, $itemType),
            $items
        );
    }

    private static function coerceScalar($value, string $typeName)
    {
        switch ($typeName) {
            case 'int':               return (int) $value;
            case 'float':             return (float) $value;
            case 'bool':              return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
            case 'string':            return (string) $value;
            case 'DateTimeImmutable':
            case 'DateTimeInterface':
                if ($value instanceof \DateTimeImmutable) {
                    return $value;
                }

                if (is_string($value) && $value !== '') {
                    try {
                        return new \DateTimeImmutable($value);
                    } catch (\Exception $e) {
                        return null;
                    }
                }

                return null;
            default:                  return $value;
        }
    }

    public static function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($input)));
    }
}
