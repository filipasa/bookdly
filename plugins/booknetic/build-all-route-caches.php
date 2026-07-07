<?php

require_once __DIR__ . '/vendor/autoload.php';

use BookneticApp\Providers\Router\RouteCache;
use BookneticApp\Providers\Router\RouteScanner;

$pluginsDir = dirname(__DIR__);
$totalRoutes = 0;

// 1. Build core cache
echo "=== Core ===\n";
$totalRoutes += buildCache(
    __DIR__ . '/app/Backend',
    __DIR__ . '/cache/routes.php',
    'booknetic'
);

// 2. Build addon caches
foreach (glob($pluginsDir . '/booknetic-*/') as $addonDir) {
    $slug = basename($addonDir);
    $addonAppDir = $addonDir . 'App';

    if (!is_dir($addonAppDir)) {
        continue;
    }

    // Load addon autoloader
    $autoload = $addonDir . 'vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    echo "\n=== {$slug} ===\n";
    $totalRoutes += buildCache(
        $addonAppDir,
        $addonDir . 'cache/routes.php',
        $slug
    );
}

echo "\n=== Summary ===\n";
echo "Total routes cached: {$totalRoutes}\n";
echo "Done.\n";

function buildCache(string $scanDir, string $cachePath, string $label): int
{
    $scanner = new RouteScanner($scanDir);
    $routes = $scanner->scan();

    $count = count($routes);

    if ($count === 0) {
        echo "[{$label}] No routes found — skipping.\n";

        return 0;
    }

    foreach ($routes as $route) {
        echo "  {$route->method} {$route->route} → {$route->controller}::{$route->action}\n";
    }

    $cache = new RouteCache($cachePath);
    $cache->write($routes);

    echo "[{$label}] {$count} routes cached → {$cachePath}\n";

    return $count;
}
