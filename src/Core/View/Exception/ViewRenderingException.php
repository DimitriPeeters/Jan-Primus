<?php

declare(strict_types=1);

namespace AEFS\Core\View\Exception;

use RuntimeException;
use Throwable;

final class ViewRenderingException extends RuntimeException
{
    public function __construct(
        private readonly string $viewName,
        private readonly string $viewFile,
        Throwable $previous
    ) {
        parent::__construct(
            sprintf(
                'Fout tijdens het renderen van view [%s] uit bestand [%s]: %s',
                $viewName,
                $viewFile,
                $previous->getMessage()
            ),
            0,
            $previous
        );
    }

    public function view(): string
    {
        return $this->viewName;
    }

    public function viewFile(): string
    {
        return $this->viewFile;
    }
}