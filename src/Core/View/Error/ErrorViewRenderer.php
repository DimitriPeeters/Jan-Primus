<?php

declare(strict_types=1);

namespace AEFS\Core\View\Error;

use AEFS\Core\Http\Response;
use AEFS\Core\View\ViewEngineInterface;
use Throwable;

final class ErrorViewRenderer
{
    public function __construct(
        private readonly ViewEngineInterface $view,
        private readonly bool $debug = false
    ) {
    }

    public function render(
        int $status,
        ?string $message = null,
        ?Throwable $exception = null
    ): Response {
        $view = sprintf('core::errors.%d', $status);

        if (!$this->view->exists($view)) {
            $view = 'core::errors.500';
            $status = 500;
        }

        return $this->view->response(
            $view,
            [
                'message' => $message,
                'exception' => $exception,
                'debug' => $this->debug,
            ],
            $status
        );
    }
}