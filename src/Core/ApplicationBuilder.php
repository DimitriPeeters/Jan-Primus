<?php

declare(strict_types=1);

namespace AEFS\Core;

use RuntimeException;

final class ApplicationBuilder
{
    private readonly Application $application;

    public function __construct(string $basePath)
    {
        $this->application = new Application(
            realpath($basePath) ?: $basePath
        );
    }

    public static function create(string $basePath): self
    {
        return new self($basePath);
    }

    public function bootstrap(): self
    {
        $bootstrap = new Bootstrap($this->application);

        $bootstrap->boot();

        $this->application->instanceBinding(
            Bootstrap::class,
            $bootstrap
        );

        return $this;
    }

    public function register(string $abstract, string|object $concrete): self
    {
        if (is_object($concrete) && !($concrete instanceof \Closure)) {
            $this->application->instanceBinding($abstract, $concrete);

            return $this;
        }

        $this->application->singleton($abstract, $concrete);

        return $this;
    }

    public function provider(string $provider): self
    {
        if (!class_exists($provider)) {
            throw new RuntimeException(
                sprintf('ServiceProvider [%s] not found.', $provider)
            );
        }

        /** @var ServiceProvider $instance */
        $instance = new $provider($this->application);

        $instance->register();
        $instance->boot();

        return $this;
    }

    public function build(): Application
    {
        return $this->application;
    }
}