<?php

namespace BookneticApp\Providers\Router\WordPress;

use BookneticApp\Providers\Router\Contracts\ParameterResolverInterface;
use BookneticApp\Providers\Router\RouteItem;
use BookneticApp\Providers\Router\RouteParam;
use WP_REST_Request;

class WordPressParameterResolver implements ParameterResolverInterface
{
    public function resolve(RouteItem $route, $request): array
    {
        /** @var WP_REST_Request $request */
        $args = [];

        foreach ($route->params as $param) {
            $key = $param->alias ?? $param->name;

            switch ($param->source) {
                case 'route':
                    $args[] = $request->get_param($key) ?? ($param->isOptional ? $param->default : null);
                    break;
                case 'body':
                    $args[] = $this->resolveBody($param, $request);
                    break;
                case 'query':
                    $args[] = $this->resolveQuery($param, $request);
                    break;
            }
        }

        return $args;
    }

    private function resolveBody(RouteParam $param, WP_REST_Request $request)
    {
        return $request->get_json_params();
    }

    private function resolveQuery(RouteParam $param, WP_REST_Request $request)
    {
        return $request->get_query_params();
    }
}
