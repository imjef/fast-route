<?php

namespace app\routes;

use function FastRoute\simpleDispatcher;

final class Router {

    private array $routes;

    public function get(string $uri, mixed $handler)
    {
        $this->routes['GET'] = [$uri, $handler];
    }

    public function ready() : array
    {
        $dispatcher = simpleDispatcher(function (\FastRoute\RouteCollector $route) {
            foreach($this->routes as $httMethod => $args) {
                $route->addRoute($httMethod, ...$args);
            }
        });
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        if(false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        return $dispatcher->dispatch($httpMethod, $uri);
    }

    public function render() : void
    {
        [$state, $handler, $args] = $this->ready();

        switch($state) {

            case \FastRoute\Dispatcher::NOT_FOUND:
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                break;
            case \FastRoute\Dispatcher::FOUND:
                if(is_callable($handler)) {
                    $handler();
                    break;
                }
                if(is_string($handler)) {
                    [$controller, $method] = explode(':', $handler);

                    $controllerNamespace = "app\\controllers\\{$controller}";
           
                    $controllerInstance = new $controllerNamespace;

                    call_user_func_array([$controllerInstance, $method], $args);
                }
                break;
        }
    }
}
?>