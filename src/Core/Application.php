<?php

declare(strict_types=1);

namespace AEFS\Core;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Router;

final class Application
{
    private static ?Application $instance = null;

    private readonly Container $container;

    public function __construct(
        private readonly string $basePath
    ) {
        self::$instance = $this;

        $this->container = new Container();

        $this->registerBaseBindings();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application has not been initialized.');
        }

        return self::$instance;
    }

    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    public function appPath(string $path = ''): string
    {
        return $this->basePath('app' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function container(): Container
    {
        return $this->container;
    }

public function bind(string $abstract, object|string|null $concrete = null): void
{
    $this->container->bind($abstract, $concrete);
}

public function singleton(string $abstract, object|string|null $concrete = null): void
{
    $this->container->singleton($abstract, $concrete);
}

    public function instanceBinding(string $abstract, object $instance): self
    {
        $this->container->instance($abstract, $instance);

        return $this;
    }

    public function make(string $abstract): object
    {
        return $this->container->get($abstract);
    }

    public function run(): Response
    {
        /** @var Request $request */
        $request = $this->make(Request::class);

        /** @var Router $router */
        $router = $this->make(Router::class);

        return $router->dispatch($request);
    }

    private function registerBaseBindings(): void
    {
        $this->instanceBinding(self::class, $this);
        $this->instanceBinding(Container::class, $this->container);

        $this->singleton(Request::class, Request::class);
        $this->singleton(Router::class, Router::class);
    }
}