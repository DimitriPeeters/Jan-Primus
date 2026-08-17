<?php

declare(strict_types=1);

namespace AEFS\Core;

final class Session
{
    private const FLASH_KEY = '_aefs_flash';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start();
    }

    public static function set(
        string $key,
        mixed $value
    ): void {
        self::start();

        $_SESSION[$key] = $value;
    }

    public static function put(
        string $key,
        mixed $value
    ): void {
        self::set($key, $value);
    }

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();

        return array_key_exists($key, $_SESSION);
    }

    public static function remove(string $key): void
    {
        self::start();

        unset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        self::remove($key);
    }

    public static function flash(
        string $key,
        mixed $value
    ): void {
        self::start();

        $_SESSION[self::FLASH_KEY] ??= [];
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();

        return isset($_SESSION[self::FLASH_KEY])
            && is_array($_SESSION[self::FLASH_KEY])
            && array_key_exists(
                $key,
                $_SESSION[self::FLASH_KEY]
            );
    }

    public static function getFlash(
        string $key,
        mixed $default = null
    ): mixed {
        self::start();

        if (!self::hasFlash($key)) {
            return $default;
        }

        $value = $_SESSION[self::FLASH_KEY][$key];

        unset($_SESSION[self::FLASH_KEY][$key]);

        if ($_SESSION[self::FLASH_KEY] === []) {
            unset($_SESSION[self::FLASH_KEY]);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function allFlash(): array
    {
        self::start();

        $messages = $_SESSION[self::FLASH_KEY] ?? [];

        unset($_SESSION[self::FLASH_KEY]);

        return is_array($messages)
            ? $messages
            : [];
    }

    public static function keepFlash(string $key): void
    {
        self::start();

        if (!self::hasFlash($key)) {
            return;
        }

        $_SESSION[self::FLASH_KEY][$key] =
            $_SESSION[self::FLASH_KEY][$key];
    }

    public static function clearFlash(): void
    {
        self::start();

        unset($_SESSION[self::FLASH_KEY]);
    }

    public static function regenerate(): void
    {
        self::start();

        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parameters['path'],
                $parameters['domain'],
                $parameters['secure'],
                $parameters['httponly']
            );
        }

        session_destroy();
    }
}