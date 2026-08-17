<?php

declare(strict_types=1);

namespace AEFS\Core;

use RuntimeException;

final class ProviderRepository
{
    /**
     * @var array<class-string<ServiceProvider>>
     */
    private array $providers = [];

    public function __construct(
        private readonly Application $application
    ) {
    }

    /**
     * @param array<class-string<ServiceProvider>> $providers
     */
    public function load(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }

        $this->boot();
    }

    /**
     * @param class-string<ServiceProvider> $provider
     */
    public function register(string $provider): void
    {
        if (!class_exists($provider)) {
            throw new RuntimeException(
                sprintf(
                    'Service provider [%s] does not exist.',
                    $provider
                )
            );
        }

        $instance = new $provider($this->application);

        if (!$instance instanceof ServiceProvider) {
            throw new RuntimeException(
                sprintf(
                    '[%s] is not a valid service provider.',
                    $provider
                )
            );
        }

        foreach ($this->providers as $registered) {
            if ($registered === $provider) {
                return;
            }
        }

        $instance->register();

        $this->providers[] = $provider;

        $this->application
            ->container()
            ->instance($provider, $instance);
    }

    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            /** @var ServiceProvider $instance */
            $instance = $this->application
                ->container()
                ->get($provider);

            $instance->boot();
        }
    }

    /**
     * @return array<class-string<ServiceProvider>>
     */
    public function all(): array
    {
        return $this->providers;
    }

    public function has(string $provider): bool
    {
        return in_array($provider, $this->providers, true);
    }

    public function count(): int
    {
        return count($this->providers);
    }
}