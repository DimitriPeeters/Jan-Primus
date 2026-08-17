<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\Http\Response;
use InvalidArgumentException;

final readonly class ViewResponseFactory
{
    public function __construct(
        private ViewEngineInterface $view
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function make(
        string $name,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): Response {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException(
                sprintf(
                    'Ongeldige HTTP-statuscode [%d].',
                    $status
                )
            );
        }

        return $this->view->response(
            $name,
            $data,
            $status,
            $headers
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function success(
        string $name,
        array $data = [],
        array $headers = []
    ): Response {
        return $this->make(
            $name,
            $data,
            200,
            $headers
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function created(
        string $name,
        array $data = [],
        array $headers = []
    ): Response {
        return $this->make(
            $name,
            $data,
            201,
            $headers
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function forbidden(
        string $name = 'core::errors.403',
        array $data = [],
        array $headers = []
    ): Response {
        return $this->make(
            $name,
            $data,
            403,
            $headers
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function notFound(
        string $name = 'core::errors.404',
        array $data = [],
        array $headers = []
    ): Response {
        return $this->make(
            $name,
            $data,
            404,
            $headers
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function unprocessable(
        string $name = 'core::errors.422',
        array $data = [],
        array $headers = []
    ): Response {
        return $this->make(
            $name,
            $data,
            422,
            $headers
        );
    }
}