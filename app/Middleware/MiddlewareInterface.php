<?php

declare(strict_types=1);

namespace App\Middleware;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;

interface MiddlewareInterface
{
    public function handle(
        Request $request,
        callable $next
    ): Response;
}
