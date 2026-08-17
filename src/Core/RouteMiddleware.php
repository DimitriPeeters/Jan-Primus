<?php

declare(strict_types=1);

namespace AEFS\Core;

use AEFS\HTTP\Request;
use AEFS\HTTP\Response;

final class RouteMiddleware
{
    /**
     * @var array<string, class-string>
     */
    private array $aliases = [];

    public function __construct(
        private readonly Container $container
    ) {
    }

    public function alias(string $name, string $middleware): self
    {
        $this->aliases[$name] = $middleware;

        return $this;
    }

    /**
     * @return array<string, class-string>
     */
    public function aliases(): array
    {
        return $this->aliases;
    }

    public function handle(
        Request $request,
        Route $route,
        callable $destination
    ): Response {
        $middleware = $this->resolve(
            $route->middlewareList()
        );

        $pipeline = array_reduce(
            array_reverse($middleware),
            fn (callable $next, object $current): callable =>
                fn (Request $request): Response =>
                    $current->handle($request, $next),

            $destination
        );

        return $pipeline($request);
    }

    /**
     * @param array<int,string> $middleware
     * @return array<int,object>
     */
    private function resolve(array $middleware): array
    {
        $resolved = [];

        foreach ($middleware as $item) {

            $class = $this->aliases[$item] ?? $item;

            $resolved[] = $this->container->get($class);
        }

        return $resolved;
    }
}