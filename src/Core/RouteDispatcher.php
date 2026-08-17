<?php

declare(strict_types=1);

namespace AEFS\Core;

use AEFS\HTTP\Request;
use AEFS\HTTP\Response;
use RuntimeException;

final class RouteDispatcher
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    public function dispatch(
        Route $route,
        Request $request,
        RouteParameterBag $parameters
    ): Response {
        $action = $route->action();

        $result = match (true) {
            $action instanceof \Closure => $this->dispatchClosure(
                $action,
                $request,
                $parameters
            ),

            is_array($action) && count($action) === 2 => $this->dispatchController(
                $action,
                $request,
                $parameters
            ),

            is_callable($action) => $this->container->call($action),

            default => throw new RuntimeException(
                'Invalid route action.'
            ),
        };

        return $this->toResponse($result);
    }

    private function dispatchClosure(
        \Closure $closure,
        Request $request,
        RouteParameterBag $parameters
    ): mixed {
        return $closure(
            $request,
            ...array_values($parameters->all())
        );
    }

    /**
     * @param array{0:class-string,1:string} $action
     */
    private function dispatchController(
        array $action,
        Request $request,
        RouteParameterBag $parameters
    ): mixed {
        [$controllerClass, $method] = $action;

        $controller = $this->container->get($controllerClass);

        $reflection = new \ReflectionMethod(
            $controller,
            $method
        );

        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {

            $type = $parameter->getType();

            if (
                $type instanceof \ReflectionNamedType &&
                !$type->isBuiltin()
            ) {
                $name = $type->getName();

                if ($name === Request::class) {
                    $arguments[] = $request;
                    continue;
                }

                $arguments[] = $this->container->get($name);
                continue;
            }

            $parameterName = $parameter->getName();

            if ($parameters->has($parameterName)) {
                $arguments[] = $parameters->get($parameterName);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                sprintf(
                    'Unable to resolve parameter [%s] for %s::%s().',
                    $parameterName,
                    $controllerClass,
                    $method
                )
            );
        }

        return $reflection->invokeArgs(
            $controller,
            $arguments
        );
    }

    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return new Response()->json($result);
        }

        return new Response((string) $result);
    }
}