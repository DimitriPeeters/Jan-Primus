<?php

declare(strict_types=1);

namespace AEFS\Core;

use AEFS\Exceptions\ExceptionHandler;
use AEFS\HTTP\Request;
use AEFS\HTTP\Response;
use AEFS\Routing\Router;
use Throwable;

final class Kernel
{
    public function __construct(
        private readonly Application $application,
        private readonly Router $router,
        private readonly ExceptionHandler $exceptionHandler
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (Throwable $exception) {
            return $this->exceptionHandler->render($request, $exception);
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        unset($request, $response);
    }
}