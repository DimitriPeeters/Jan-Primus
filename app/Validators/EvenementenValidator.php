<?php

declare(strict_types=1);

namespace App\Validators;

use App\Models\Event;
use DateTimeImmutable;
use InvalidArgumentException;

final class EvenementenValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        $titel = trim(
            (string) ($data['titel'] ?? '')
        );

        if ($titel === '') {
            throw new InvalidArgumentException(
                'Titel is verplicht.'
            );
        }

        if ($this->length($titel) > 255) {
            throw new InvalidArgumentException(
                'Titel mag maximaal 255 tekens bevatten.'
            );
        }

        $locatie = trim(
            (string) ($data['locatie'] ?? '')
        );

        if ($this->length($locatie) > 255) {
            throw new InvalidArgumentException(
                'Locatie mag maximaal 255 tekens bevatten.'
            );
        }

        $startdatum = trim(
            (string) ($data['startdatum'] ?? '')
        );

        if ($startdatum === '') {
            throw new InvalidArgumentException(
                'Startdatum is verplicht.'
            );
        }

        if (!$this->isValidDate($startdatum)) {
            throw new InvalidArgumentException(
                'Startdatum is ongeldig.'
            );
        }

        $einddatum = $data['einddatum'] ?? null;

        if ($einddatum !== null) {
            $einddatum = trim((string) $einddatum);

            if (!$this->isValidDate($einddatum)) {
                throw new InvalidArgumentException(
                    'Einddatum is ongeldig.'
                );
            }

            if ($einddatum < $startdatum) {
                throw new InvalidArgumentException(
                    'De einddatum mag niet vóór de startdatum liggen.'
                );
            }
        }

        $maxDeelnemers = $data['max_deelnemers'] ?? null;

        if (
            $maxDeelnemers !== null
            && (int) $maxDeelnemers < 1
        ) {
            throw new InvalidArgumentException(
                'Maximum deelnemers moet minstens 1 zijn.'
            );
        }

        $status = (string) ($data['status'] ?? '');

        if (!array_key_exists($status, Event::statusOptions())) {
            throw new InvalidArgumentException(
                'De gekozen evenementstatus is ongeldig.'
            );
        }

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

    private function length(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value)
            : strlen($value);
    }
}
