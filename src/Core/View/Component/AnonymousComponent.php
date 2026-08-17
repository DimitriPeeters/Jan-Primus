<?php

declare(strict_types=1);

namespace AEFS\Core\View\Component;

use InvalidArgumentException;

final class AnonymousComponent implements ComponentInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $view,
        private readonly array $data = []
    ) {
        if (trim($this->view) === '') {
            throw new InvalidArgumentException(
                'Componentview mag niet leeg zijn.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function view(): string
    {
        return $this->view;
    }
}