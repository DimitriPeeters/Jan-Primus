<?php

declare(strict_types=1);

namespace AEFS\Core\View\Composer;

use Closure;

final class CallbackViewComposer implements ViewComposerInterface
{
    /**
     * @param Closure(string, array<string, mixed>): array<string, mixed> $callback
     */
    public function __construct(
        private readonly Closure $callback
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function compose(string $view, array $data): array
    {
        return ($this->callback)($view, $data);
    }
}