<?php

declare(strict_types=1);

namespace AEFS\Core;

use Closure;

final class RouteRegistrar
{
    private ?RouteGroup $group = null;

    public function __construct(
        private readonly Router $router
    ) {
    }

    public function group(
        callable $callback,
        ?RouteGroup $group = null
    ): void {
        $previous = $this->group;

        $this->group = $group ?? new RouteGroup();

        $callback($this);

        $this->group = $previous;
    }

    public function prefix(string $prefix): self
    {
        $this->ensureGroup()->prefix($prefix);

        return $this;
    }

    public function name(string $prefix): self
    {
        $this->ensureGroup()->name($prefix);

        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $this->ensureGroup()->middleware($middleware);

        return $this;
    }

    public function where(string $parameter, string $pattern): self
    {
        $this->ensureGroup()->where($parameter, $pattern);

        return $this;
    }

    public function get(string $uri, mixed $action): Route
    {
        return $this->register(['GET'], $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->register(['POST'], $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->register(['PUT'], $uri, $action);
    }

    public function patch(string $uri, mixed $action): Route
    {
        return $this->register(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->register(['DELETE'], $uri, $action);
    }

    public function options(string $uri, mixed $action): Route
    {
        return $this->register(['OPTIONS'], $uri, $action);
    }

    public function any(string $uri, mixed $action): Route
    {
        return $this->register(
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            $uri,
            $action
        );
    }

    /**
     * @param array<int,string> $methods
     */
    private function register(
        array $methods,
        string $uri,
        mixed $action
    ): Route {
        $route = new Route(
            $methods,
            $uri,
            $action
        );

        if ($this->group !== null) {
            $route = $this->group->apply($route);
        }

        $this->router
            ->routes()
            ->add($route);

        return $route;
    }

    private function ensureGroup(): RouteGroup
    {
        if ($this->group === null) {
            $this->group = new RouteGroup();
        }

        return $this->group;
    }
}