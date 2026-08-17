<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'lid';

    public const APPROVAL_PENDING = 'wachtend';
    public const APPROVAL_APPROVED = 'goedgekeurd';

    public function __construct(
        public readonly int $gebruikerId,
        public readonly int $lidId,
        public readonly string $email,
        public readonly string $rol,
        public readonly bool $actief,
        public readonly bool $mailBlacklist,
        public readonly string $passwordHash,
        public readonly ?string $resetToken,
        public readonly ?string $resetTokenExpires,
        public readonly string $voornaam,
        public readonly string $achternaam,
        public readonly string $goedkeuringsstatus = self::APPROVAL_APPROVED,
        public readonly ?string $goedgekeurdOp = null,
    ) {
    }

    public function fullName(): string
    {
        return trim($this->voornaam . ' ' . $this->achternaam);
    }

    public function initials(): string
    {
        $firstNameInitial = function_exists('mb_substr')
            ? mb_substr($this->voornaam, 0, 1)
            : substr($this->voornaam, 0, 1);

        $lastNameInitial = function_exists('mb_substr')
            ? mb_substr($this->achternaam, 0, 1)
            : substr($this->achternaam, 0, 1);

        $initials = $firstNameInitial . $lastNameInitial;

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($initials)
            : strtoupper($initials);
    }

    public function isActive(): bool
    {
        return $this->actief;
    }

    public function isAdmin(): bool
    {
        return $this->rol === self::ROLE_ADMIN;
    }

    public function isMember(): bool
    {
        return $this->rol === self::ROLE_MEMBER;
    }

    public function isBlacklisted(): bool
    {
        return $this->mailBlacklist;
    }

    public function isPending(): bool
    {
        return $this->goedkeuringsstatus === self::APPROVAL_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->goedkeuringsstatus === self::APPROVAL_APPROVED;
    }

    public function isInactive(): bool
    {
        return $this->isApproved() && !$this->isActive();
    }

    public function hasResetToken(): bool
    {
        return $this->resetToken !== null
            && $this->resetToken !== '';
    }

    public function roleLabel(): string
    {
        return $this->isAdmin()
            ? 'Administrator'
            : 'Lid';
    }

    public function statusLabel(): string
    {
        if ($this->isPending()) {
            return 'Wacht op goedkeuring';
        }

        return $this->isActive()
            ? 'Actief'
            : 'Inactief';
    }

    public function statusCssClass(): string
    {
        if ($this->isPending()) {
            return 'pending';
        }

        return $this->isActive()
            ? 'active'
            : 'inactive';
    }
}