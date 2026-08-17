<?php

declare(strict_types=1);

namespace AEFS\Core;

use RuntimeException;

final class Config
{
    /**
     * @var array<string, mixed>
     */
    private array $items = [];

    public function __construct(
        string $configPath
    ) {
        $this->load($configPath);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);

        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);

        $items =& $this->items;

        while (count($segments) > 1) {
            $segment = array_shift($segments);

            if (!isset($items[$segment]) || !is_array($items[$segment])) {
                $items[$segment] = [];
            }

            $items =& $items[$segment];
        }

        $items[array_shift($segments)] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    private function load(string $configPath): void
    {
        if (!is_dir($configPath)) {
            throw new RuntimeException(sprintf(
                'Configuration directory [%s] does not exist.',
                $configPath
            ));
        }

        $files = glob($configPath . DIRECTORY_SEPARATOR . '*.php');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);

            $config = require $file;

            if (!is_array($config)) {
                throw new RuntimeException(sprintf(
                    'Configuration file [%s] must return an array.',
                    basename($file)
                ));
            }

            $this->items[$key] = $config;
        }
    }
}