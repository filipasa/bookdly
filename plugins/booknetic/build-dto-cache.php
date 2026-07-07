<?php

/**
 * Build-time cache generator for REST API DTO pipeline.
 *
 * Usage:
 *   php build-dto-cache.php                        # Core plugin only
 *   php build-dto-cache.php booknetic-{addon-name}  # Specific addon
 *
 * Scans all classes with #[ApiController] attribute, extracts reflection
 * metadata for #[FromBody]/#[FromQuery] parameters and their DTO classes,
 * and writes a cache file that eliminates runtime reflection.
 */

require_once __DIR__ . '/vendor/autoload.php';

use BookneticApp\Providers\Core\Dto\DtoMetadataCache;
use BookneticApp\Providers\Router\Attributes\ApiController;
use BookneticApp\Providers\Router\Attributes\FromBody;
use BookneticApp\Providers\Router\Attributes\FromQuery;

$addonArg = $argv[1] ?? null;

if ($addonArg !== null) {
    $addonDir = dirname(__DIR__) . '/' . $addonArg;

    if (!is_dir($addonDir)) {
        echo "Error: Addon directory not found: {$addonDir}\n";
        exit(1);
    }

    $addonAutoloader = $addonDir . '/vendor/autoload.php';

    if (file_exists($addonAutoloader)) {
        require_once $addonAutoloader;
    }

    $scanDir = $addonDir . '/App';
    $cacheDir = $addonDir . '/cache';
    $cachePath = $cacheDir . '/dto_metadata_cache.php';

    echo "Building cache for addon: {$addonArg}\n";
} else {
    $scanDir = __DIR__ . '/app';
    $cacheDir = __DIR__ . '/cache';
    $cachePath = $cacheDir . '/dto_metadata_cache.php';

    echo "Building cache for core plugin\n";
}

if (!is_dir($scanDir)) {
    echo "Error: Scan directory not found: {$scanDir}\n";
    exit(1);
}

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

echo "Scanning for #[ApiController] classes in {$scanDir}...\n";

$controllers = [];
$dtos = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scanDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $content = file_get_contents($file->getPathname());

    if (strpos($content, 'ApiController') === false) {
        continue;
    }

    $namespace = '';
    $className = '';

    if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
        $namespace = $nsMatch[1];
    }

    if (preg_match('/class\s+(\w+)/', $content, $classMatch)) {
        $className = $classMatch[1];
    }

    if (empty($namespace) || empty($className)) {
        continue;
    }

    $fqcn = $namespace . '\\' . $className;

    if (!class_exists($fqcn)) {
        continue;
    }

    $ref = new ReflectionClass($fqcn);
    $apiControllerAttrs = $ref->getAttributes(ApiController::class);

    if (empty($apiControllerAttrs)) {
        continue;
    }

    echo "  Found: {$fqcn}\n";

    $methods = [];

    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getDeclaringClass()->getName() !== $fqcn) {
            continue;
        }

        $params = [];
        $hasDtoParam = false;

        foreach ($method->getParameters() as $param) {
            $isFromBody = !empty($param->getAttributes(FromBody::class));
            $isFromQuery = !empty($param->getAttributes(FromQuery::class));
            $isDtoParam = $isFromBody || $isFromQuery;

            if ($isDtoParam) {
                $hasDtoParam = true;
            }

            $type = $param->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'mixed';

            $params[] = [
                'name'        => $param->getName(),
                'type'        => $typeName,
                'fromRequest' => $isDtoParam,
            ];

            if ($isDtoParam && class_exists($typeName) && !isset($dtos[$typeName])) {
                $dtos[$typeName] = DtoMetadataCache::reflectDtoProperties($typeName);
                echo "    DTO: {$typeName}\n";

                collectNestedDtos($dtos[$typeName], $dtos);
            }
        }

        if ($hasDtoParam) {
            $methods[$method->getName()] = $params;
        }
    }

    if (!empty($methods)) {
        $controllers[$fqcn] = $methods;
    }
}

function collectNestedDtos(array $propertyMeta, array &$dtos): void
{
    foreach ($propertyMeta as $meta) {
        $typeName = $meta['type'];

        if (
            !in_array($typeName, ['string', 'int', 'float', 'bool', 'array', 'mixed'], true)
            && class_exists($typeName)
            && !isset($dtos[$typeName])
        ) {
            $dtos[$typeName] = DtoMetadataCache::reflectDtoProperties($typeName);
            echo "    Nested DTO: {$typeName}\n";
            collectNestedDtos($dtos[$typeName], $dtos);
        }

        if (!empty($meta['arrayItemType']) && class_exists($meta['arrayItemType']) && !isset($dtos[$meta['arrayItemType']])) {
            $itemType = $meta['arrayItemType'];
            $dtos[$itemType] = DtoMetadataCache::reflectDtoProperties($itemType);
            echo "    Array item DTO: {$itemType}\n";
            collectNestedDtos($dtos[$itemType], $dtos);
        }
    }
}

$cacheContent = "<?php\n\n// Auto-generated by build-dto-cache.php — do NOT edit manually.\n// Generated: " . date('Y-m-d H:i:s') . "\n\nreturn " . var_export([
    'controllers' => $controllers,
    'dtos'        => $dtos,
], true) . ";\n";

file_put_contents($cachePath, $cacheContent);

$controllerCount = count($controllers);
$dtoCount = count($dtos);

echo "\nCache written to: {$cachePath}\n";
echo "Controllers: {$controllerCount}, DTOs: {$dtoCount}\n";
echo "Done.\n";
