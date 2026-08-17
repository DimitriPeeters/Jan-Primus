<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use RuntimeException;

final class ViewNotFoundException extends RuntimeException
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        private readonly string $view,
        private readonly array $paths
    ) {
        parent::__construct(
            sprintf(
                'View [%s] niet gevonden in: %s',
                $view,
                $paths === [] ? '[geen viewpaden]' : implode(', ', $paths)
            )
        );
    }

    public function view(): string
    {
        return $this->view;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }
}