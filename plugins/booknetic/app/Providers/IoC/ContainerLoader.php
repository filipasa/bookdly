<?php

namespace BookneticApp\Providers\IoC;

use BookneticApp\Providers\IoC\Contracts\ServiceProviderInterface;

class ContainerLoader
{
    /**
     * Load services from a cache array (production).
     *
     * @param array{services: array, bindings: array, providers: string[]} $cache
     */
    public static function loadFromCache(array $cache): void
    {
        self::registerServices($cache['services'] ?? []);
        self::registerBindings($cache['bindings'] ?? []);
        self::runProviders($cache['providers'] ?? []);
    }

    /**
     * Load services from scanner results (development).
     *
     * @param array{services: array, bindings: array, providers: string[]} $scanResult
     */
    public static function loadFromScan(array $scanResult): void
    {
        self::loadFromCache($scanResult);
    }

    private static function registerServices(array $services): void
    {
        foreach ($services as $id => $meta) {
            $lifetime = $meta['lifetime'] ?? ServiceLifetime::SINGLETON;
            $factory = $meta['factory'] ?? null;

            switch ($lifetime) {
                case ServiceLifetime::SCOPED:
                    Container::addScoped($id, $factory);
                    break;
                case ServiceLifetime::TRANSIENT:
                    Container::addTransient($id, $factory);
                    break;
                default:
                    Container::add($id, $factory);
                    break;
            }
        }
    }

    private static function registerBindings(array $bindings): void
    {
        foreach ($bindings as $interface => $concrete) {
            Container::bind($interface, $concrete);
        }
    }

    private static function runProviders(array $providers): void
    {
        foreach ($providers as $providerClass) {
            if (! class_exists($providerClass)) {
                continue;
            }

            $provider = new $providerClass();

            if (! $provider instanceof ServiceProviderInterface) {
                continue;
            }

            $builder = new ContainerBuilder();
            $provider->register($builder);
            $builder->applyToContainer();
        }
    }
}
