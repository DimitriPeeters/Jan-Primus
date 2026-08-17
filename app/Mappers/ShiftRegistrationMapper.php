<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\ShiftRegistration;

final class ShiftRegistrationMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public function fromDatabase(
        array $row
    ): ShiftRegistration {
        return new ShiftRegistration(
            inschrijvingId: (int) $row['inschrijving_id'],
            shiftId: (int) $row['shift_id'],
            lidId: (int) $row['lid_id'],
            status: (string) (
                $row['status']
                ?? ShiftRegistration::STATUS_WACHTEND
            ),
            opmerkingLid: $this->nullableString(
                $row['opmerking_lid'] ?? null
            ),
            goedgekeurdDoor: $this->nullableInt(
                $row['goedgekeurd_door'] ?? null
            ),
            goedgekeurdOp: $this->nullableString(
                $row['goedgekeurd_op'] ?? null
            ),
            geannuleerdDoor: $this->nullableInt(
                $row['geannuleerd_door'] ?? null
            ),
            geannuleerdOp: $this->nullableString(
                $row['geannuleerd_op'] ?? null
            ),
            annulatieReden: $this->nullableString(
                $row['annulatie_reden'] ?? null
            ),
            aanwezig: (bool) (
                $row['aanwezig'] ?? false
            ),
            aanwezigAfgevinktOp: $this->nullableString(
                $row['aanwezig_afgevinkt_op'] ?? null
            ),
            aangemaaktOp: (string) $row['aangemaakt_op'],
            bijgewerktOp: $this->nullableString(
                $row['bijgewerkt_op'] ?? null
            ),
            lidVoornaam: $this->nullableString(
                $row['lid_voornaam'] ?? null
            ),
            lidAchternaam: $this->nullableString(
                $row['lid_achternaam'] ?? null
            ),
            lidEmail: $this->nullableString(
                $row['lid_email'] ?? null
            ),
            shiftNaam: $this->nullableString(
                $row['shift_naam'] ?? null
            ),
            shiftStartOp: $this->nullableString(
                $row['shift_start_op'] ?? null
            ),
            shiftEindOp: $this->nullableString(
                $row['shift_eind_op'] ?? null
            ),
            eventTitel: $this->nullableString(
                $row['event_titel'] ?? null
            ),
            typeNaam: $this->nullableString(
                $row['type_naam'] ?? null
            ),
            goedgekeurdDoorNaam: $this->nullableString(
                $row['goedgekeurd_door_naam'] ?? null
            ),
            geannuleerdDoorNaam: $this->nullableString(
                $row['geannuleerd_door_naam'] ?? null
            )
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public function map(array $row): ShiftRegistration
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
            'shift_id' => (int) $data['shift_id'],
            'lid_id' => (int) $data['lid_id'],
            'status' => (string) (
                $data['status']
                ?? ShiftRegistration::STATUS_WACHTEND
            ),
            'opmerking_lid' => $this->nullableString(
                $data['opmerking_lid'] ?? null
            ),
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
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