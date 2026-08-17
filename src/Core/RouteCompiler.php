<?php

declare(strict_types=1);

namespace AEFS\Core;

final class RouteCompiler
{
    /**
     * Compile a route URI into a regular expression.
     */
    public function compile(Route $route): string
    {
        $uri = $route->uri();

        $pattern = preg_replace_callback(
            '/\{([A-Za-z_][A-Za-z0-9_]*)\}/',
            static function (array $matches) use ($route): string {
                $parameter = $matches[1];

                $constraints = $route->constraints();

                $expression = $constraints[$parameter] ?? '[^/]+';

                return sprintf(
                    '(?P<%s>%s)',
                    $parameter,
                    $expression
                );
            },
            $uri
        );

        return '#^' . $pattern . '$#u';
    }

    /**
     * @return array<string,string>
     */
    public function extract(string $pattern, string $uri): array
    {
        if (preg_match($pattern, $uri, $matches) !== 1) {
            return [];
        }

        $parameters = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

    public function matches(Route $route, string $uri): bool
    {
        return preg_match(
            $this->compile($route),
            $uri
        ) === 1;
    }
}