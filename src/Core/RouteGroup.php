<?php

declare(strict_types=1);

namespace AEFS\Core;

final class RouteGroup
{
    private string $prefix = '';

    private ?string $name = null;

    /**
     * @var array<int, string>
     */
    private array $middleware = [];

    /**
     * @var array<string, string>
     */
    private array $where = [];

    public function prefix(string $prefix): self
    {
        $this->prefix = '/' . trim($prefix, '/');

        return $this;
    }

    public function name(string $name): self
    {
        $this->name = rtrim($name, '.') . '.';

        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        foreach ((array) $middleware as $item) {
            $this->middleware[] = $item;
        }

        return $this;
    }

    public function where(string $parameter, string $pattern): self
    {
        $this->where[$parameter] = $pattern;

        return $this;
    }

    public function apply(Route $route): Route
    {
        $uri = $this->prefix . $route->uri();

        $new = new Route(
            $route->methods(),
            $uri,
            $route->action()
        );

        if ($route->getName() !== null || $this->name !== null) {
            $new->name(
                ($this->name ?? '') .
                ($route->getName() ?? '')
            );
        }

        foreach ($this->middleware as $middleware) {
            $new->middleware($middleware);
        }

        foreach ($route->middlewareList() as $middleware) {
            $new->middleware($middleware);
        }

        foreach ($this->where as $parameter => $pattern) {
            $new->where($parameter, $pattern);
        }

        foreach ($route->constraints() as $parameter => $pattern) {
            $new->where($parameter, $pattern);
        }

        $new->defaults(
            $route->defaultValues()
        );

        return $new;
    }

    public function prefixValue(): string
    {
        return $this->prefix;
    }

    public function namePrefix(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<int, string>
     */
    public function middlewareList(): array
    {
        return $this->middleware;
    }

    /**
     * @return array<string, string>
     */
    public function constraints(): array
    {
        return $this->where;
    }
}