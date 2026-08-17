<?php

declare(strict_types=1);

namespace AEFS\Core;

final class Environment
{
    /**
     * @var array<string,string>
     */
    private array $variables = [];

    public function __construct(string $path)
    {
        $this->load($path);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->variables[$key]
            ?? $_ENV[$key]
            ?? $_SERVER[$key]
            ?? getenv($key)
            ?: $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->variables)
            || array_key_exists($key, $_ENV)
            || array_key_exists($key, $_SERVER);
    }

    /**
     * @return array<string,string>
     */
    public function all(): array
    {
        return $this->variables;
    }

    private function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');

            $name = trim($name);
            $value = trim($value);

            $value = trim($value, "\"'");
            $this->variables[$name] = $value;

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;

            putenv(sprintf('%s=%s', $name, $value));
        }
    }
}