<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

use InvalidArgumentException;

final class Cookie
{
    private string $name;

    private string $value;

    private int $expires = 0;

    private ?int $maxAge = null;

    private string $path = '/';

    private string $domain = '';

    private bool $secure = false;

    private bool $httpOnly = true;

    private string $sameSite = 'Lax';

    public function __construct(
        string $name,
        string $value = ''
    ) {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException(
                'Cookie name may not be empty.'
            );
        }

        if (
            str_contains($name, '=')
            || str_contains($name, ';')
            || str_contains($name, ',')
            || preg_match('/[\x00-\x20\x7F]/', $name) === 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid cookie name [%s].',
                    $name
                )
            );
        }

        $this->name = $name;
        $this->value = $value;
    }

    public function value(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function expires(int $timestamp): self
    {
        $this->expires = max(0, $timestamp);

        return $this;
    }

    public function minutes(int $minutes): self
    {
        if ($minutes < 0) {
            throw new InvalidArgumentException(
                'Cookie lifetime in minutes may not be negative.'
            );
        }

        $this->expires = time() + ($minutes * 60);

        return $this;
    }

    public function forever(): self
    {
        $this->expires = time() + (60 * 60 * 24 * 365 * 5);

        return $this;
    }

    public function session(): self
    {
        $this->expires = 0;
        $this->maxAge = null;

        return $this;
    }

    public function maxAge(int $seconds): self
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException(
                'Cookie max-age may not be negative.'
            );
        }

        $this->maxAge = $seconds;

        if ($seconds > 0) {
            $this->expires = time() + $seconds;
        }

        return $this;
    }

    public function path(string $path): self
    {
        $path = trim($path);

        $this->path = $path === ''
            ? '/'
            : $path;

        return $this;
    }

    public function domain(string $domain): self
    {
        $this->domain = trim($domain);

        return $this;
    }

    public function secure(bool $secure = true): self
    {
        $this->secure = $secure;

        return $this;
    }

    public function httpOnly(bool $httpOnly = true): self
    {
        $this->httpOnly = $httpOnly;

        return $this;
    }

    public function sameSite(string $sameSite): self
    {
        $sameSite = ucfirst(
            strtolower(
                trim($sameSite)
            )
        );

        $allowed = [
            'Lax',
            'Strict',
            'None',
        ];

        if (!in_array($sameSite, $allowed, true)) {
            throw new InvalidArgumentException(
                'SameSite must be Lax, Strict or None.'
            );
        }

        if ($sameSite === 'None' && !$this->secure) {
            throw new InvalidArgumentException(
                'SameSite=None requires a secure cookie.'
            );
        }

        $this->sameSite = $sameSite;

        return $this;
    }

    public function send(): bool
    {
        return setcookie(
            $this->name,
            $this->value,
            $this->options()
        );
    }

    public function delete(): bool
    {
        $options = $this->options();
        $options['expires'] = time() - 3600;
        $options['max_age'] = 0;

        return setcookie(
            $this->name,
            '',
            $options
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function expiry(): int
    {
        return $this->expires;
    }

    public function getMaxAge(): ?int
    {
        return $this->maxAge;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * @return array{
     *     expires: int,
     *     path: string,
     *     domain: string,
     *     secure: bool,
     *     httponly: bool,
     *     samesite: string,
     *     max_age?: int
     * }
     */
    private function options(): array
    {
        $options = [
            'expires' => $this->expires,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ];

        if ($this->maxAge !== null) {
            $options['max_age'] = $this->maxAge;
        }

        return $options;
    }
}