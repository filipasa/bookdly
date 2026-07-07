<?php

require_once __DIR__ . '/vendor/autoload.php';

use BookneticApp\Providers\Router\RouteCache;
use BookneticApp\Providers\Router\RouteScanner;

$scanDir   = $argv[1] ?? __DIR__ . '/app/Backend';
$cachePath = $argv[2] ?? __DIR__ . '/cache/routes.php';

// If scanning an addon directory, load its autoloader
$addonAutoload = dirname($scanDir) . '/vendor/autoload.php';
if (file_exists($addonAutoload) && realpath($addonAutoload) !== realpath(__DIR__ . '/vendor/autoload.php')) {
    require_once $addonAutoload;
}

echo "Scanning for #[ApiController] classes in {$scanDir}...\n";

$scanner = new RouteScanner($scanDir);
$routes = $scanner->scan();

echo 'Found ' . count($routes) . " routes.\n";

foreach ($routes as $route) {
    echo "  {$route->method} {$route->route} → {$route->controller}::{$route->action}\n";
}

if (count($routes) === 0) {
    echo "\nNo routes found — skipping cache write.\n";
    exit(0);
}

$cache = new RouteCache($cachePath);
$cache->write($routes);

echo "\nRoute cache written to: {$cachePath}\n";
echo "Done.\n";
