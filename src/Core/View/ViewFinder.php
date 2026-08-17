<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use InvalidArgumentException;

final class ViewFinder
{
    /**
     * @var list<string>
     */
    private array $paths = [];

    /**
     * @var array<string, string>
     */
    private array $namespaces = [];

    /**
     * @var array<string, string>
     */
    private array $resolvedViews = [];

    /**
     * @param list<string> $paths
     */
    public function __construct(array $paths = [])
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
    }

    public function addPath(string $path): void
    {
        $path = $this->normalizeDirectory($path);

        if (in_array($path, $this->paths, true)) {
            return;
        }

        $this->paths[] = $path;
        $this->flushCache();
    }

    public function prependPath(string $path): void
    {
        $path = $this->normalizeDirectory($path);

        $this->paths = array_values(
            array_filter(
                $this->paths,
                static fn (string $existingPath): bool => $existingPath !== $path
            )
        );

        array_unshift($this->paths, $path);

        $this->flushCache();
    }

    public function addNamespace(
        string $namespace,
        string $path
    ): void {
        $namespace = trim($namespace);

        if ($namespace === '') {
            throw new InvalidArgumentException(
                'View namespace mag niet leeg zijn.'
            );
        }

        if (
            str_contains($namespace, '.')
            || str_contains($namespace, '/')
            || str_contains($namespace, '\\')
            || str_contains($namespace, '::')
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Ongeldige view namespace [%s].',
                    $namespace
                )
            );
        }

        $this->namespaces[$namespace] = $this->normalizeDirectory($path);

        $this->flushCache();
    }

    public function removeNamespace(string $namespace): void
    {
        unset($this->namespaces[$namespace]);

        $this->flushCache();
    }

    public function find(string $view): string
    {
        $view = $this->normalizeViewName($view);

        if (isset($this->resolvedViews[$view])) {
            return $this->resolvedViews[$view];
        }

        $file = str_contains($view, '::')
            ? $this->findNamespacedView($view)
            : $this->findStandardView($view);

        $this->resolvedViews[$view] = $file;

        return $file;
    }

    public function exists(string $view): bool
    {
        try {
            $this->find($view);

            return true;
        } catch (
            ViewNotFoundException
            | InvalidArgumentException
        ) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    /**
     * @return array<string, string>
     */
    public function namespaces(): array
    {
        return $this->namespaces;
    }

    public function flushCache(): void
    {
        $this->resolvedViews = [];
    }

    private function findStandardView(string $view): string
    {
        $relativePath = $this->toRelativePath($view);

        foreach ($this->paths as $path) {
            $candidate = $path
                . DIRECTORY_SEPARATOR
                . $relativePath;

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new ViewNotFoundException(
            $view,
            $this->paths
        );
    }

    private function findNamespacedView(string $view): string
    {
        [$namespace, $name] = explode('::', $view, 2);

        if (
            $namespace === ''
            || $name === ''
            || !isset($this->namespaces[$namespace])
        ) {
            throw new ViewNotFoundException(
                $view,
                array_values($this->namespaces)
            );
        }

        $relativePath = $this->toRelativePath($name);
        $candidate = $this->namespaces[$namespace]
            . DIRECTORY_SEPARATOR
            . $relativePath;

        if (!is_file($candidate)) {
            throw new ViewNotFoundException(
                $view,
                [$this->namespaces[$namespace]]
            );
        }

        return $candidate;
    }

    private function normalizeDirectory(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new InvalidArgumentException(
                'Viewpad mag niet leeg zijn.'
            );
        }

        $path = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $path
        );

        return rtrim(
            $path,
            DIRECTORY_SEPARATOR
        );
    }

    private function normalizeViewName(string $view): string
    {
        $view = trim($view);

        if ($view === '') {
            throw new InvalidArgumentException(
                'Viewnaam mag niet leeg zijn.'
            );
        }

        if (
            str_contains($view, '..')
            || str_contains($view, "\0")
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Ongeldige viewnaam [%s].',
                    $view
                )
            );
        }

        return $view;
    }

    private function toRelativePath(string $view): string
    {
        $view = str_replace(
            ['/', '\\'],
            '.',
            $view
        );

        $view = trim($view, '.');

        if ($view === '') {
            throw new InvalidArgumentException(
                'Viewnaam mag niet leeg zijn.'
            );
        }

        $segments = explode('.', $view);

        foreach ($segments as $segment) {
            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Ongeldige viewnaam [%s].',
                        $view
                    )
                );
            }
        }

        return implode(
            DIRECTORY_SEPARATOR,
            $segments
        ) . '.php';
    }
}