<?php

declare(strict_types=1);

namespace AEFS\Core\View\Component;

abstract class AbstractComponent implements ComponentInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        protected readonly array $data = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}