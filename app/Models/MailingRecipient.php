<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class MailingRecipient
{
    public function __construct(
        public readonly int $recipientId,
        public readonly int $mailingId,
        public readonly ?int $memberId,
        public readonly string $email,
        public readonly string $name,
        public readonly string $status,
        public readonly int $attempts,
        public readonly ?string $nextAttemptAt,
        public readonly ?string $sentAt,
        public readonly ?string $error
    ) {
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'in_wachtrij' => 'In wachtrij',
            'bezig' => 'Bezig',
            'verzonden' => 'Verzonden',
            'mislukt' => 'Mislukt',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function statusCssClass(): string
    {
        return match ($this->status) {
            'verzonden' => 'badge-success',
            'mislukt' => 'badge-danger',
            'bezig' => 'badge-info',
            default => 'badge-warning',
        };
    }

    public function displaySentAt(): string
    {
        if ($this->sentAt === null || $this->sentAt === '') {
            return '-';
        }

        return (new DateTimeImmutable($this->sentAt))
            ->format('d/m/Y H:i');
    }
}
