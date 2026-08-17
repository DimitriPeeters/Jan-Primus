<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\Http\Response;
use AEFS\Core\View\Composer\ComposerDefinition;
use AEFS\Core\View\Composer\ViewComposerInterface;
use AEFS\Core\View\Composer\ViewComposerRegistry;
use Closure;

final readonly class ViewManager
{
    public function __construct(
        private ViewEngineInterface $engine,
        private ViewFinder $finder,
        private ViewComposerRegistry $composers
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(
        string $view,
        array $data = []
    ): string {
        return $this->engine->render(
            $view,
            $data
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function response(
        string $view,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): Response {
        return $this->engine->response(
            $view,
            $data,
            $status,
            $headers
        );
    }

    public function exists(
        string $view
    ): bool {
        return $this->finder->exists(
            $view
        );
    }

    public function addPath(
        string $path
    ): void {
        $this->finder->addPath(
            $path
        );
    }

    public function prependPath(
        string $path
    ): void {
        $this->finder->prependPath(
            $path
        );
    }

    public function addNamespace(
        string $namespace,
        string $path
    ): void {
        $this->finder->addNamespace(
            $namespace,
            $path
        );
    }

    public function removeNamespace(
        string $namespace
    ): void {
        $this->finder->removeNamespace(
            $namespace
        );
    }

    public function flushFinderCache(): void
    {
        $this->finder->flushCache();
    }

    public function share(
        string $key,
        mixed $value
    ): void {
        $this->engine->share(
            $key,
            $value
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function shareMany(
        array $data
    ): void {
        $this->engine->shareMany(
            $data
        );
    }

    public function composer(
        string $view,
        ViewComposerInterface|Closure $composer
    ): void {
        $this->composers->add(
            $view,
            $composer
        );
    }

    /**
     * @param list<string> $views
     */
    public function composers(
        array $views,
        ViewComposerInterface|Closure $composer
    ): void {
        $this->composers->addMany(
            $views,
            $composer
        );
    }

    public function composerDefinition(
        ComposerDefinition $definition
    ): void {
        $this->composers->addDefinition(
            $definition
        );
    }

    public function composerGlobal(
        ViewComposerInterface|Closure $composer
    ): void {
        $this->composers->addGlobal(
            $composer
        );
    }
}