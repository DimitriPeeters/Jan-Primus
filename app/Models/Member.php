<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;

final class Member
{
    public function __construct(

        public readonly int $lidId,

        public readonly string $voornaam,

        public readonly string $achternaam,

        public readonly ?string $email,

        public readonly ?string $telefoon,

        public readonly ?string $straat,

        public readonly ?string $postcode,

        public readonly ?string $gemeente,

        public readonly ?string $land,

        public readonly ?string $geslacht,

        public readonly ?string $geboortedatum,

        public readonly ?string $rijksregisternummer,

        public readonly ?string $tshirtmaat,

        public readonly bool $actief,

        public readonly bool $gdprConsent,

        public readonly ?string $gdprTimestamp,

        public readonly ?string $opmerkingen,

        public readonly ?string $aangemaaktOp,

        public readonly ?string $bijgewerktOp,

        public readonly ?string $toegetredenOp,

        public readonly ?string $uitgetredenOp,

        public readonly bool $nationaalIdentificatienummerOnleesbaar = false

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

    public function fullAddress(): string
    {
        return trim(implode(', ', array_filter([
            $this->straat,
            trim(($this->postcode ?? '') . ' ' . ($this->gemeente ?? '')),
            $this->land,
        ])));
    }

    public function age(): ?int
    {
        if (empty($this->geboortedatum)) {
            return null;
        }

        $geboorte = DateTime::createFromFormat(
            'Y-m-d',
            $this->geboortedatum
        );

        if (!$geboorte) {
            return null;
        }

        return $geboorte->diff(new DateTime())->y;
    }

    public function isAdult(): bool
    {
        $age = $this->age();

        return $age !== null && $age >= 18;
    }

    public function hasEmail(): bool
    {
        return !empty($this->email);
    }

    public function hasPhone(): bool
    {
        return !empty($this->telefoon);
    }

    public function hasAddress(): bool
    {
        return !empty($this->straat)
            || !empty($this->gemeente);
    }

    public function gdprAccepted(): bool
    {
        return $this->gdprConsent;
    }
}
