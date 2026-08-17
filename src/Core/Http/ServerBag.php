<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

final class ServerBag extends ParameterBag
{
    public function method(): string
    {
        return strtoupper(
            (string) $this->get('REQUEST_METHOD', 'GET')
        );
    }

    public function uri(): string
    {
        return (string) $this->get('REQUEST_URI', '/');
    }

    public function path(): string
    {
        $path = parse_url($this->uri(), PHP_URL_PATH);

        return $path === null || $path === false || $path === ''
            ? '/'
            : $path;
    }

    public function queryString(): string
    {
        return (string) $this->get('QUERY_STRING', '');
    }

    public function host(): string
    {
        return (string) $this->get('HTTP_HOST', 'localhost');
    }

    public function scheme(): string
    {
        if (
            $this->get('HTTPS') === 'on' ||
            $this->get('HTTPS') === '1'
        ) {
            return 'https';
        }

        if (
            (int) $this->get('SERVER_PORT', 80) === 443
        ) {
            return 'https';
        }

        if (
            strtolower((string) $this->get('HTTP_X_FORWARDED_PROTO', '')) === 'https'
        ) {
            return 'https';
        }

        return 'http';
    }

    public function port(): int
    {
        return (int) $this->get(
            'SERVER_PORT',
            $this->scheme() === 'https' ? 443 : 80
        );
    }

    public function ip(): string
    {
        $forwarded = $this->get('HTTP_X_FORWARDED_FOR');

        if (is_string($forwarded) && $forwarded !== '') {
            return trim(explode(',', $forwarded)[0]);
        }

        return (string) $this->get('REMOTE_ADDR', '127.0.0.1');
    }

    public function userAgent(): string
    {
        return (string) $this->get('HTTP_USER_AGENT', '');
    }

    public function referer(): ?string
    {
        return $this->get('HTTP_REFERER');
    }

    public function isSecure(): bool
    {
        return $this->scheme() === 'https';
    }

    public function isAjax(): bool
    {
        return strtolower(
            (string) $this->get('HTTP_X_REQUESTED_WITH', '')
        ) === 'xmlhttprequest';
    }

    public function acceptsJson(): bool
    {
        return str_contains(
            strtolower((string) $this->get('HTTP_ACCEPT', '')),
            'application/json'
        );
    }

    public function bearerToken(): ?string
    {
        $header = (string) $this->get('HTTP_AUTHORIZATION', '');

        if (
            preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1
        ) {
            return trim($matches[1]);
        }

        return null;
    }
}