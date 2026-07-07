<?php

namespace BookneticApp\Providers\Router\Contracts;

use BookneticApp\Providers\Router\RouteItem;

interface ParameterResolverInterface
{
    /**
     * Resolve ordered parameters for a controller action from the framework's request.
     *
     * @return array<int, mixed>
     */
    public function resolve(RouteItem $route, $request): array;
}
