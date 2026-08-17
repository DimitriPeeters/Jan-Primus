<?php

declare(strict_types=1);

namespace AEFS\Core;

use InvalidArgumentException;

final class MaintenanceMode
{
    private const FILE = 'maintenance.php';

    public function __construct(
        private readonly Application $application
    ) {
    }

    public function enabled(): bool
    {
        return is_file($this->file());
    }

    public function enable(
        int $statusCode = 503,
        string $message = 'Service Unavailable',
        ?int $retryAfter = null
    ): void {
        if ($statusCode < 500 || $statusCode > 599) {
            throw new InvalidArgumentException(
                'Maintenance status code must be between 500 and 599.'
            );
        }

        $data = [
            'enabled_at' => date('c'),
            'status' => $statusCode,
            'message' => $message,
            'retry_after' => $retryAfter,
        ];

        $content = sprintf(
            "<?php\n\ndeclare(strict_types=1);\n\nreturn %s;\n",
            var_export($data, true)
        );

        file_put_contents(
            $this->file(),
            $content,
            LOCK_EX
        );
    }

    public function disable(): void
    {
        if ($this->enabled()) {
            unlink($this->file());
        }
    }

    /**
     * @return array{
     *     enabled_at:string,
     *     status:int,
     *     message:string,
     *     retry_after:int|null
     * }
     */
    public function data(): array
    {
        if (!$this->enabled()) {
            return [
                'enabled_at' => '',
                'status' => 503,
                'message' => 'Service Unavailable',
                'retry_after' => null,
            ];
        }

        /** @var array $data */
        $data = require $this->file();

        return $data;
    }

    private function file(): string
    {
        return $this->application->storagePath(
            'framework' .
            DIRECTORY_SEPARATOR .
            self::FILE
        );
    }
}