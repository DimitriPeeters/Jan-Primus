<?php

declare(strict_types=1);

namespace AEFS\Core;

use App\Models\User;

final class Auth
{
    private const SESSION_KEY = 'auth';

    public static function check(): bool
    {
        $user = self::user();

        return $user !== null
            && isset($user['gebruiker_id'])
            && (int) $user['gebruiker_id'] > 0;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function login(User $user): void
    {
        Session::regenerate();

        $sessionUser = [
            'gebruiker_id' => $user->gebruikerId,
            'lid_id' => $user->lidId,
            'email' => $user->email,
            'rol' => $user->rol,
            'voornaam' => $user->voornaam,
            'achternaam' => $user->achternaam,
        ];

        Session::set(
            self::SESSION_KEY,
            $sessionUser
        );

        Session::set(
            'user_id',
            $user->gebruikerId
        );

        Session::set(
            'user_email',
            $user->email
        );

        Session::set(
            'user_role',
            $user->rol
        );

        Session::set(
            'member_id',
            $user->lidId
        );
    }

    public static function logout(): void
    {
        foreach (
            [
                self::SESSION_KEY,
                'user_id',
                'user_email',
                'user_role',
                'member_id',
            ] as $key
        ) {
            Session::remove($key);
        }

        Session::regenerate();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        $user = Session::get(
            self::SESSION_KEY
        );

        return is_array($user)
            ? $user
            : null;
    }

    public static function id(): ?int
    {
        $id = self::user()['gebruiker_id'] ?? null;

        return $id !== null
            ? (int) $id
            : null;
    }

    public static function memberId(): ?int
    {
        $id = self::user()['lid_id'] ?? null;

        return $id !== null
            ? (int) $id
            : null;
    }

    public static function email(): ?string
    {
        $email = self::user()['email'] ?? null;

        return is_string($email)
            ? $email
            : null;
    }

    public static function role(): ?string
    {
        $role = self::user()['rol'] ?? null;

        return is_string($role)
            ? $role
            : null;
    }

    public static function name(): ?string
    {
        $user = self::user();

        if ($user === null) {
            return null;
        }

        return trim(
            (string) ($user['voornaam'] ?? '')
            . ' '
            . (string) ($user['achternaam'] ?? '')
        );
    }

    public static function hasRole(string $role): bool
    {
        return self::role() === $role;
    }

    public static function isAdmin(): bool
    {
        return self::hasRole(
            User::ROLE_ADMIN
        );
    }

    public static function isMember(): bool
    {
        return self::hasRole(
            User::ROLE_MEMBER
        );
    }
}
