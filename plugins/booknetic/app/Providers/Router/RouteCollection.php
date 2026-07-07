<?php

namespace BookneticApp\Providers\Router;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, RouteItem>
 */
class RouteCollection implements IteratorAggregate, Countable
{
    private array $routes;
    /**
     * @param RouteItem[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public static function fromCache(RouteCache $cache): self
    {
        return new self($cache->read());
    }

    /**
     * @return RouteItem[]
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * @return RouteItem[]
     */
    public function byMethod(string $method): array
    {
        return array_values(
            array_filter($this->routes, fn (RouteItem $r) => $r->method === $method)
        );
    }

    /**
     * @return ArrayIterator<int, RouteItem>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->routes);
    }

    /**
     * @return string[]
     */
    public function controllers(): array
    {
        return array_values(array_unique(
            array_map(fn (RouteItem $r) => $r->controller, $this->routes)
        ));
    }

    public function count(): int
    {
        return count($this->routes);
    }
}
