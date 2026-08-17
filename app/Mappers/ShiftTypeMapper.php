<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\ShiftType;

final class ShiftTypeMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public function fromDatabase(array $row): ShiftType
    {
        return new ShiftType(
            typeId: (int) $row['type_id'],
            naam: (string) $row['naam'],
            kleur: (string) (
                $row['kleur'] ?? ShiftType::DEFAULT_COLOR
            ),
            icoon: $this->nullableString(
                $row['icoon'] ?? null
            ),
            omschrijving: $this->nullableString(
                $row['omschrijving'] ?? null
            ),
            actief: (bool) ($row['actief'] ?? true),
            aangemaaktOp: (string) $row['aangemaakt_op'],
            bijgewerktOp: $this->nullableString(
                $row['bijgewerkt_op'] ?? null
            )
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public function map(array $row): ShiftType
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
            'naam' => trim((string) $data['naam']),
            'kleur' => trim(
                (string) (
                    $data['kleur']
                    ?? ShiftType::DEFAULT_COLOR
                )
            ),
            'icoon' => $this->nullableString(
                $data['icoon'] ?? null
            ),
            'omschrijving' => $this->nullableString(
                $data['omschrijving'] ?? null
            ),
            'actief' => (bool) ($data['actief'] ?? true)
                ? 1
                : 0,
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