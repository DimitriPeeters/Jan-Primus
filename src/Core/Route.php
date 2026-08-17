<?php

declare(strict_types=1);

namespace AEFS\Core;

use InvalidArgumentException;

final class Route
{
    /**
     * @var callable|array{0: class-string, 1: string}
     */
    private readonly mixed $action;

    /**
     * @var list<class-string>
     */
    private array $middleware = [];

    /**
     * @var array<string, string>
     */
    private array $parameters = [];

    private ?string $name = null;

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param list<class-string> $middleware
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        mixed $action,
        array $middleware = [],
        ?string $name = null
    ) {
        $this->action = $action;
        $this->middleware = $middleware;
        $this->name = $name;
    }

    public function middleware(string ...$middleware): self
    {
        $this->middleware = array_values(
            array_unique([
                ...$this->middleware,
                ...$middleware,
            ])
        );

        return $this;
    }

    /**
     * @return list<class-string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function name(string $name): self
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException(
                'Routenaam mag niet leeg zijn.'
            );
        }

        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array<string, string>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function parameter(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->parameters[$key] ?? $default;
    }

    public function hasParameter(string $key): bool
    {
        return array_key_exists(
            $key,
            $this->parameters
        );
    }

    public function matches(string $uri): bool
    {
        return $this->compile($uri) !== null;
    }

    /**
     * @return array<string, string>|null
     */
    public function compile(string $uri): ?array
    {
        $routeUri = $this->normalizeUri($this->uri);
        $requestUri = $this->normalizeUri($uri);

        $parameterNames = [];

        $pattern = preg_replace_callback(
            '/\{([A-Za-z_][A-Za-z0-9_]*)\}/',
            static function (array $matches) use (&$parameterNames): string {
                $parameterNames[] = $matches[1];

                return '___ROUTE_PARAMETER___';
            },
            $routeUri
        );

        if ($pattern === null) {
            return null;
        }

        $pattern = preg_quote(
            $pattern,
            '#'
        );

        $pattern = str_replace(
            preg_quote('___ROUTE_PARAMETER___', '#'),
            '([^/]+)',
            $pattern
        );

        if (
            preg_match(
                '#^' . $pattern . '$#',
                $requestUri,
                $matches
            ) !== 1
        ) {
            return null;
        }

        array_shift($matches);

        $parameters = [];

        foreach ($parameterNames as $index => $name) {
            if (!isset($matches[$index])) {
                continue;
            }

            $parameters[$name] = rawurldecode(
                (string) $matches[$index]
            );
        }

        return $parameters;
    }

    public function allows(string $method): bool
    {
        $allowedMethods = array_map(
            'strtoupper',
            explode('|', $this->method)
        );

        return in_array(
            strtoupper($method),
            $allowedMethods,
            true
        );
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function action(): mixed
    {
        return $this->action;
    }

    public function method(): string
    {
        return $this->method;
    }

    private function normalizeUri(string $uri): string
    {
        $uri = '/' . ltrim(
            trim($uri),
            '/'
        );

        if ($uri === '/') {
            return '/';
        }

        return rtrim($uri, '/');
    }
}