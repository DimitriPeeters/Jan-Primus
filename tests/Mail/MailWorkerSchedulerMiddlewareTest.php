<?php

declare(strict_types=1);

namespace Tests\Mail;

use AEFS\Core\Config;
use AEFS\Core\Http\JsonResponse;
use AEFS\Core\Http\Request;
use App\Middleware\MailWorkerSchedulerMiddleware;
use RuntimeException;

final readonly class MailWorkerSchedulerMiddlewareTest
{
    private const TOKEN = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    public function __construct(
        private Config $config
    ) {
    }

    public function run(): void
    {
        $this->assertDenied(enabled: false, token: self::TOKEN, status: 404);
        $this->assertDenied(enabled: true, token: 'te-kort', status: 503);
        $this->assertDenied(
            enabled: true,
            token: self::TOKEN,
            status: 403,
            secure: false,
            providedToken: self::TOKEN
        );
        $this->assertDenied(enabled: true, token: self::TOKEN, status: 401);
        $this->assertDenied(
            enabled: true,
            token: self::TOKEN,
            status: 401,
            providedToken: str_repeat('f', 64)
        );
        $this->assertAllowed();
    }

    private function assertDenied(
        bool $enabled,
        string $token,
        int $status,
        bool $secure = true,
        ?string $providedToken = null
    ): void {
        $this->config->set('mail_worker.enabled', $enabled);
        $this->config->set('mail_worker.token', $token);
        $called = false;
        $middleware = new MailWorkerSchedulerMiddleware($this->config);
        $response = $middleware->handle(
            $this->request($secure, $providedToken),
            static function () use (&$called): JsonResponse {
                $called = true;

                return JsonResponse::success();
            }
        );

        if ($called || $response->status() !== $status) {
            throw new RuntimeException(
                sprintf('Verwachte afwijzing met HTTP-status %d bleef uit.', $status)
            );
        }

        if (
            $response->headers()->get('cache-control') !== 'no-store, private'
            || $response->headers()->get('x-content-type-options') !== 'nosniff'
        ) {
            throw new RuntimeException(
                'Een afgewezen schedulerrequest mist beveiligingsheaders.'
            );
        }

        if (str_contains($response->content(), self::TOKEN)) {
            throw new RuntimeException(
                'De schedulerresponse mag de geconfigureerde token nooit bevatten.'
            );
        }
    }

    private function assertAllowed(): void
    {
        $this->config->set('mail_worker.enabled', true);
        $this->config->set('mail_worker.token', self::TOKEN);
        $called = false;
        $middleware = new MailWorkerSchedulerMiddleware($this->config);
        $response = $middleware->handle(
            $this->request(true, self::TOKEN),
            static function () use (&$called): JsonResponse {
                $called = true;

                return JsonResponse::success(
                    message: 'Testverwerking geslaagd.',
                    statusCode: 202
                );
            }
        );

        if (!$called || $response->status() !== 202) {
            throw new RuntimeException(
                'Een geldig schedulerrequest werd niet doorgelaten.'
            );
        }
    }

    private function request(bool $secure, ?string $token): Request
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/internal/mail-worker/process',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_PORT' => $secure ? 443 : 80,
            'HTTPS' => $secure ? 'on' : 'off',
        ];

        if ($token !== null) {
            $server['HTTP_X_AEFS_WORKER_TOKEN'] = $token;
        }

        return new Request(server: $server);
    }
}
