<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\Mailing;
use App\Models\MailingRecipient;

final class MailingMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public function mailing(array $row): Mailing
    {
        return new Mailing(
            mailingId: (int) $row['mailing_id'],
            type: (string) $row['type'],
            audienceType: (string) $row['doelgroep_type'],
            audienceJson: $this->nullableString($row['doelgroep_json'] ?? null),
            eventId: $this->nullableInt($row['event_id'] ?? null),
            createdBy: $this->nullableInt($row['aangemaakt_door'] ?? null),
            subject: (string) $row['onderwerp'],
            html: (string) $row['inhoud_html'],
            text: (string) $row['inhoud_tekst'],
            status: (string) $row['status'],
            createdAt: (string) $row['aangemaakt_op'],
            updatedAt: $this->nullableString($row['bijgewerkt_op'] ?? null),
            completedAt: $this->nullableString($row['voltooid_op'] ?? null),
            recipientCount: (int) ($row['ontvangers_aantal'] ?? 0),
            queuedCount: (int) ($row['wachtrij_aantal'] ?? 0),
            sentCount: (int) ($row['verzonden_aantal'] ?? 0),
            failedCount: (int) ($row['mislukt_aantal'] ?? 0),
            eventTitle: $this->nullableString($row['event_titel'] ?? null),
            creatorName: $this->nullableString($row['maker_naam'] ?? null)
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public function recipient(array $row): MailingRecipient
    {
        return new MailingRecipient(
            recipientId: (int) $row['ontvanger_id'],
            mailingId: (int) $row['mailing_id'],
            memberId: $this->nullableInt($row['lid_id'] ?? null),
            email: (string) $row['email'],
            name: (string) $row['naam'],
            status: (string) $row['status'],
            attempts: (int) $row['pogingen'],
            nextAttemptAt: $this->nullableString(
                $row['volgende_poging_op'] ?? null
            ),
            sentAt: $this->nullableString($row['verzonden_op'] ?? null),
            error: $this->nullableString($row['foutmelding'] ?? null)
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
