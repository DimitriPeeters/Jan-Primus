<?php

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

final class LedenValidator
{
    public function validate(array $data): void
    {
        if (empty(trim($data['voornaam'] ?? ''))) {

            throw new InvalidArgumentException(
                'Voornaam is verplicht.'
            );

        }

        if (empty(trim($data['achternaam'] ?? ''))) {

            throw new InvalidArgumentException(
                'Achternaam is verplicht.'
            );

        }

        if (
            !empty($data['email']) &&
            !filter_var(
                $data['email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {

            throw new InvalidArgumentException(
                'Ongeldig e-mailadres.'
            );

        }

        if (
            !empty($data['geboortedatum']) &&
            !$this->isValidDate(
                $data['geboortedatum']
            )
        ) {

            throw new InvalidArgumentException(
                'Ongeldige geboortedatum.'
            );

        }

        if (
            !empty($data['rekeningnummer']) &&
            !$this->isValidIBAN(
                $data['rekeningnummer']
            )
        ) {

            throw new InvalidArgumentException(
                'Ongeldig IBAN-nummer.'
            );

        }

        if (
            !empty($data['land']) &&
            mb_strlen($data['land']) > 100
        ) {

            throw new InvalidArgumentException(
                'Land is te lang.'
            );

        }

        if (
            !empty($data['geslacht']) &&
            !in_array(
                $data['geslacht'],
                ['M','V','X'],
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Ongeldig geslacht.'
            );

        }
    }

    private function isValidDate(
        string $datum
    ): bool {

        $d = \DateTime::createFromFormat(
            'Y-m-d',
            $datum
        );

        return $d !== false
            && $d->format('Y-m-d') === $datum;
    }

    private function isValidIBAN(
        string $iban
    ): bool {

        $iban = strtoupper(
            preg_replace('/\s+/', '', $iban)
        );

        return (bool) preg_match(
            '/^[A-Z]{2}[0-9]{2}[A-Z0-9]{10,30}$/',
            $iban
        );
    }
}