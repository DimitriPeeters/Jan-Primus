<?php

declare(strict_types=1);

namespace App\Models;

use DateInterval;
use DateTimeImmutable;

final class Shift
{
    public const STATUS_ACTIEF = 'actief';
    public const STATUS_GEANNULEERD = 'geannuleerd';

    public function __construct(
        public readonly int $shiftId,
        public readonly int $eventId,
        public readonly int $typeId,
        public readonly ?string $naam,
        public readonly string $startOp,
        public readonly string $eindOp,
        public readonly int $maxPersonen,
        public readonly string $status,
        public readonly string $aangemaaktOp,
        public readonly ?string $bijgewerktOp,
        public readonly ?string $eventTitel = null,
        public readonly ?string $eventStartDatum = null,
        public readonly ?string $eventEindDatum = null,
        public readonly ?string $eventStatus = null,
        public readonly ?string $typeNaam = null,
        public readonly ?string $typeKleur = null,
        public readonly ?string $typeIcoon = null,
        public readonly ?string $typeOmschrijving = null,
        public readonly int $aantalWachtend = 0,
        public readonly int $aantalBevestigd = 0,
        public readonly int $aantalReserve = 0,
        public readonly int $aantalGeweigerd = 0,
        public readonly int $aantalGeannuleerd = 0
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIEF => 'Actief',
            self::STATUS_GEANNULEERD => 'Geannuleerd',
        ];
    }

    public function isActief(): bool
    {
        return $this->status === self::STATUS_ACTIEF;
    }

    public function isGeannuleerd(): bool
    {
        return $this->status === self::STATUS_GEANNULEERD;
    }

    public function isAfgelopen(
        ?DateTimeImmutable $moment = null
    ): bool {
        $moment ??= new DateTimeImmutable();

        return $this->endDateTime() < $moment;
    }

    public function looptOverMiddernacht(): bool
    {
        return $this->startDateTime()->format('Y-m-d')
            !== $this->endDateTime()->format('Y-m-d');
    }

    public function duurInMinuten(): int
    {
        $seconds = $this->endDateTime()->getTimestamp()
            - $this->startDateTime()->getTimestamp();

        return max(
            0,
            (int) floor($seconds / 60)
        );
    }

    public function beschikbarePlaatsen(): int
    {
        return max(
            0,
            $this->maxPersonen - $this->aantalBevestigd
        );
    }

    public function isVolzet(): bool
    {
        return $this->beschikbarePlaatsen() === 0;
    }

    public function bezettingsPercentage(): int
    {
        if ($this->maxPersonen <= 0) {
            return 0;
        }

        return min(
            100,
            (int) round(
                ($this->aantalBevestigd / $this->maxPersonen) * 100
            )
        );
    }

    public function displayNaam(): string
    {
        if (
            $this->naam !== null
            && trim($this->naam) !== ''
        ) {
            return $this->naam;
        }

        return $this->typeNaam ?? 'Shift';
    }

    public function displayType(): string
    {
        return $this->typeNaam ?? ShiftType::DEFAULT_NAME;
    }

    public function displayDatum(): string
    {
        return $this->startDateTime()->format('d/m/Y');
    }

    public function displayTijdvak(): string
    {
        $start = $this->startDateTime();
        $end = $this->endDateTime();

        if (!$this->looptOverMiddernacht()) {
            return sprintf(
                '%s – %s',
                $start->format('H:i'),
                $end->format('H:i')
            );
        }

        return sprintf(
            '%s – %s (+1 dag)',
            $start->format('H:i'),
            $end->format('H:i')
        );
    }

    public function displayPeriode(): string
    {
        return sprintf(
            '%s, %s',
            $this->displayDatum(),
            $this->displayTijdvak()
        );
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status]
            ?? ucfirst($this->status);
    }

    public function statusCssClass(): string
    {
        return $this->isActief()
            ? 'badge-success'
            : 'badge-danger';
    }

    public function annulatieDeadline(): DateTimeImmutable
    {
        $eventStart = $this->eventStartDatum !== null
            ? new DateTimeImmutable($this->eventStartDatum)
            : $this->startDateTime();

        return $eventStart->sub(
            new DateInterval('P14D')
        );
    }

    public function magLidZelfAnnuleren(
        ?DateTimeImmutable $moment = null
    ): bool {
        $moment ??= new DateTimeImmutable();

        return $this->isActief()
            && !$this->isAfgelopen($moment)
            && $moment < $this->annulatieDeadline();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAuditArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'type_id' => $this->typeId,
            'naam' => $this->naam,
            'start_op' => $this->startOp,
            'eind_op' => $this->eindOp,
            'max_personen' => $this->maxPersonen,
            'status' => $this->status,
        ];
    }

    private function startDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->startOp);
    }

    private function endDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->eindOp);
    }
}
