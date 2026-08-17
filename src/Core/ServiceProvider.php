<?php

declare(strict_types=1);

namespace AEFS\Core;

abstract class ServiceProvider
{
    public function __construct(
        protected readonly Application $app
    ) {
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
    }

    /**
     * Boot services after all providers have been registered.
     */
    public function boot(): void
    {
    }

    protected function bind(string $abstract, \Closure|string $concrete): void
    {
        $this->app->bind($abstract, $concrete);
    }

    protected function singleton(string $abstract, string|object $concrete): void
    {
        $this->app->singleton($abstract, $concrete);
    }

    protected function instance(string $abstract, object $instance): void
    {
        $this->app->instanceBinding($abstract, $instance);
    }

    protected function make(string $abstract): object
    {
        return $this->app->make($abstract);
    }

    protected function container(): Container
    {
        return $this->app->container();
    }
}