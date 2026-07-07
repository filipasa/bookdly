<?php

namespace BookneticApp\Providers\Core\Dto;

use BookneticApp\Providers\Core\Attributes\Validation\ArrayType;
use BookneticApp\Providers\Router\Attributes\FromBody;
use BookneticApp\Providers\Router\Attributes\FromQuery;
use BookneticApp\Providers\Core\Attributes\Validation\ValidationRule;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

class DtoMetadataCache
{
    /** @var array<string, array> Plugin root → loaded cache data */
    private static array $cacheRegistry = [];

    /** @var array<string, string|null> Class name → resolved plugin root */
    private static array $pluginRoots = [];

    /**
     * Get parameter metadata for a controller method.
     * Returns null if no #[FromBody]/#[FromQuery] parameters found.
     *
     * @return array|null Array of parameter descriptors, or null
     */
    public static function getMethodParams(string $className, string $methodName): ?array
    {
        $cache = self::loadCacheForClass($className);

        if ($cache !== null) {
            return $cache['controllers'][$className][$methodName] ?? null;
        }

        return self::reflectMethodParams($className, $methodName);
    }

    /**
     * Get property metadata for a DTO class.
     *
     * @return array|null Property metadata, or null if not cached
     */
    public static function getDtoMeta(string $dtoClass): ?array
    {
        $cache = self::loadCacheForClass($dtoClass);

        if ($cache !== null) {
            return $cache['dtos'][$dtoClass] ?? null;
        }

        return null;
    }

    /**
     * Check if a pre-built cache file exists for the plugin that owns the given class.
     */
    public static function hasCacheForClass(string $className): bool
    {
        $pluginRoot = self::resolvePluginRoot($className);

        if ($pluginRoot === null) {
            return false;
        }

        return file_exists(self::getCachePathForPlugin($pluginRoot));
    }

    /**
     * Load the cache for the plugin that owns the given class.
     */
    private static function loadCacheForClass(string $className): ?array
    {
        $pluginRoot = self::resolvePluginRoot($className);

        if ($pluginRoot === null) {
            return null;
        }

        if (array_key_exists($pluginRoot, self::$cacheRegistry)) {
            return self::$cacheRegistry[$pluginRoot];
        }

        $cachePath = self::getCachePathForPlugin($pluginRoot);

        if (file_exists($cachePath)) {
            self::$cacheRegistry[$pluginRoot] = require $cachePath;

            return self::$cacheRegistry[$pluginRoot];
        }

        self::$cacheRegistry[$pluginRoot] = null;

        return null;
    }

    /**
     * Resolve the plugin root directory for a given class.
     * Walks up from the class file until it finds a directory containing init.php.
     */
    private static function resolvePluginRoot(string $className): ?string
    {
        if (array_key_exists($className, self::$pluginRoots)) {
            return self::$pluginRoots[$className];
        }

        if (!class_exists($className)) {
            self::$pluginRoots[$className] = null;

            return null;
        }

        $ref = new ReflectionClass($className);
        $filePath = $ref->getFileName();

        if ($filePath === false) {
            self::$pluginRoots[$className] = null;

            return null;
        }

        $dir = dirname($filePath);
        $maxDepth = 10;

        while ($dir !== dirname($dir) && $maxDepth-- > 0) {
            if (file_exists($dir . '/init.php')) {
                self::$pluginRoots[$className] = $dir;

                return $dir;
            }

            $dir = dirname($dir);
        }

        self::$pluginRoots[$className] = null;

        return null;
    }

    /**
     * Determine the cache file path for a plugin root.
     * Core: app/Providers/Core/Cache/dto_metadata_cache.php
     * Addons: cache/dto_metadata_cache.php
     */
    private static function getCachePathForPlugin(string $pluginRoot): string
    {
        return $pluginRoot . '/cache/dto_metadata_cache.php';
    }

    private static function reflectMethodParams(string $className, string $methodName): ?array
    {
        $ref = new ReflectionMethod($className, $methodName);
        $params = [];
        $hasFromRequest = false;

        foreach ($ref->getParameters() as $param) {
            $isFromBody = !empty($param->getAttributes(FromBody::class));
            $isFromQuery = !empty($param->getAttributes(FromQuery::class));
            $isFromRequest = $isFromBody || $isFromQuery;

            if ($isFromRequest) {
                $hasFromRequest = true;
            }

            $type = $param->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'mixed';

            $params[] = [
                'name'        => $param->getName(),
                'type'        => $typeName,
                'fromRequest' => $isFromRequest,
            ];
        }

        return $hasFromRequest ? $params : null;
    }

    /**
     * Runtime reflection: extract property metadata from a DTO class (for cache builder).
     *
     * @return array<string, array> keyed by camelCase property name
     */
    public static function reflectDtoProperties(string $dtoClass): array
    {
        $ref = new ReflectionClass($dtoClass);
        $properties = [];

        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $camelName = $property->getName();
            $type = $property->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'mixed';
            $nullable = $type instanceof ReflectionNamedType && $type->allowsNull();

            $rules = [];
            $arrayItemType = null;

            foreach ($property->getAttributes() as $attribute) {
                $attrInstance = $attribute->newInstance();

                if ($attrInstance instanceof ArrayType && $attrInstance->itemType !== null) {
                    $arrayItemType = $attrInstance->itemType;
                }

                if ($attrInstance instanceof ValidationRule) {
                    $ruleData = ['type' => (new ReflectionClass($attrInstance))->getShortName()];

                    foreach ((new ReflectionClass($attrInstance))->getProperties(ReflectionProperty::IS_PUBLIC) as $ruleProp) {
                        $ruleData[$ruleProp->getName()] = $ruleProp->getValue($attrInstance);
                    }

                    $rules[] = $ruleData;
                }
            }

            $properties[$camelName] = [
                'type'          => $typeName,
                'nullable'      => $nullable,
                'hasDefault'    => $property->hasDefaultValue(),
                'mapFrom'       => DtoHydrator::camelToSnake($camelName),
                'arrayItemType' => $arrayItemType,
                'rules'         => $rules,
            ];
        }

        return $properties;
    }
}
