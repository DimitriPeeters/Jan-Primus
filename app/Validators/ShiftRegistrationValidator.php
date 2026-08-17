<?php

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

final class ShiftRegistrationValidator
{
    public function validateComment(?string $comment): void
    {
        if ($comment === null) {
            return;
        }

        if ($this->length($comment) > 1000) {
            throw new InvalidArgumentException(
                'De opmerking mag maximaal 1000 tekens bevatten.'
            );
        }
    }

    public function validateCancellationReason(
        ?string $reason
    ): void {
        if ($reason === null) {
            return;
        }

        if ($this->length($reason) > 1000) {
            throw new InvalidArgumentException(
                'De reden van annulering mag maximaal 1000 tekens bevatten.'
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