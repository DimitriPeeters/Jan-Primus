<?php

declare(strict_types=1);

namespace AEFS\Core\View\Composer;

interface ViewComposerInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function compose(string $view, array $data): array;
}