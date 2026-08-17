<?php

declare(strict_types=1);

namespace App\Mail;

final class MailContent
{
    public function __construct(
        public readonly string $subject,
        public readonly string $html,
        public readonly string $text
    ) {
    }
}
