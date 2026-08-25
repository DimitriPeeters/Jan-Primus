<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\Shift;

final class ShiftMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public function fromDatabase(array $row): Shift
    {
        return new Shift(
            shiftId: (int) $row['shift_id'],
            eventId: (int) $row['event_id'],
            typeId: (int) $row['type_id'],
            naam: $this->nullableString(
                $row['naam'] ?? null
            ),
            startOp: (string) $row['start_op'],
            eindOp: (string) $row['eind_op'],
            maxPersonen: (int) $row['max_personen'],
            status: (string) (
                $row['status'] ?? Shift::STATUS_ACTIEF
            ),
            aangemaaktOp: (string) $row['aangemaakt_op'],
            bijgewerktOp: $this->nullableString(
                $row['bijgewerkt_op'] ?? null
            ),
            eventTitel: $this->nullableString(
                $row['event_titel'] ?? null
            ),
            eventStartDatum: $this->nullableString(
                $row['event_startdatum'] ?? null
            ),
            eventEindDatum: $this->nullableString(
                $row['event_einddatum'] ?? null
            ),
            eventStatus: $this->nullableString(
                $row['event_status'] ?? null
            ),
            typeNaam: $this->nullableString(
                $row['type_naam'] ?? null
            ),
            typeKleur: $this->nullableString(
                $row['type_kleur'] ?? null
            ),
            typeIcoon: $this->nullableString(
                $row['type_icoon'] ?? null
            ),
            typeOmschrijving: $this->nullableString(
                $row['type_omschrijving'] ?? null
            ),
            aantalWachtend: (int) (
                $row['aantal_wachtend'] ?? 0
            ),
            aantalBevestigd: (int) (
                $row['aantal_bevestigd'] ?? 0
            ),
            aantalReserve: (int) (
                $row['aantal_reserve'] ?? 0
            ),
            aantalGeweigerd: (int) (
                $row['aantal_geweigerd'] ?? 0
            ),
            aantalGeannuleerd: (int) (
                $row['aantal_geannuleerd'] ?? 0
            )
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public function map(array $row): Shift
    {
        return $this->fromDatabase($row);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function toDatabase(array $data): array
    {
        return [
            'event_id' => (int) $data['event_id'],
            'type_id' => (int) $data['type_id'],
            'naam' => $this->nullableString(
                $data['naam'] ?? null
            ),
            'start_op' => (string) $data['start_op'],
            'eind_op' => (string) $data['eind_op'],
            'max_personen' => (int) $data['max_personen'],
            'status' => (string) (
                $data['status'] ?? Shift::STATUS_ACTIEF
            ),
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
