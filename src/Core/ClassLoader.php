<?php

declare(strict_types=1);

namespace AEFS\Core;

use RuntimeException;

final class ClassLoader
{
    /**
     * @var array<string, string>
     */
    private array $prefixes = [];

    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        spl_autoload_register([$this, 'autoload'], true, true);

        $this->registered = true;
    }

    public function addNamespace(string $prefix, string $directory): void
    {
        $prefix = trim($prefix, '\\') . '\\';

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($directory)) {
            throw new RuntimeException(
                sprintf(
                    'Namespace directory [%s] does not exist.',
                    $directory
                )
            );
        }

        $this->prefixes[$prefix] = $directory;
    }

    /**
     * @return array<string,string>
     */
    public function namespaces(): array
    {
        return $this->prefixes;
    }

    public function autoload(string $class): void
    {
        foreach ($this->prefixes as $prefix => $directory) {

            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relative = substr($class, strlen($prefix));

            $file = $directory .
                str_replace('\\', DIRECTORY_SEPARATOR, $relative) .
                '.php';

            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }
}