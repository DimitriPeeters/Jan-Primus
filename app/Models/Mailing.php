<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class Mailing
{
    public const STATUS_QUEUED = 'in_wachtrij';
    public const STATUS_PROCESSING = 'bezig';
    public const STATUS_SENT = 'verzonden';
    public const STATUS_PARTIAL = 'gedeeltelijk_mislukt';
    public const STATUS_FAILED = 'mislukt';

    public function __construct(
        public readonly int $mailingId,
        public readonly string $type,
        public readonly string $audienceType,
        public readonly ?string $audienceJson,
        public readonly ?int $eventId,
        public readonly ?int $createdBy,
        public readonly string $subject,
        public readonly string $html,
        public readonly string $text,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $completedAt,
        public readonly int $recipientCount = 0,
        public readonly int $queuedCount = 0,
        public readonly int $sentCount = 0,
        public readonly int $failedCount = 0,
        public readonly ?string $eventTitle = null,
        public readonly ?string $creatorName = null
    ) {
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_QUEUED => 'In wachtrij',
            self::STATUS_PROCESSING => 'Bezig',
            self::STATUS_SENT => 'Verzonden',
            self::STATUS_PARTIAL => 'Gedeeltelijk mislukt',
            self::STATUS_FAILED => 'Mislukt',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function statusCssClass(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'badge-success',
            self::STATUS_PARTIAL, self::STATUS_FAILED => 'badge-danger',
            self::STATUS_PROCESSING => 'badge-info',
            default => 'badge-warning',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'event_gepubliceerd' => 'Event gepubliceerd',
            'event_bevestigd' => 'Event bevestigd',
            'event_reserve' => 'Event reserve',
            'event_geannuleerd' => 'Event geannuleerd',
            'shift_planning' => 'Shiftplanning',
            'wachtwoord_reset' => 'Wachtwoordherstel',
            'manueel' => 'Manuele mailing',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function displayCreatedAt(): string
    {
        return (new DateTimeImmutable($this->createdAt))
            ->format('d/m/Y H:i');
    }

    public function progressLabel(): string
    {
        return sprintf(
            '%d / %d verzonden',
            $this->sentCount,
            $this->recipientCount
        );
    }
}
