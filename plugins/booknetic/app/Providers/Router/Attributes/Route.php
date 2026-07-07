<?php

namespace BookneticApp\Providers\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Route
{
    public string $route;
    public function __construct(string $route)
    {
        $this->route = $route;
    }
}
