<?php

declare(strict_types=1);

namespace App\Middleware;

use AEFS\Core\Auth;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\View\ViewFactory;

final class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ViewFactory $views
    ) {
    }

    public function handle(
        Request $request,
        callable $next
    ): Response {
        if (!Auth::isAdmin()) {
            return $this->views->response(
                'core::errors.403',
                [
                    'message' => 'Deze pagina is uitsluitend toegankelijk voor administrators.',
                ],
                403
            );
        }

        $response = $next($request);

        return $response instanceof Response
            ? $response
            : new Response((string) $response);
    }
}
