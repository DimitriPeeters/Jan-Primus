<?php

declare(strict_types=1);

namespace AEFS\Core;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    /**
     * @var array<string, Closure|class-string|object>
     */
    private array $bindings = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

public function bind(string $abstract, object|string|null $concrete = null): void
{
    $this->bindings[$abstract] = [
        'concrete' => $concrete ?? $abstract,
        'shared' => false,
    ];
}

public function singleton(string $abstract, object|string|null $concrete = null): void
{
    $this->bindings[$abstract] = [
        'concrete' => $concrete ?? $abstract,
        'shared' => true,
    ];
}

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }

public function get(string $abstract): object
{
    if (isset($this->instances[$abstract])) {
        return $this->instances[$abstract];
    }

    if (isset($this->bindings[$abstract])) {
        $binding = $this->bindings[$abstract];

        if ($binding instanceof Closure) {
            $object = $binding($this);

            if (!is_object($object)) {
                throw new RuntimeException(sprintf(
                    'Container binding [%s] did not return an object.',
                    $abstract
                ));
            }

            return $object;
        }

        $concrete = $binding['concrete'] ?? $binding;
        $shared = (bool) ($binding['shared'] ?? false);

        $object = $concrete instanceof Closure
            ? $concrete($this)
            : $this->build($concrete);

        if (!is_object($object)) {
            throw new RuntimeException(sprintf(
                'Container binding [%s] did not resolve to an object.',
                $abstract
            ));
        }

        if ($shared) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    return $this->build($abstract);
}

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function build(string $class): object
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new RuntimeException(sprintf(
                'Class [%s] does not exist.',
                $class
            ), previous: $e);
        }

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException(sprintf(
                'Class [%s] is not instantiable.',
                $class
            ));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();

                    continue;
                }

                throw new RuntimeException(sprintf(
                    'Unable to resolve parameter [$%s] in [%s].',
                    $parameter->getName(),
                    $class
                ));
            }

            $arguments[] = $this->get($type->getName());
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @param callable|array{0:object|string,1:string} $callable
     */
    public function call(callable|array $callable): mixed
    {
        if (is_array($callable)) {
            $target = is_string($callable[0])
                ? $this->get($callable[0])
                : $callable[0];

            $method = $callable[1];

            $reflection = new \ReflectionMethod($target, $method);
        } else {
            $reflection = new \ReflectionFunction(Closure::fromCallable($callable));
            $target = null;
        }

        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            throw new RuntimeException(sprintf(
                'Unable to resolve parameter [$%s].',
                $parameter->getName()
            ));
        }

        if ($reflection instanceof \ReflectionMethod) {
            return $reflection->invokeArgs($target, $arguments);
        }

        return $reflection->invokeArgs($arguments);
    }
}