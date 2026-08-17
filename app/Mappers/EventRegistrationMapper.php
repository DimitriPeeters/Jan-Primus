<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\EventRegistration;

final class EventRegistrationMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public function fromDatabase(array $row): EventRegistration
    {
        $dagen = [];
        $dagenCsv = trim((string) ($row['dagen_csv'] ?? ''));

        if ($dagenCsv !== '') {
            $dagen = array_values(
                array_filter(
                    explode(',', $dagenCsv),
                    static fn(string $datum): bool => $datum !== ''
                )
            );
        }

        return new EventRegistration(
            inschrijvingId: (int) $row['inschrijving_id'],
            eventId: (int) $row['event_id'],
            lidId: (int) $row['lid_id'],
            status: (string) (
                $row['status']
                ?? EventRegistration::STATUS_WACHTEND
            ),
            aangemeldOp: (string) $row['aangemeld_op'],
            uitschrijfreden: $this->nullableString(
                $row['uitschrijfreden'] ?? null
            ),
            annulatieAangevraagdOp: $this->nullableString(
                $row['annulatie_aangevraagd_op'] ?? null
            ),
            uitgeschrevenOp: $this->nullableString(
                $row['uitgeschreven_op'] ?? null
            ),
            annulatieBevestigdDoor:
                isset($row['annulatie_bevestigd_door'])
                    ? (int) $row['annulatie_bevestigd_door']
                    : null,
            dagen: $dagen,
            lidVoornaam: $this->nullableString(
                $row['lid_voornaam'] ?? null
            ),
            lidAchternaam: $this->nullableString(
                $row['lid_achternaam'] ?? null
            ),
            lidEmail: $this->nullableString(
                $row['lid_email'] ?? null
            ),
            eventTitel: $this->nullableString(
                $row['event_titel'] ?? null
            ),
            eventStartDatum: $this->nullableString(
                $row['event_startdatum'] ?? null
            ),
            eventEindDatum: $this->nullableString(
                $row['event_einddatum'] ?? null
            )
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== ''
            ? $value
            : null;
    }
}
