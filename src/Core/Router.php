<?php

declare(strict_types=1);

namespace AEFS\Core;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use RuntimeException;

final class Router
{
    private RouteCollection $routes;

    public function __construct(
        private readonly Container $container,
        ?RouteCollection $routes = null
    ) {
        $this->routes = $routes ?? new RouteCollection();
    }

    public function get(
        string $uri,
        mixed $action
    ): Route {
        return $this->map(
            ['GET'],
            $uri,
            $action
        );
    }

    public function post(
        string $uri,
        mixed $action
    ): Route {
        return $this->map(
            ['POST'],
            $uri,
            $action
        );
    }

    public function put(
        string $uri,
        mixed $action
    ): Route {
        return $this->map(
            ['PUT'],
            $uri,
            $action
        );
    }

    public function patch(
        string $uri,
        mixed $action
    ): Route {
        return $this->map(
            ['PATCH'],
            $uri,
            $action
        );
    }

    public function delete(
        string $uri,
        mixed $action
    ): Route {
        return $this->map(
            ['DELETE'],
            $uri,
            $action
        );
    }

    public function options(
        string $uri,
        mixed $action
    ): Route {
        return $this->map(
            ['OPTIONS'],
            $uri,
            $action
        );
    }

    public function any(
        string $uri,
        mixed $action
    ): Route {
        return $this->map(
            [
                'GET',
                'POST',
                'PUT',
                'PATCH',
                'DELETE',
                'OPTIONS',
            ],
            $uri,
            $action
        );
    }

    /**
     * @param list<string> $methods
     */
    public function map(
        array $methods,
        string $uri,
        mixed $action
    ): Route {
        $route = new Route(
            implode('|', $methods),
            $uri,
            $action
        );

        $this->routes->add($route);

        return $route;
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if (!$route instanceof Route) {
                continue;
            }

            if (!$route->allows($request->method())) {
                continue;
            }

            $parameters = $route->compile(
                $request->path()
            );

            if ($parameters === null) {
                continue;
            }

            $route->setParameters($parameters);
            $request->setRouteParameters($parameters);

            return $this->dispatchRoute(
                $route,
                $request
            );
        }

        return new Response(
            '404 Not Found',
            404,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]
        );
    }

    public function routes(): RouteCollection
    {
        return $this->routes;
    }

    private function dispatchRoute(
        Route $route,
        Request $request
    ): Response {
        $destination = function (Request $request) use ($route): Response {
            return $this->dispatchAction(
                $route,
                $request
            );
        };

        $pipeline = array_reduce(
            array_reverse($route->getMiddleware()),
            function (
                callable $next,
                string $middlewareClass
            ): callable {
                return function (Request $request) use (
                    $middlewareClass,
                    $next
                ): Response {
                    $middleware = $this->container->get(
                        $middlewareClass
                    );

                    if (!method_exists($middleware, 'handle')) {
                        throw new RuntimeException(
                            sprintf(
                                'Middleware [%s] heeft geen handle()-methode.',
                                $middlewareClass
                            )
                        );
                    }

                    return $this->normalizeResponse(
                        $middleware->handle(
                            $request,
                            $next
                        )
                    );
                };
            },
            $destination
        );

        return $this->normalizeResponse(
            $pipeline($request)
        );
    }

    private function dispatchAction(
        Route $route,
        Request $request
    ): Response {
        $action = $route->action();

        if (is_callable($action)) {
            return $this->normalizeResponse(
                $action($request)
            );
        }

        if (
            is_array($action)
            && count($action) === 2
        ) {
            [$controllerClass, $method] = $action;

            if (
                !is_string($controllerClass)
                || !is_string($method)
            ) {
                throw new RuntimeException(
                    'Ongeldige controlleractie.'
                );
            }

            $controller = $this->container->get(
                $controllerClass
            );

            if (!method_exists($controller, $method)) {
                throw new RuntimeException(
                    sprintf(
                        'Controllermethode [%s::%s] bestaat niet.',
                        $controllerClass,
                        $method
                    )
                );
            }

            return $this->normalizeResponse(
                $controller->{$method}($request)
            );
        }

        throw new RuntimeException(
            'Ongeldige routeactie.'
        );
    }

    private function normalizeResponse(mixed $response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        return new Response(
            (string) $response
        );
    }
}
