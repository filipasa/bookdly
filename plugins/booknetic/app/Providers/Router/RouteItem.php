<?php

namespace BookneticApp\Providers\Router;

class RouteItem
{
    public string $route;
    public string $method;
    public string $controller;
    public string $action;
    /** @var RouteParam[] */
    public array $params;

    public function __construct(
        string $route,
        string $method,
        string $controller,
        string $action,
        array $params
    ) {
        $this->route = $route;
        $this->method = $method;
        $this->controller = $controller;
        $this->action = $action;
        $this->params = $params;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function __set_state(array $data): self
    {
        return new self(
            $data['route'],
            $data['method'],
            $data['controller'],
            $data['action'],
            $data['params']
        );
    }
}
