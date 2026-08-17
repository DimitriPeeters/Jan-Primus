<?php

declare(strict_types=1);

namespace App\Models;

final class ShiftType
{
    public const DEFAULT_NAME = 'Steward';
    public const DEFAULT_COLOR = '#1E3A8A';

    public function __construct(
        public readonly int $typeId,
        public readonly string $naam,
        public readonly string $kleur,
        public readonly ?string $icoon,
        public readonly ?string $omschrijving,
        public readonly bool $actief,
        public readonly string $aangemaaktOp,
        public readonly ?string $bijgewerktOp
    ) {
    }

    public function isActief(): bool
    {
        return $this->actief;
    }

    public function isDefault(): bool
    {
        return strcasecmp(
            $this->naam,
            self::DEFAULT_NAME
        ) === 0;
    }

    public function displayOmschrijving(): string
    {
        return $this->omschrijving ?? 'Geen omschrijving';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAuditArray(): array
    {
        return [
            'naam' => $this->naam,
            'kleur' => $this->kleur,
            'icoon' => $this->icoon,
            'omschrijving' => $this->omschrijving,
            'actief' => $this->actief,
        ];
    }
}