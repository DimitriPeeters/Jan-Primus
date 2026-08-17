<?php

declare(strict_types=1);

namespace AEFS\Core\View\Concerns;

use AEFS\Core\Http\Response;
use AEFS\Core\View\ViewFactory;
use LogicException;

trait RendersViews
{
    private ?ViewFactory $viewFactory = null;

    final public function setViewFactory(
        ViewFactory $viewFactory
    ): void {
        $this->viewFactory = $viewFactory;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    final protected function view(
        string $view,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): Response {
        return $this->views()->response(
            $view,
            $data,
            $status,
            $headers
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    final protected function renderView(
        string $view,
        array $data = []
    ): string {
        return $this->views()->render(
            $view,
            $data
        );
    }

    final protected function viewExists(
        string $view
    ): bool {
        return $this->views()->exists($view);
    }

    final protected function shareViewData(
        string $key,
        mixed $value
    ): void {
        $this->views()->share(
            $key,
            $value
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    final protected function shareViewDataMany(
        array $data
    ): void {
        $this->views()->shareMany($data);
    }

    private function views(): ViewFactory
    {
        if (!$this->viewFactory instanceof ViewFactory) {
            throw new LogicException(
                sprintf(
                    'ViewFactory werd niet ingesteld op controller [%s].',
                    static::class
                )
            );
        }

        return $this->viewFactory;
    }
}