<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\Http\Response;
use InvalidArgumentException;

final readonly class ViewFactory
{
    public function __construct(
        private ViewEngineInterface $engine
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(
        string $view,
        array $data = []
    ): string {
        return $this->engine->render(
            $view,
            $data
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function response(
        string $view,
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

        return $this->engine->response(
            $view,
            $data,
            $status,
            $headers
        );
    }

    public function exists(string $view): bool
    {
        return $this->engine->exists($view);
    }

    public function share(
        string $key,
        mixed $value
    ): void {
        $this->engine->share(
            $key,
            $value
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function shareMany(array $data): void
    {
        $this->engine->shareMany($data);
    }
}