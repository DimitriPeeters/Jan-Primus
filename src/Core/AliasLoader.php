<?php

declare(strict_types=1);

namespace AEFS\Core;

use RuntimeException;

final class AliasLoader
{
    /**
     * @var array<string, class-string>
     */
    private array $aliases = [];

    private bool $registered = false;

    /**
     * @param array<string, class-string> $aliases
     */
    public function __construct(array $aliases = [])
    {
        $this->aliases = $aliases;
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        spl_autoload_register($this->loadAlias(...), true, true);

        $this->registered = true;
    }

    public function alias(string $alias, string $class): void
    {
        $this->aliases[$alias] = $class;
    }

    /**
     * @return array<string, class-string>
     */
    public function all(): array
    {
        return $this->aliases;
    }

    public function has(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    private function loadAlias(string $alias): void
    {
        if (!isset($this->aliases[$alias])) {
            return;
        }

        $class = $this->aliases[$alias];

        if (!class_exists($class)) {
            throw new RuntimeException(
                sprintf(
                    'Unable to load alias [%s]. Target class [%s] not found.',
                    $alias,
                    $class
                )
            );
        }

        if (!class_exists($alias, false)) {
            class_alias($class, $alias);
        }
    }
}