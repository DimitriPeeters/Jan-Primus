<?php

declare(strict_types=1);

namespace AEFS\Core;

use InvalidArgumentException;

final class EnvironmentDetector
{
    public const DEVELOPMENT = 'development';
    public const TESTING = 'testing';
    public const STAGING = 'staging';
    public const PRODUCTION = 'production';

    private string $environment;

    public function __construct(
        private readonly Environment $environmentLoader
    ) {
        $this->environment = $this->detect();
    }

    public function current(): string
    {
        return $this->environment;
    }

    public function is(string $environment): bool
    {
        return $this->environment === strtolower($environment);
    }

    public function isDevelopment(): bool
    {
        return $this->environment === self::DEVELOPMENT;
    }

    public function isTesting(): bool
    {
        return $this->environment === self::TESTING;
    }

    public function isStaging(): bool
    {
        return $this->environment === self::STAGING;
    }

    public function isProduction(): bool
    {
        return $this->environment === self::PRODUCTION;
    }

    public function set(string $environment): void
    {
        $environment = strtolower(trim($environment));

        if (!in_array($environment, [
            self::DEVELOPMENT,
            self::TESTING,
            self::STAGING,
            self::PRODUCTION,
        ], true)) {
            throw new InvalidArgumentException(
                sprintf('Unknown environment [%s].', $environment)
            );
        }

        $this->environment = $environment;
    }

    private function detect(): string
    {
        $environment = strtolower(
            (string) $this->environmentLoader->get(
                'APP_ENV',
                self::PRODUCTION
            )
        );

        return match ($environment) {
            self::DEVELOPMENT => self::DEVELOPMENT,
            self::TESTING => self::TESTING,
            self::STAGING => self::STAGING,
            default => self::PRODUCTION,
        };
    }
}