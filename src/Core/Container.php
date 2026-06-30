<?php

declare(strict_types=1);

namespace AEFS\Core;

use Closure;
use ReflectionClass;
use RuntimeException;

final class Container
{
    private static array $bindings = [];
    private static array $instances = [];

    public static function bind(string $abstract, Closure $factory): void
    {
        self::$bindings[$abstract] = $factory;
    }

    public static function singleton(string $abstract, Closure $factory): void
    {
        self::$bindings[$abstract] = function () use ($abstract, $factory) {

            if (!isset(self::$instances[$abstract])) {
                self::$instances[$abstract] = $factory();
            }

            return self::$instances[$abstract];
        };
    }

    public static function get(string $class): mixed
    {
        if (isset(self::$bindings[$class])) {
            return self::$bindings[$class]();
        }

        return self::build($class);
    }

    private static function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Klasse {$class} bestaat niet.");
        }

        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {

            $type = $parameter->getType();

            if ($type === null) {
                throw new RuntimeException(
                    "Kan {$parameter->getName()} niet injecteren."
                );
            }

            $arguments[] = self::get($type->getName());
        }

        return $reflection->newInstanceArgs($arguments);
    }
}