<?php

declare(strict_types=1);

namespace AEFS\Core;

final class Url
{
    public static function base(): string
    {
        static $base = null;

        if ($base !== null) {
            return $base;
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';

        $base = str_replace(
            '\\',
            '/',
            dirname($script)
        );

        if ($base === '/' || $base === '\\') {
            $base = '';
        }

        return $base;
    }

    public static function to(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');

        return self::base() . $path;
    }

    public static function asset(string $path): string
    {
        return self::to('/assets/' . ltrim($path, '/'));
    }

    public static function current(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public static function is(string $path): bool
    {
        return self::current() === self::to($path);
    }
}