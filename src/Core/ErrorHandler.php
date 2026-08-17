<?php

declare(strict_types=1);

namespace AEFS\Core;

use Throwable;

final class ErrorHandler
{
    public function __construct(
        private readonly Logger $logger,
        private readonly EnvironmentDetector $environment
    ) {
    }

    public function register(): void
    {
        error_reporting(E_ALL);

        ini_set(
            'display_errors',
            $this->environment->isDevelopment() ? '1' : '0'
        );

        set_error_handler(
            [$this, 'handleError']
        );

        set_exception_handler(
            [$this, 'handleException']
        );

        register_shutdown_function(
            [$this, 'handleShutdown']
        );
    }

    public function handleError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException(
            $message,
            0,
            $severity,
            $file,
            $line
        );
    }

    public function handleException(Throwable $exception): void
    {
        $this->logger->error(
            $exception->getMessage(),
            [
                'exception' => get_class($exception),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'trace'     => $exception->getTraceAsString(),
            ]
        );

        http_response_code(500);

        if ($this->environment->isDevelopment()) {
            echo '<pre>';
            echo htmlspecialchars((string) $exception, ENT_QUOTES);
            echo '</pre>';

            return;
        }

        echo 'Internal Server Error';
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        if (!in_array(
            $error['type'],
            [
                E_ERROR,
                E_CORE_ERROR,
                E_COMPILE_ERROR,
                E_PARSE,
            ],
            true
        )) {
            return;
        }

        $this->logger->critical(
            $error['message'],
            [
                'file' => $error['file'],
                'line' => $error['line'],
            ]
        );

        http_response_code(500);

        if ($this->environment->isDevelopment()) {
            echo '<pre>';
            print_r($error);
            echo '</pre>';

            return;
        }

        echo 'Internal Server Error';
    }
}