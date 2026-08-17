<?php

declare(strict_types=1);

namespace AEFS\Core;

final class MiddlewarePipeline
{
    /**
     * @param array<class-string> $middleware
     */
    public function handle(
        Request $request,
        array $middleware,
        callable $destination
    ): mixed {
        $pipeline = array_reduce(
            array_reverse($middleware),

            function (callable $next, string $middlewareClass): callable {

                return function (Request $request) use ($middlewareClass, $next) {

                    $middleware = Container::get($middlewareClass);

                    if (!method_exists($middleware, 'handle')) {
                        throw new \RuntimeException(
                            sprintf(
                                'Middleware "%s" heeft geen handle()-methode.',
                                $middlewareClass
                            )
                        );
                    }

                    return $middleware->handle($request, $next);
                };
            },

            $destination
        );

        return $pipeline($request);
    }
}