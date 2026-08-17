<?php

declare(strict_types=1);

namespace App\Validators;

use App\Models\Event;
use DateTimeImmutable;
use InvalidArgumentException;

final class EventRegistrationValidator
{
    /**
     * @param string[] $days
     */
    public function validateDays(Event $event, array $days): void
    {
        if ($days === []) {
            throw new InvalidArgumentException(
                'Kies minstens één dag waarop je beschikbaar bent.'
            );
        }

        $start = $event->startDatum;
        $end = $event->eindDatum ?? $event->startDatum;

        foreach ($days as $day) {
            if (!$this->isValidDate($day)) {
                throw new InvalidArgumentException(
                    'Een van de gekozen evenementdagen is ongeldig.'
                );
            }

            if ($day < $start || $day > $end) {
                throw new InvalidArgumentException(
                    'De gekozen dagen moeten binnen de evenementperiode liggen.'
                );
            }
        }
    }

    public function validateCancellationReason(?string $reason): void
    {
        if ($reason === null) {
            return;
        }

        $length = function_exists('mb_strlen')
            ? mb_strlen($reason)
            : strlen($reason);

        if ($length > 1000) {
            throw new InvalidArgumentException(
                'De reden van annulering mag maximaal 1000 tekens bevatten.'
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
}
