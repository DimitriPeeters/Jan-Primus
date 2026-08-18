<?php

declare(strict_types=1);

namespace App\Middleware;

use AEFS\Core\Config;
use AEFS\Core\Http\JsonResponse;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;

final class MailWorkerSchedulerMiddleware implements MiddlewareInterface
{
    public const TOKEN_HEADER = 'X-AEFS-Worker-Token';

    private const TOKEN_PATTERN = '/^[a-f0-9]{64}$/i';

    public function __construct(
        private readonly Config $config
    ) {
    }

    public function handle(
        Request $request,
        callable $next
    ): Response {
        if (!(bool) $this->config->get('mail_worker.enabled', false)) {
            return $this->error('Niet gevonden.', 404);
        }

        $expectedToken = trim(
            (string) $this->config->get('mail_worker.token', '')
        );

        if (preg_match(self::TOKEN_PATTERN, $expectedToken) !== 1) {
            return $this->error(
                'De scheduler is niet correct geconfigureerd.',
                503
            );
        }

        if ($request->scheme() !== 'https') {
            return $this->error('HTTPS is verplicht.', 403);
        }

        $providedToken = trim(
            (string) $request->header(self::TOKEN_HEADER, '')
        );

        if (
            preg_match(self::TOKEN_PATTERN, $providedToken) !== 1
            || !hash_equals($expectedToken, $providedToken)
        ) {
            return $this->error('Niet geautoriseerd.', 401);
        }

        $response = $next($request);

        if (!$response instanceof Response) {
            $response = new Response((string) $response);
        }

        return $this->secure($response);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return $this->secure(
            JsonResponse::error(
                message: $message,
                statusCode: $status
            )
        );
    }

    private function secure(Response $response): Response
    {
        return $response
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
