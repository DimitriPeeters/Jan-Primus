<?php

declare(strict_types=1);

namespace AEFS\Core\View\Composer;

use Closure;
use InvalidArgumentException;

final class ViewComposerRegistry
{
    /**
     * @var array<string, list<ViewComposerInterface>>
     */
    private array $composers = [];

    /**
     * @var list<ViewComposerInterface>
     */
    private array $globalComposers = [];

    public function add(
        string $view,
        ViewComposerInterface|Closure $composer
    ): void {
        $view = $this->normalizeView($view);

        $this->composers[$view] ??= [];
        $this->composers[$view][] = $this->normalizeComposer(
            $composer
        );
    }

    /**
     * @param list<string> $views
     */
    public function addMany(
        array $views,
        ViewComposerInterface|Closure $composer
    ): void {
        $normalizedComposer = $this->normalizeComposer(
            $composer
        );

        foreach ($views as $view) {
            $view = $this->normalizeView($view);

            $this->composers[$view] ??= [];
            $this->composers[$view][] = $normalizedComposer;
        }
    }

    public function addDefinition(
        ComposerDefinition $definition
    ): void {
        $this->addMany(
            $definition->views,
            $definition->composer
        );
    }

    public function addGlobal(
        ViewComposerInterface|Closure $composer
    ): void {
        $this->globalComposers[] = $this->normalizeComposer(
            $composer
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function compose(
        string $view,
        array $data
    ): array {
        foreach ($this->globalComposers as $composer) {
            $data = array_replace(
                $data,
                $composer->compose(
                    $view,
                    $data
                )
            );
        }

        foreach ($this->matchingComposers($view) as $composer) {
            $data = array_replace(
                $data,
                $composer->compose(
                    $view,
                    $data
                )
            );
        }

        return $data;
    }

    public function clear(): void
    {
        $this->composers = [];
        $this->globalComposers = [];
    }

    /**
     * @return list<ViewComposerInterface>
     */
    private function matchingComposers(
        string $view
    ): array {
        $matching = [];

        foreach ($this->composers as $pattern => $composers) {
            if (!$this->matches($pattern, $view)) {
                continue;
            }

            foreach ($composers as $composer) {
                $matching[] = $composer;
            }
        }

        return $matching;
    }

    private function matches(
        string $pattern,
        string $view
    ): bool {
        if ($pattern === '*' || $pattern === $view) {
            return true;
        }

        $expression = preg_quote(
            $pattern,
            '/'
        );

        $expression = str_replace(
            '\*',
            '.*',
            $expression
        );

        return preg_match(
            '/^' . $expression . '$/',
            $view
        ) === 1;
    }

    private function normalizeComposer(
        ViewComposerInterface|Closure $composer
    ): ViewComposerInterface {
        if ($composer instanceof ViewComposerInterface) {
            return $composer;
        }

        return new CallbackViewComposer(
            $composer
        );
    }

    private function normalizeView(
        string $view
    ): string {
        $view = trim($view);

        if ($view === '') {
            throw new InvalidArgumentException(
                'Viewnaam voor composer mag niet leeg zijn.'
            );
        }

        return $view;
    }
}