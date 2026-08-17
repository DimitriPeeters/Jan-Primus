<?php

declare(strict_types=1);

namespace AEFS\Core;

use RuntimeException;

final class Version
{
    public const NAME = 'AEFS Framework';

    public const VERSION = '1.0.0';

    public const PHP_MINIMUM = '8.4.0';

    public const CODENAME = 'Aurora';

    private function __construct()
    {
    }

    public static function name(): string
    {
        return self::NAME;
    }

    public static function version(): string
    {
        return self::VERSION;
    }

    public static function full(): string
    {
        return sprintf(
            '%s %s (%s)',
            self::NAME,
            self::VERSION,
            self::CODENAME
        );
    }

    public static function phpMinimum(): string
    {
        return self::PHP_MINIMUM;
    }

    public static function psr(): string
    {
        return self::PSR;
    }

    public static function checkPhpVersion(): void
    {
        if (version_compare(PHP_VERSION, self::PHP_MINIMUM, '<')) {
            throw new RuntimeException(
                sprintf(
                    '%s requires PHP %s or newer. Current version: %s.',
                    self::NAME,
                    self::PHP_MINIMUM,
                    PHP_VERSION
                )
            );
        }
    }

    /**
     * @return array<string,string>
     */
    public static function info(): array
    {
        return [
            'framework' => self::NAME,
            'version'   => self::VERSION,
            'codename'  => self::CODENAME,
            'php'        => PHP_VERSION,
            'minimum'    => self::PHP_MINIMUM,
            'psr'        => self::PSR,
        ];
    }
}