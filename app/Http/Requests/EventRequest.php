<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\BelgianDateTime;

final class EventRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $maxDeelnemers = trim(
            (string) ($this->input['max_deelnemers'] ?? '')
        );

        $einddatum = BelgianDateTime::normalizeDateInput(
            $this->input['einddatum'] ?? ''
        );

        $beschrijving = trim(
            (string) ($this->input['beschrijving'] ?? '')
        );

        $locatie = trim(
            (string) ($this->input['locatie'] ?? '')
        );

        return [
            'titel' => trim(
                (string) ($this->input['titel'] ?? '')
            ),
            'beschrijving' => $beschrijving !== ''
                ? $beschrijving
                : null,
            'locatie' => $locatie !== ''
                ? $locatie
                : null,
            'max_deelnemers' => $maxDeelnemers !== ''
                ? (int) $maxDeelnemers
                : null,
            'startdatum' => BelgianDateTime::normalizeDateInput(
                $this->input['startdatum'] ?? ''
            ),
            'einddatum' => $einddatum !== ''
                ? $einddatum
                : null,
            'status' => trim(
                (string) ($this->input['status'] ?? 'concept')
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function shifts(): array
    {
        $rows = $this->input['shifts'] ?? [];

        if (!is_array($rows)) {
            return [];
        }

        $shifts = [];

        foreach ($rows as $row) {
            if (!is_array($row) || !$this->hasShiftInput($row)) {
                continue;
            }

            $shifts[] = (new ShiftRequest($row))->all();
        }

        return $shifts;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hasShiftInput(array $row): bool
    {
        foreach (
            [
                'type_id',
                'naam',
                'shift_datum',
                'starttijd',
                'eindtijd',
                'max_personen',
            ] as $key
        ) {
            if (trim((string) ($row[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

}
