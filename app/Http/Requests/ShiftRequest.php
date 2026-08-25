<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Shift;
use App\Support\BelgianDateTime;
use DateTimeImmutable;

final class ShiftRequest
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
        $datum = BelgianDateTime::normalizeDateInput(
            $this->input['shift_datum'] ?? ''
        );

        $starttijd = $this->normalizeTime(
            $this->input['starttijd'] ?? ''
        );

        $eindtijd = $this->normalizeTime(
            $this->input['eindtijd'] ?? ''
        );

        return [
            'event_id' => (int) (
                $this->input['event_id'] ?? 0
            ),
            'type_id' => (int) (
                $this->input['type_id'] ?? 0
            ),
            'naam' => $this->nullableString(
                $this->input['naam'] ?? null
            ),
            'start_op' => $this->combineDateAndTime(
                $datum,
                $starttijd
            ),
            'eind_op' => $this->endDateTime(
                $datum,
                $starttijd,
                $eindtijd
            ),
            'max_personen' => (int) (
                $this->input['max_personen'] ?? 0
            ),
            'status' => trim(
                (string) (
                    $this->input['status']
                    ?? Shift::STATUS_ACTIEF
                )
            ),
        ];
    }

    private function endDateTime(
        string $datum,
        string $starttijd,
        string $eindtijd
    ): string {
        $combined = $this->combineDateAndTime(
            $datum,
            $eindtijd
        );

        if (
            !$this->isValidDate($datum)
            || !$this->isValidTime($starttijd)
            || !$this->isValidTime($eindtijd)
            || $eindtijd >= $starttijd
        ) {
            return $combined;
        }

        return (new DateTimeImmutable($combined))
            ->modify('+1 day')
            ->format('Y-m-d H:i:s');
    }

    private function combineDateAndTime(
        string $datum,
        string $tijd
    ): string {
        return trim($datum . ' ' . $tijd);
    }

    private function normalizeTime(mixed $value): string
    {
        $value = trim((string) $value);

        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        return $value;
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        return $date instanceof DateTimeImmutable
            && $date->format('Y-m-d') === $value;
    }

    private function isValidTime(string $value): bool
    {
        $time = DateTimeImmutable::createFromFormat(
            '!H:i:s',
            $value
        );

        return $time instanceof DateTimeImmutable
            && $time->format('H:i:s') === $value;
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
