<?php

namespace BookneticApp\Providers\Router\WordPress;

use BookneticApp\Providers\Core\Exceptions\ValidationException;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Core\RestRoute;
use BookneticApp\Providers\Router\Contracts\ParameterResolverInterface;
use BookneticApp\Providers\Router\Contracts\RouteRegistrarInterface;
use BookneticApp\Providers\Router\RouteCollection;
use BookneticApp\Providers\Router\RouteItem;
use Closure;
use WP_Error;
use WP_REST_Request;

class WordPressRouteRegistrar implements RouteRegistrarInterface
{
    private string $namespace;
    private Closure $serviceLocator;
    private ParameterResolverInterface $resolver;

    public function __construct(
        string $namespace,
        Closure $serviceLocator,
        ParameterResolverInterface $resolver
    ) {
        $this->namespace = $namespace;
        $this->serviceLocator = $serviceLocator;
        $this->resolver = $resolver;
    }

    public function register(RouteCollection $routes): void
    {
        add_action('rest_api_init', function () use ($routes) {
            foreach ($routes as $route) {
                $this->registerRoute($route);
            }
        });
    }

    private function registerRoute(RouteItem $route): void
    {
        $wpPattern = preg_replace('#\{(\w+)}#', '(?P<$1>[^/]+)', $route->route);

        register_rest_route($this->namespace, $wpPattern, [
            'methods' => $route->method,
            'callback' => function (WP_REST_Request $request) use ($route) {
                try {
                    Permission::setAsBackEnd();
                    Permission::setIsMobile(RestRoute::isAppPasswordRequest());

                    $controller = ($this->serviceLocator)($route->controller);
                    $args = $this->resolver->resolve($route, $request);
                    $result = $controller->{$route->action}(...$args);

                    return is_array($result) || is_object($result) ? $result : ['error_msg' => 'Error'];
                } catch (ValidationException $e) {
                    return new WP_Error(422, $e->getMessage(), [
                        'status' => 422,
                        'errors' => $e->getErrors(),
                    ]);
                } catch (\Exception $e) {
                    $code = in_array($e->getCode(), [400, 404, 409, 422, 500], true) ? $e->getCode() : 400;

                    return new WP_Error($code, $e->getMessage(), ['status' => $code]);
                }
            },
            'permission_callback' => fn () => current_user_can('read'),
        ]);
    }
}
