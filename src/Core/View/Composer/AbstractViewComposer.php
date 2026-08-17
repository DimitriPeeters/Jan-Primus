<?php

declare(strict_types=1);

namespace AEFS\Core\View\Composer;

abstract class AbstractViewComposer implements ViewComposerInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    final public function compose(
        string $view,
        array $data
    ): array {
        return $this->data(
            $view,
            $data
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    abstract protected function data(
        string $view,
        array $data
    ): array;
}