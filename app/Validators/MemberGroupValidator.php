<?php

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

final class MemberGroupValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        $name = trim((string) ($data['naam'] ?? ''));
        $description = trim(
            (string) ($data['beschrijving'] ?? '')
        );

        if ($name === '') {
            throw new InvalidArgumentException(
                'De groepsnaam is verplicht.'
            );
        }

        if ($this->length($name) > 100) {
            throw new InvalidArgumentException(
                'De groepsnaam mag maximaal 100 tekens bevatten.'
            );
        }

        if ($this->length($description) > 5000) {
            throw new InvalidArgumentException(
                'De groepsbeschrijving mag maximaal 5000 tekens bevatten.'
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
