<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class EventRegistration
{
    public const STATUS_WACHTEND = 'wachtend';
    public const STATUS_BEVESTIGD = 'bevestigd';
    public const STATUS_RESERVE = 'reserve';
    public const STATUS_GEWEIGERD = 'geweigerd';

    /**
     * @param string[] $dagen
     */
    public function __construct(
        public readonly int $inschrijvingId,
        public readonly int $eventId,
        public readonly int $lidId,
        public readonly string $status,
        public readonly string $aangemeldOp,
        public readonly ?string $uitschrijfreden,
        public readonly ?string $annulatieAangevraagdOp,
        public readonly ?string $uitgeschrevenOp,
        public readonly ?int $annulatieBevestigdDoor,
        public readonly array $dagen = [],
        public readonly ?string $lidVoornaam = null,
        public readonly ?string $lidAchternaam = null,
        public readonly ?string $lidEmail = null,
        public readonly ?string $eventTitel = null,
        public readonly ?string $eventStartDatum = null,
        public readonly ?string $eventEindDatum = null
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_WACHTEND => 'Wachtend',
            self::STATUS_BEVESTIGD => 'Bevestigd',
            self::STATUS_RESERVE => 'Reserve',
            self::STATUS_GEWEIGERD => 'Geweigerd',
        ];
    }

    public function isActief(): bool
    {
        return $this->uitgeschrevenOp === null
            && $this->status !== self::STATUS_GEWEIGERD;
    }

    public function hasPendingCancellation(): bool
    {
        return $this->annulatieAangevraagdOp !== null
            && $this->uitgeschrevenOp === null;
    }

    public function isUitgeschreven(): bool
    {
        return $this->uitgeschrevenOp !== null;
    }

    public function isWachtend(): bool
    {
        return $this->isActief()
            && $this->status === self::STATUS_WACHTEND;
    }

    public function isBevestigd(): bool
    {
        return $this->isActief()
            && $this->status === self::STATUS_BEVESTIGD;
    }

    public function isReserve(): bool
    {
        return $this->isActief()
            && $this->status === self::STATUS_RESERVE;
    }

    public function isGeweigerd(): bool
    {
        return $this->status === self::STATUS_GEWEIGERD;
    }

    public function lidNaam(): string
    {
        $naam = trim(
            sprintf(
                '%s %s',
                $this->lidVoornaam ?? '',
                $this->lidAchternaam ?? ''
            )
        );

        return $naam !== ''
            ? $naam
            : 'Onbekend lid';
    }

    public function statusLabel(): string
    {
        if ($this->uitgeschrevenOp !== null) {
            return 'Uitgeschreven';
        }

        if ($this->hasPendingCancellation()) {
            return 'Annulering aangevraagd';
        }

        return self::statusOptions()[$this->status]
            ?? ucfirst($this->status);
    }

    public function statusCssClass(): string
    {
        if ($this->uitgeschrevenOp !== null) {
            return 'badge-danger';
        }

        if ($this->hasPendingCancellation()) {
            return 'badge-warning';
        }

        return match ($this->status) {
            self::STATUS_BEVESTIGD => 'badge-success',
            self::STATUS_RESERVE => 'badge-info',
            self::STATUS_GEWEIGERD => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function displayAangemeldOp(): string
    {
        return (new DateTimeImmutable($this->aangemeldOp))
            ->format('d/m/Y H:i');
    }

    public function displayAnnulatieAangevraagdOp(): string
    {
        if ($this->annulatieAangevraagdOp === null) {
            return '-';
        }

        return (new DateTimeImmutable($this->annulatieAangevraagdOp))
            ->format('d/m/Y H:i');
    }

    public function displayDagen(): string
    {
        if ($this->dagen === []) {
            return 'Niet afzonderlijk vastgelegd';
        }

        $days = array_values(array_unique($this->dagen));
        sort($days);

        return implode(
            ', ',
            array_map(
                static fn(string $datum): string => (
                    new DateTimeImmutable($datum)
                )->format('d/m/Y'),
                $days
            )
        );
    }

    public function coversDate(string $date): bool
    {
        if ($this->dagen !== []) {
            return in_array($date, $this->dagen, true);
        }

        return $this->eventStartDatum === $date;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAuditArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'lid_id' => $this->lidId,
            'status' => $this->status,
            'dagen' => $this->dagen,
            'uitschrijfreden' => $this->uitschrijfreden,
            'annulatie_aangevraagd_op' => $this->annulatieAangevraagdOp,
            'uitgeschreven_op' => $this->uitgeschrevenOp,
            'annulatie_bevestigd_door' => $this->annulatieBevestigdDoor,
        ];
    }
}
