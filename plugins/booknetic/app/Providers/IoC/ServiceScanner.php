<?php

namespace BookneticApp\Providers\IoC;

use BookneticApp\Providers\IoC\Attributes\Bind;
use BookneticApp\Providers\IoC\Attributes\Component;
use BookneticApp\Providers\IoC\Contracts\ServiceProviderInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class ServiceScanner
{
    private string $scanDir;

    public function __construct(string $scanDir)
    {
        $this->scanDir = $scanDir;
    }

    /**
     * Scan directory for #[Service] classes and ServiceProviderInterface implementations.
     *
     * @return array{services: array, bindings: array, providers: string[]}
     */
    public function scan(): array
    {
        $services = [];
        $bindings = [];
        $providers = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->scanDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            $hasService = strpos($content, '#[Service') !== false
                || strpos($content, '#[Repository') !== false
                || strpos($content, '#[Component') !== false;
            $hasProvider = strpos($content, 'ServiceProviderInterface') !== false;

            if (! $hasService && ! $hasProvider) {
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

            if (! class_exists($fqcn)) {
                try {
                    require_once $file->getPathname();
                } catch (\Throwable $e) {
                    continue;
                }

                if (! class_exists($fqcn)) {
                    continue;
                }
            }

            $ref = new ReflectionClass($fqcn);

            if ($ref->isAbstract() || $ref->isInterface()) {
                continue;
            }

            // Check for ServiceProviderInterface
            if ($ref->implementsInterface(ServiceProviderInterface::class)) {
                $providers[] = $fqcn;

                continue;
            }

            // Check for #[Component] (or subclass: #[Service], #[Repository])
            $serviceAttrs = $ref->getAttributes(Component::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (empty($serviceAttrs)) {
                continue;
            }

            $serviceAttr = $serviceAttrs[0]->newInstance();

            $services[$fqcn] = [
                'lifetime' => $serviceAttr->lifetime,
                'factory'  => null,
            ];

            // Check for #[Bind] attributes
            $bindAttrs = $ref->getAttributes(Bind::class);

            foreach ($bindAttrs as $bindAttr) {
                $bindInstance = $bindAttr->newInstance();
                $bindings[$bindInstance->interface] = $fqcn;
            }
        }

        return [
            'services'  => $services,
            'bindings'  => $bindings,
            'providers' => $providers,
        ];
    }
}
