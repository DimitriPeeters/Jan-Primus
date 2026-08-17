<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

final class CookieBag
{
    /**
     * @var array<string, Cookie>
     */
    private array $cookies = [];

    /**
     * @param array<string, string> $cookies
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $name => $value) {
            $this->cookies[$name] = new Cookie(
                name: $name,
                value: (string) $value
            );
        }
    }

    public function set(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): void {
        $this->cookies[$name] = new Cookie(
            name: $name,
            value: $value,
            expires: $expires,
            path: $path,
            domain: $domain,
            secure: $secure,
            httpOnly: $httpOnly,
            sameSite: $sameSite
        );
    }

    public function get(string $name): ?Cookie
    {
        return $this->cookies[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    public function remove(string $name): void
    {
        unset($this->cookies[$name]);
    }

    /**
     * @return array<string, Cookie>
     */
    public function all(): array
    {
        return $this->cookies;
    }

    public function clear(): void
    {
        $this->cookies = [];
    }
}