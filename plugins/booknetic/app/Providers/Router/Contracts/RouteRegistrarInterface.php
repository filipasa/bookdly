<?php

namespace BookneticApp\Providers\Router\Contracts;

use BookneticApp\Providers\Router\RouteCollection;

interface RouteRegistrarInterface
{
    public function register(RouteCollection $routes): void;
}
