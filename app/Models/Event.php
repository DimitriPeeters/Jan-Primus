<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class Event
{
    public const STATUS_CONCEPT = 'concept';
    public const STATUS_PUBLISHED = 'gepubliceerd';
    public const STATUS_CLOSED = 'afgesloten';
    public const STATUS_CANCELLED = 'geannuleerd';

    public function __construct(
        public readonly int $eventId,
        public readonly string $titel,
        public readonly ?string $beschrijving,
        public readonly string $startDatum,
        public readonly ?string $eindDatum,
        public readonly ?string $locatie,
        public readonly ?int $maxDeelnemers,
        public readonly string $status,
        public readonly ?string $planningVerstuurd,
        public readonly string $aangemaaktOp,
        public readonly ?string $bijgewerktOp,
        public readonly int $aantalInschrijvingen = 0,
        public readonly int $aantalBevestigd = 0,
        public readonly int $aantalAnnulatieverzoeken = 0
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_CONCEPT => 'Concept',
            self::STATUS_PUBLISHED => 'Gepubliceerd',
            self::STATUS_CLOSED => 'Afgesloten',
            self::STATUS_CANCELLED => 'Geannuleerd',
        ];
    }

    public function duurtMeerdereDagen(): bool
    {
        return $this->eindDatum !== null
            && $this->eindDatum !== $this->startDatum;
    }

    public function displayDate(): string
    {
        if (!$this->duurtMeerdereDagen()) {
            return $this->formatDate($this->startDatum);
        }

        return sprintf(
            '%s – %s',
            $this->formatDate($this->startDatum),
            $this->formatDate($this->eindDatum)
        );
    }

    public function durationDays(): int
    {
        $start = new DateTimeImmutable($this->startDatum);
        $end = new DateTimeImmutable(
            $this->eindDatum ?? $this->startDatum
        );

        return $start->diff($end)->days + 1;
    }

    /**
     * @return string[]
     */
    public function dates(): array
    {
        $current = new DateTimeImmutable($this->startDatum);
        $end = new DateTimeImmutable(
            $this->eindDatum ?? $this->startDatum
        );
        $dates = [];

        while ($current <= $end) {
            $dates[] = $current->format('Y-m-d');
            $current = $current->modify('+1 day');
        }

        return $dates;
    }

    public function isPast(): bool
    {
        $today = new DateTimeImmutable('today');
        $end = new DateTimeImmutable(
            $this->eindDatum ?? $this->startDatum
        );

        return $end < $today;
    }

    public function isToday(): bool
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $end = $this->eindDatum ?? $this->startDatum;

        return $today >= $this->startDatum
            && $today <= $end;
    }

    public function isFuture(): bool
    {
        $today = new DateTimeImmutable('today');
        $start = new DateTimeImmutable($this->startDatum);

        return $start > $today;
    }

    public function isConcept(): bool
    {
        return $this->status === self::STATUS_CONCEPT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isVisibleToMembers(): bool
    {
        return !$this->isConcept();
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status]
            ?? ucfirst($this->status);
    }

    public function statusCssClass(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'badge-success',
            self::STATUS_CLOSED => 'badge-info',
            self::STATUS_CANCELLED => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function periodStatusLabel(): string
    {
        if ($this->isToday()) {
            return 'Vandaag';
        }

        if ($this->isFuture()) {
            return 'Toekomstig';
        }

        return 'Afgelopen';
    }

    public function periodStatusCssClass(): string
    {
        if ($this->isToday()) {
            return 'badge-info';
        }

        if ($this->isFuture()) {
            return 'badge-success';
        }

        return 'badge-warning';
    }

    public function hasLocation(): bool
    {
        return $this->locatie !== null
            && $this->locatie !== '';
    }

    public function hasDescription(): bool
    {
        return $this->beschrijving !== null
            && $this->beschrijving !== '';
    }

    public function hasCapacityLimit(): bool
    {
        return $this->maxDeelnemers !== null;
    }

    public function remainingPlaces(): ?int
    {
        if ($this->maxDeelnemers === null) {
            return null;
        }

        return max(
            0,
            $this->maxDeelnemers - $this->aantalBevestigd
        );
    }

    public function isFull(): bool
    {
        return $this->maxDeelnemers !== null
            && $this->aantalBevestigd >= $this->maxDeelnemers;
    }

    public function hasPendingCancellationRequests(): bool
    {
        return $this->aantalAnnulatieverzoeken > 0;
    }

    public function capacityLabel(): string
    {
        if ($this->maxDeelnemers === null) {
            return 'Onbeperkt';
        }

        return sprintf(
            '%d / %d bevestigd',
            $this->aantalBevestigd,
            $this->maxDeelnemers
        );
    }

    public function planningWasSent(): bool
    {
        return $this->planningVerstuurd !== null
            && $this->planningVerstuurd !== '';
    }

    public function displayPlanningSentAt(): string
    {
        if (!$this->planningWasSent()) {
            return 'Nog niet verstuurd';
        }

        return (new DateTimeImmutable($this->planningVerstuurd))
            ->format('d/m/Y H:i');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAuditArray(): array
    {
        return [
            'titel' => $this->titel,
            'beschrijving' => $this->beschrijving,
            'locatie' => $this->locatie,
            'max_deelnemers' => $this->maxDeelnemers,
            'startdatum' => $this->startDatum,
            'einddatum' => $this->eindDatum,
            'status' => $this->status,
            'planning_verstuurd' => $this->planningVerstuurd,
        ];
    }

    private function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        return (new DateTimeImmutable($date))->format('d/m/Y');
    }
}
