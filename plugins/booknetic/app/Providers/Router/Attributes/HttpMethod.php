<?php

namespace BookneticApp\Providers\Router\Attributes;

/**
 * @internal
 */
class HttpMethod
{
    public string $method;
    public string $route;

    public function __construct(
        string $method,
        string $route = ''
    ) {
        $this->method = $method;
        $this->route = $route;
    }
}
