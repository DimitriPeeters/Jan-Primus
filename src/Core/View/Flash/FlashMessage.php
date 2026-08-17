<?php

declare(strict_types=1);

namespace AEFS\Core\View\Flash;

final readonly class FlashMessage
{
    public function __construct(
        public string $type,
        public string $message
    ) {
    }
}