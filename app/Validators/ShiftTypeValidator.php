<?php

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

final class ShiftTypeValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        $name = trim((string) ($data['naam'] ?? ''));
        $color = trim((string) ($data['kleur'] ?? ''));
        $icon = trim((string) ($data['icoon'] ?? ''));
        $description = trim(
            (string) ($data['omschrijving'] ?? '')
        );

        if ($name === '') {
            throw new InvalidArgumentException(
                'De naam van de shiftfunctie is verplicht.'
            );
        }

        if ($this->length($name) > 100) {
            throw new InvalidArgumentException(
                'De naam van de shiftfunctie mag maximaal 100 tekens bevatten.'
            );
        }

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) {
            throw new InvalidArgumentException(
                'Kies een geldige kleur voor de shiftfunctie.'
            );
        }

        if ($this->length($icon) > 50) {
            throw new InvalidArgumentException(
                'De icoonnaam mag maximaal 50 tekens bevatten.'
            );
        }

        if ($this->length($description) > 1000) {
            throw new InvalidArgumentException(
                'De omschrijving mag maximaal 1000 tekens bevatten.'
            );
        }
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value)
            : strlen($value);
    }
}
