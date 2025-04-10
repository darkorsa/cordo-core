<?php

declare(strict_types=1);

namespace Cordo\Core\UI\Http;

use stdClass;
use Relay\Relay;
use FastRoute\RouteCollector;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router
{
    private $middlewares = [];

    private $routes = [];

    public function addMiddleware(MiddlewareInterface|array $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function addRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
        $route = new stdClass();
        $route->method      = $method;
        $route->path        = $path;
        $route->handler     = $handler;
        $route->middlewares = array_merge($this->middlewares, $middlewares);

        $this->routes[] = $route;
    }

    public function routes()
    {
        return function (RouteCollector $collector) {
            foreach ($this->routes as $route) {
                $collector->addRoute($route->method, $route->path, $this->getHandler($route));
            }
        };
    }

    public function getRoutesCollection(array $options = []): array
    {
        $options += [
            'routeParser' => 'FastRoute\\RouteParser\\Std',
            'dataGenerator' => 'FastRoute\\DataGenerator\\GroupCountBased',
        ];

        $routeCollector = new RouteCollector(
            new $options['routeParser'],
            new $options['dataGenerator']
        );

        ($this->routes())($routeCollector);

        return $routeCollector->getData();
    }

    private function getHandler(stdClass $route)
    {
        return function (ServerRequestInterface $request, ContainerInterface $container, array $vars) use ($route) {
            $route->middlewares[] = static function (
                RequestInterface $request,
                RequestHandlerInterface $handler
            ) use (
                $route,
                $container,
                $vars
            ) {
                if (is_callable($route->handler)) {
                    $handlerCallable = $route->handler;

                    return $handlerCallable($request, $vars);
                }

                [$controller, $action] = explode('@', $route->handler);

                return $container->get($controller)->run($request, $action, $vars);
            };

            return $this->processMiddlewares($route->middlewares, $request);
        };
    }

    private function processMiddlewares(array $middlewares, ServerRequestInterface $request): ResponseInterface
    {
        $middlewares = array_map(static function ($middleware) {
            if (is_array($middleware)) {
                [$className, $params] = $middleware;
                return new $className(...$params);
            } else {
                return $middleware;
            }
        }, $middlewares);

        $relay = new Relay($middlewares, static function ($entry) {
            return is_string($entry) ? new $entry() : $entry;
        });

        return $relay->handle($request);
    }
}
