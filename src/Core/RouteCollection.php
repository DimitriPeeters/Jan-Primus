<?php

declare(strict_types=1);

namespace AEFS\Core;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use Traversable;

final class RouteCollection implements IteratorAggregate, Countable
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    /**
     * @var array<string, Route>
     */
    private array $namedRoutes = [];

    public function add(Route $route): self
    {
        $this->routes[] = $route;

        if ($route->getName() !== null) {
            $this->namedRoutes[$route->getName()] = $route;
        }

        return $this;
    }

    /**
     * @return array<int, Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function findByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function hasNamed(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    public function clear(): void
    {
        $this->routes = [];
        $this->namedRoutes = [];
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function isEmpty(): bool
    {
        return $this->routes === [];
    }

    public function first(): ?Route
    {
        return $this->routes[0] ?? null;
    }

    public function last(): ?Route
    {
        if ($this->routes === []) {
            return null;
        }

        return $this->routes[array_key_last($this->routes)];
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->routes);
    }
}