<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\Container;
use RuntimeException;

final readonly class ViewBootstrapper
{
    public function __construct(
        private Container $container,
        private ViewServiceProviderInterface $provider
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function boot(array $config): void
    {
        $this->provider->register(
            $this->container,
            $config
        );

        $this->assertRegistered();
    }

    private function assertRegistered(): void
    {
        $services = [
            ViewFinder::class,
            ViewEngine::class,
            ViewEngineInterface::class,
            ViewManager::class,
            ViewFactory::class,
            ViewResponseFactory::class,
        ];

        foreach ($services as $service) {
            $instance = $this->container->get($service);

            if (!is_object($instance)) {
                throw new RuntimeException(
                    sprintf(
                        'Viewservice [%s] kon niet worden geregistreerd.',
                        $service
                    )
                );
            }
        }
    }
}