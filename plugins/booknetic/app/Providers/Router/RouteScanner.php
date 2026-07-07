<?php

namespace BookneticApp\Providers\Router;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use BookneticApp\Providers\Router\Attributes\ApiController;
use BookneticApp\Providers\Router\Attributes\FromBody;
use BookneticApp\Providers\Router\Attributes\FromForm;
use BookneticApp\Providers\Router\Attributes\FromQuery;
use BookneticApp\Providers\Router\Attributes\FromRoute;
use BookneticApp\Providers\Router\Attributes\HttpMethod;
use BookneticApp\Providers\Router\Attributes\Route;
use SplFileInfo;

class RouteScanner
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return RouteItem[]
     */
    public function scan(): array
    {
        if (!is_dir($this->directory)) {
            throw new \InvalidArgumentException("Directory does not exist: {$this->directory}");
        }

        $routes = [];
        $classMap = $this->scanDirectory();

        foreach ($classMap as $class => $file) {
            $content = file_get_contents($file);
            if ($content === false || strpos($content, 'ApiController') === false) {
                continue;
            }

            if (!class_exists($class, false)) {
                try {
                    require_once $file;
                } catch (\Throwable $_) {
                    continue;
                }
            }

            try {
                $reflection = new ReflectionClass($class);
            } catch (ReflectionException $_) {
                continue;
            }

            if (!$reflection->getAttributes(ApiController::class)) {
                continue;
            }

            $classRoute = $reflection->getAttributes(Route::class);
            $routePrefix = !empty($classRoute) ? $classRoute[0]->newInstance()->route : '';

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(HttpMethod::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                    /** @var HttpMethod $httpMethod */
                    $httpMethod = $attr->newInstance();
                    $routePath = rtrim($routePrefix, '/') . '/' . ltrim($httpMethod->route, '/');

                    $routes[] = new RouteItem(
                        $routePath,
                        $httpMethod->method,
                        $class,
                        $method->getName(),
                        $this->extractParams($method)
                    );
                }
            }
        }

        return $routes;
    }

    /**
     * @return RouteParam[]
     */
    private function extractParams(ReflectionMethod $method): array
    {
        $params = [];

        foreach ($method->getParameters() as $refParam) {
            $source = 'route';
            $alias = null;

            $fromRoute = $refParam->getAttributes(FromRoute::class);
            if (!empty($fromRoute)) {
                $source = 'route';
                $alias = $fromRoute[0]->newInstance()->name;
            } elseif (!empty($refParam->getAttributes(FromBody::class))) {
                $source = 'body';
            } elseif (!empty($refParam->getAttributes(FromQuery::class))) {
                $source = 'query';
            } elseif (!empty($fromForm = $refParam->getAttributes(FromForm::class))) {
                $source = 'form';
                $inst = $fromForm[0]->newInstance();
                $alias = $inst->name !== '' ? $inst->name : null;
            }

            $type = $refParam->getType();
            $typeName = $type !== null ? $type->getName() : 'mixed';

            $params[] = new RouteParam(
                $refParam->getName(),
                $typeName,
                $source,
                $alias,
                $refParam->isOptional(),
                $refParam->isOptional() && $refParam->isDefaultValueAvailable() ? $refParam->getDefaultValue() : null
            );
        }

        return $params;
    }

    /**
     * @return array<string, string> className => filePath
     */
    private function scanDirectory(): array
    {
        $classes = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $classNames = $this->extractClassNamesFromFile($file->getPathname());
            foreach ($classNames as $className) {
                $classes[$className] = $file->getPathname();
            }
        }

        return $classes;
    }

    /**
     * @return string[]
     */
    private function extractClassNamesFromFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $classes = [];
        $tokens = token_get_all($content);
        $namespace = '';

        foreach ($tokens as $i => $iValue) {
            $token = $iValue;

            if (is_array($token)) {
                [$tokenType] = $token;

                switch ($tokenType) {
                    case T_NAMESPACE:
                        $namespace = $this->extractNamespace($tokens, $i);
                        break;

                    case T_CLASS:
                    case T_INTERFACE:
                    case T_TRAIT:
                        $className = $this->extractClassName($tokens, $i);
                        if ($className) {
                            $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
                            $classes[] = $fullClassName;
                        }
                        break;
                }
            }
        }

        return $classes;
    }

    private function extractNamespace(array $tokens, int &$position): string
    {
        $namespace = '';
        $position++;

        while (isset($tokens[$position]) && is_array($tokens[$position]) && $tokens[$position][0] === T_WHITESPACE) {
            $position++;
        }

        while (isset($tokens[$position])) {
            $token = $tokens[$position];

            if (is_array($token)) {
                if (in_array($token[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                    $namespace .= $token[1];
                } elseif ($token[0] !== T_WHITESPACE) {
                    break;
                }
            } elseif ($token === ';') {
                break;
            }

            $position++;
        }

        return trim($namespace);
    }

    private function extractClassName(array $tokens, int &$position): string
    {
        $position++;

        while (isset($tokens[$position]) && is_array($tokens[$position]) && $tokens[$position][0] === T_WHITESPACE) {
            $position++;
        }

        if (isset($tokens[$position]) && is_array($tokens[$position]) && $tokens[$position][0] === T_STRING) {
            return $tokens[$position][1];
        }

        return '';
    }
}
