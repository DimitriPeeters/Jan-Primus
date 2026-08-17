<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\Container;

interface ViewServiceProviderInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function register(
        Container $container,
        array $config
    ): void;
}