<?php

declare(strict_types=1);

namespace AEFS\Core\View\Component;

interface ComponentInterface
{
    /**
     * @return array<string, mixed>
     */
    public function data(): array;

    public function view(): string;
}