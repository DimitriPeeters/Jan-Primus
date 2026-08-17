<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

final class UrlHelper
{
    public function __construct(
        private readonly string $baseUrl = ''
    ) {
    }

    /**
     * @param array<string, scalar|null> $query
     */
    public function to(
        string $path = '',
        array $query = []
    ): string {
        $baseUrl = rtrim($this->baseUrl, '/');
        $path = trim($path);

        if ($path === '') {
            $url = $baseUrl !== '' ? $baseUrl : '/';
        } elseif ($this->isAbsolute($path)) {
            $url = $path;
        } else {
            $url = $baseUrl . '/' . ltrim($path, '/');
        }

        if ($query !== []) {
            $queryString = http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            );

            if ($queryString !== '') {
                $url .= str_contains($url, '?') ? '&' : '?';
                $url .= $queryString;
            }
        }

        return $url;
    }

    /**
     * @param array<string, scalar|null> $query
     */
    public function current(array $query = []): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        return $this->to($path, $query);
    }

    public function previous(string $fallback = '/'): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        if (!is_string($referer) || trim($referer) === '') {
            return $this->to($fallback);
        }

        return $referer;
    }

    private function isAbsolute(string $path): bool
    {
        return str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '//');
    }
}