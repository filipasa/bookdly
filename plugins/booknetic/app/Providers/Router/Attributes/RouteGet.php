<?php

namespace BookneticApp\Providers\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RouteGet extends HttpMethod
{
    public function __construct(string $route = '')
    {
        parent::__construct('GET', $route);
    }
}
