<?php

declare(strict_types=1);

namespace App\Mail;

final class OutgoingMail
{
    /**
     * @param array<int, array{path: string, name: string}> $attachments
     */
    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $recipientName,
        public readonly string $subject,
        public readonly string $html,
        public readonly string $text,
        public readonly array $attachments = []
    ) {
    }
}
