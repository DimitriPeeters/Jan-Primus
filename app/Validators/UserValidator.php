<?php

declare(strict_types=1);

namespace App\Validators;

final class UserValidator
{
    /**
     * @return array<string,string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        $email = trim((string)($data['email'] ?? ''));
        $rol = trim((string)($data['rol'] ?? ''));

        if ($email === '') {
            $errors['email'] = 'E-mailadres is verplicht.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ongeldig e-mailadres.';
        }

        if ($rol === '') {
            $errors['rol'] = 'Rol is verplicht.';
        }

        return $errors;
    }
}