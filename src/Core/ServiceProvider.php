<?php

declare(strict_types=1);

namespace AEFS\Core;

final class ServiceProvider
{
    public static function register(): void
    {
        Container::singleton(
            Config::class,
            fn() => Config::getInstance()
        );

        Container::singleton(
            Database::class,
            fn() => Database::getInstance()
        );

        Container::singleton(
            Logger::class,
            fn() => Logger::getInstance()
        );
    }
}