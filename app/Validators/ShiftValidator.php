<?php

declare(strict_types=1);

namespace App\Validators;

use App\Models\Event;
use App\Models\Shift;
use DateTimeImmutable;
use InvalidArgumentException;

final class ShiftValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        if ((int) ($data['event_id'] ?? 0) <= 0) {
            throw new InvalidArgumentException(
                'Kies een geldig evenement.'
            );
        }

        if ((int) ($data['type_id'] ?? 0) <= 0) {
            throw new InvalidArgumentException(
                'Kies een geldig shifttype.'
            );
        }

        $naam = trim(
            (string) ($data['naam'] ?? '')
        );

        if ($this->length($naam) > 100) {
            throw new InvalidArgumentException(
                'De shiftnaam mag maximaal 100 tekens bevatten.'
            );
        }

        $startOp = trim(
            (string) ($data['start_op'] ?? '')
        );

        $eindOp = trim(
            (string) ($data['eind_op'] ?? '')
        );

        if (!$this->isValidDateTime($startOp)) {
            throw new InvalidArgumentException(
                'De startdatum of starttijd is ongeldig.'
            );
        }

        if (!$this->isValidDateTime($eindOp)) {
            throw new InvalidArgumentException(
                'De einddatum of eindtijd is ongeldig.'
            );
        }

        $start = new DateTimeImmutable($startOp);
        $end = new DateTimeImmutable($eindOp);

        if ($end <= $start) {
            throw new InvalidArgumentException(
                'De eindtijd moet na de starttijd liggen.'
            );
        }

        if ($end > $start->modify('+1 day')) {
            throw new InvalidArgumentException(
                'Een shift mag maximaal 24 uur duren.'
            );
        }

        if ((int) ($data['max_personen'] ?? 0) < 1) {
            throw new InvalidArgumentException(
                'Het vereiste aantal vrijwilligers moet minstens 1 zijn.'
            );
        }

        $status = (string) ($data['status'] ?? '');

        if (!array_key_exists($status, Shift::statusOptions())) {
            throw new InvalidArgumentException(
                'De gekozen shiftstatus is ongeldig.'
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateForEvent(
        array $data,
        Event $event
    ): void {
        $this->validate($data);

        if ((int) $data['event_id'] !== $event->eventId) {
            throw new InvalidArgumentException(
                'De shift hoort niet bij het gekozen evenement.'
            );
        }

        $start = new DateTimeImmutable(
            (string) $data['start_op']
        );

        $end = new DateTimeImmutable(
            (string) $data['eind_op']
        );

        $eventStart = new DateTimeImmutable(
            $event->startDatum . ' 00:00:00'
        );

        $eventEnd = new DateTimeImmutable(
            ($event->eindDatum ?? $event->startDatum)
            . ' 23:59:59'
        );

        $maximumEnd = $eventEnd->modify('+1 day');

        if (
            $start < $eventStart
            || $start > $eventEnd
        ) {
            throw new InvalidArgumentException(
                'De shift moet starten binnen de periode van het evenement.'
            );
        }

        if ($end > $maximumEnd) {
            throw new InvalidArgumentException(
                'De shift mag uiterlijk de dag na het evenement eindigen.'
            );
        }

        if ($event->isCancelled()) {
            throw new InvalidArgumentException(
                'Aan een geannuleerd evenement kunnen geen shifts worden toegevoegd of gewijzigd.'
            );
        }
    }

    private function isValidDateTime(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $value
        );

        return $date instanceof DateTimeImmutable
            && $date->format('Y-m-d H:i:s') === $value;
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value)
            : strlen($value);
    }
}
