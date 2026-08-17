<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

use AEFS\Session\Session;

trait InteractsWithInput
{
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request->input($key, $default);
    }

    public function all(): array
    {
        return $this->request->all();
    }

    public function only(array $keys): array
    {
        return $this->request->only($keys);
    }

    public function except(array $keys): array
    {
        return $this->request->except($keys);
    }

    public function has(string $key): bool
    {
        return $this->request->input($key) !== null;
    }

    public function filled(string $key): bool
    {
        $value = $this->request->input($key);

        return $value !== null
            && $value !== ''
            && $value !== [];
    }

    public function boolean(string $key): bool
    {
        return filter_var(
            $this->request->input($key),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->request->input($key, $default);
    }

    public function float(string $key, float $default = 0): float
    {
        return (float) $this->request->input($key, $default);
    }

    public function string(string $key, string $default = ''): string
    {
        return (string) $this->request->input($key, $default);
    }

    public function array(string $key): array
    {
        $value = $this->request->input($key);

        return is_array($value)
            ? $value
            : [];
    }

    public function old(string $key, mixed $default = null): mixed
    {
        if (!property_exists($this, 'session')) {
            return $default;
        }

        /** @var Session $session */
        $session = $this->session;

        return $session->getOldInput($key, $default);
    }
}