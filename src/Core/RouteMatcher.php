<?php

declare(strict_types=1);

namespace AEFS\Core;

final class RouteMatcher
{
    public function match(
        RouteCollection $routes,
        string $method,
        string $uri
    ): ?RouteMatch {
        $uri = '/' . trim($uri, '/');

        foreach ($routes as $route) {

            if (!$route->allows($method)) {
                continue;
            }

            $parameters = [];

            if ($this->matches($route, $uri, $parameters)) {
                return new RouteMatch(
                    $route,
                    new RouteParameterBag($parameters)
                );
            }
        }

        return null;
    }

    /**
     * @param array<string,string> $parameters
     */
    private function matches(
        Route $route,
        string $uri,
        array &$parameters
    ): bool {
        $pattern = preg_replace_callback(
            '/\{([A-Za-z0-9_]+)\}/',
            static function (array $match): string {
                return '(?P<' . $match[1] . '>[^/]+)';
            },
            $route->uri()
        );

        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $uri, $matches)) {
            return false;
        }

        foreach ($matches as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $parameters[$key] = $value;
        }

        foreach ($route->constraints() as $name => $regex) {
            if (
                isset($parameters[$name]) &&
                preg_match('#^' . $regex . '$#', $parameters[$name]) !== 1
            ) {
                return false;
            }
        }

        return true;
    }
}