<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\Event;

final class EventMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public function fromDatabase(array $row): Event
    {
        return new Event(
            eventId: (int) $row['event_id'],
            titel: (string) $row['titel'],
            beschrijving: $this->nullableString(
                $row['beschrijving'] ?? null
            ),
            startDatum: (string) $row['startdatum'],
            eindDatum: $this->nullableString(
                $row['einddatum'] ?? null
            ),
            locatie: $this->nullableString(
                $row['locatie'] ?? null
            ),
            maxDeelnemers: $row['max_deelnemers'] !== null
                ? (int) $row['max_deelnemers']
                : null,
            status: (string) (
                $row['status'] ?? Event::STATUS_PUBLISHED
            ),
            planningVerstuurd: $this->nullableString(
                $row['planning_verstuurd'] ?? null
            ),
            aangemaaktOp: (string) $row['aangemaakt_op'],
            bijgewerktOp: $this->nullableString(
                $row['bijgewerkt_op'] ?? null
            ),
            aantalInschrijvingen: (int) (
                $row['aantal_inschrijvingen'] ?? 0
            ),
            aantalBevestigd: (int) (
                $row['aantal_bevestigd'] ?? 0
            ),
            aantalAnnulatieverzoeken: (int) (
                $row['aantal_annulatieverzoeken'] ?? 0
            )
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function toDatabase(array $data): array
    {
        return [
            'titel' => (string) $data['titel'],
            'beschrijving' => $data['beschrijving'],
            'locatie' => $data['locatie'],
            'max_deelnemers' => $data['max_deelnemers'],
            'startdatum' => (string) $data['startdatum'],
            'einddatum' => $data['einddatum'],
            'status' => (string) $data['status'],
        ];
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
