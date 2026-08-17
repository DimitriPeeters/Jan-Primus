<?php

declare(strict_types=1);

namespace AEFS\Core;

use RuntimeException;

final class Bootstrap
{
    private bool $booted = false;

    /**
     * @var array<int, ServiceProvider>
     */
    private array $providers = [];

    public function __construct(
        private readonly Application $application
    ) {
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->registerEnvironment();
        $this->registerConfiguration();
        $this->registerProviders();

        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    private function registerEnvironment(): void
    {
        $environment = new Environment(
            $this->application->basePath('.env')
        );

        $this->application->instanceBinding(
            Environment::class,
            $environment
        );
    }

    private function registerConfiguration(): void
    {
        $config = new Config(
            $this->application->configPath()
        );

        $this->application->instanceBinding(
            Config::class,
            $config
        );
    }

    private function registerProviders(): void
    {
        /** @var Config $config */
        $config = $this->application->make(Config::class);

        $providers = $config->get('app.providers', []);

        if (!is_array($providers)) {
            throw new RuntimeException(
                'Configuration key [app.providers] must be an array.'
            );
        }

        foreach ($providers as $providerClass) {

            if (!class_exists($providerClass)) {
                throw new RuntimeException(
                    sprintf(
                        'ServiceProvider [%s] does not exist.',
                        $providerClass
                    )
                );
            }

            $provider = new $providerClass($this->application);

            if (!$provider instanceof ServiceProvider) {
                throw new RuntimeException(
                    sprintf(
                        '[%s] is not a ServiceProvider.',
                        $providerClass
                    )
                );
            }

            $provider->register();

            $this->providers[] = $provider;
        }
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}