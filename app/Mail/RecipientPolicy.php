<?php

declare(strict_types=1);

namespace App\Mail;

use AEFS\Core\Config;
use RuntimeException;

final class RecipientPolicy
{
    /**
     * @var string[]|null
     */
    private ?array $allowlist = null;

    public function __construct(
        private readonly Config $config
    ) {
    }

    public function isRestricted(): bool
    {
        return $this->allowedEmails() !== [];
    }

    /**
     * @return string[]
     */
    public function allowedEmails(): array
    {
        if ($this->allowlist !== null) {
            return $this->allowlist;
        }

        $configured = $this->config->get(
            'mail.recipient_allowlist',
            []
        );

        if (!is_array($configured)) {
            throw new RuntimeException(
                'De lokale mail-ontvangersbeperking moet een lijst zijn.'
            );
        }

        $emails = [];

        foreach ($configured as $email) {
            if (!is_string($email)) {
                throw new RuntimeException(
                    'De lokale mail-ontvangersbeperking bevat een ongeldig adres.'
                );
            }

            $email = strtolower(trim($email));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException(
                    'De lokale mail-ontvangersbeperking bevat een ongeldig adres.'
                );
            }

            $emails[$email] = $email;
        }

        $this->allowlist = array_values($emails);

        return $this->allowlist;
    }

    public function allows(string $email): bool
    {
        $allowlist = $this->allowedEmails();

        return $allowlist === []
            || in_array(strtolower(trim($email)), $allowlist, true);
    }

    /**
     * @param array<int, array<string, mixed>> $members
     *
     * @return array<int, array<string, mixed>>
     */
    public function filter(array $members): array
    {
        if (!$this->isRestricted()) {
            return $members;
        }

        return array_values(
            array_filter(
                $members,
                fn(array $member): bool => $this->allows(
                    (string) ($member['email'] ?? '')
                )
            )
        );
    }
}
