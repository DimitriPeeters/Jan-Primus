<?php

declare(strict_types=1);

namespace AEFS\Core;

final class Flash
{
    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    private static function add(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['_flash'][$type][] = $message;
    }

    /**
     * @return array<string,array<int,string>>
     */
    public static function all(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $messages = $_SESSION['_flash'] ?? [];

        unset($_SESSION['_flash']);

        return $messages;
    }
}