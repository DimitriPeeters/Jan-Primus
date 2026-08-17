<?php

declare(strict_types=1);

namespace App\Validators;

use AEFS\Models\Gebruiker;
use InvalidArgumentException;

final class GebruikersValidator
{
    public function validate(
        array $data,
        bool $nieuw = true
    ): void {

        if (empty($data['lid_id'])) {

            throw new InvalidArgumentException(
                'Selecteer een lid.'
            );

        }

        if (empty(trim($data['email'] ?? ''))) {

            throw new InvalidArgumentException(
                'E-mailadres is verplicht.'
            );

        }

        if (!filter_var(
            $data['email'],
            FILTER_VALIDATE_EMAIL
        )) {

            throw new InvalidArgumentException(
                'Ongeldig e-mailadres.'
            );

        }

        if (
            $nieuw &&
            empty($data['password'])
        ) {

            throw new InvalidArgumentException(
                'Wachtwoord is verplicht.'
            );

        }

        if (
            !empty($data['password']) &&
            strlen($data['password']) < 8
        ) {

            throw new InvalidArgumentException(
                'Het wachtwoord moet minstens 8 tekens bevatten.'
            );

        }

        if (empty($data['rol'])) {

            throw new InvalidArgumentException(
                'Selecteer een rol.'
            );

        }

        $rollen = [

            Gebruiker::ROL_ADMIN,

            Gebruiker::ROL_EVENTMANAGER,

            Gebruiker::ROL_COORDINATOR,

            Gebruiker::ROL_LID

        ];

        if (
            !in_array(
                $data['rol'],
                $rollen,
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Ongeldige rol.'
            );

        }

    }
}