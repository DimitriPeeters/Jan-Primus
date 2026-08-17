<?php

declare(strict_types=1);

namespace App\Middleware;

use AEFS\Core\Auth;
use AEFS\Core\Http\RedirectResponse;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Url;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(
        Request $request,
        callable $next
    ): Response {
        if (Auth::guest()) {
            return new RedirectResponse(
                Url::to('/login')
            );
        }

        $response = $next($request);

        return $response instanceof Response
            ? $response
            : new Response((string) $response);
    }
}
