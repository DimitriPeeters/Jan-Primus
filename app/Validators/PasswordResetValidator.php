<?php

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

final class PasswordResetValidator
{
    public function validateEmail(string $email): void
    {
        if ($email === '') {
            throw new InvalidArgumentException(
                'E-mailadres is verplicht.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'Vul een geldig e-mailadres in.'
            );
        }
    }

    public function validatePasswords(
        string $password,
        string $confirmation
    ): void {
        if ($password === '') {
            throw new InvalidArgumentException(
                'Een nieuw wachtwoord is verplicht.'
            );
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException(
                'Het wachtwoord moet minstens 8 tekens bevatten.'
            );
        }

        if ($password !== $confirmation) {
            throw new InvalidArgumentException(
                'De wachtwoorden komen niet overeen.'
            );
        }
    }
}
