<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Auth;

final class AuthorizationService
{
    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    public function isAdmin(): bool
    {
        return Auth::isAdmin();
    }

    public function isMember(): bool
    {
        return Auth::isMember();
    }

    public function canManageMembers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canAccessMemberProfile(int $memberId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return Auth::memberId() === $memberId;
    }
}
