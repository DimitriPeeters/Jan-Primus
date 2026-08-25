<?php

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

final class MemberValidator
{
    public function validate(array $data): void
    {
        $this->validateVoornaam($data);

        $this->validateAchternaam($data);

        $this->validateEmail($data);

        $this->validatePostcode($data);

        $this->validateNationalIdentificationNumber($data);

        $this->validateRequiredDetails($data);
    }

    private function validateVoornaam(array $data): void
    {
        if (trim((string)($data['voornaam'] ?? '')) === '') {

            throw new InvalidArgumentException(
                'Voornaam is verplicht.'
            );

        }
    }

    private function validateAchternaam(array $data): void
    {
        if (trim((string)($data['achternaam'] ?? '')) === '') {

            throw new InvalidArgumentException(
                'Achternaam is verplicht.'
            );

        }
    }

    private function validateEmail(array $data): void
    {
        $email = trim((string)($data['email'] ?? ''));

        if (
            $email !== '' &&
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            throw new InvalidArgumentException(
                'Ongeldig e-mailadres.'
            );
        }
    }

    private function validatePostcode(array $data): void
    {
        $postcode = trim((string)($data['postcode'] ?? ''));

        if (
            $postcode !== '' &&
            !ctype_digit($postcode)
        ) {
            throw new InvalidArgumentException(
                'Postcode moet numeriek zijn.'
            );
        }
    }

    private function validateNationalIdentificationNumber(
        array $data
    ): void {
        $number = trim(
            (string) ($data['rijksregisternummer'] ?? '')
        );

        if ($number === '') {
            throw new InvalidArgumentException(
                'Nationaal identificatienummer is verplicht.'
            );
        }

        $length = function_exists('mb_strlen')
            ? mb_strlen($number)
            : strlen($number);

        if ($length > 100) {
            throw new InvalidArgumentException(
                'Het nationale identificatienummer mag maximaal 100 tekens bevatten.'
            );
        }
    }

    private function validateRequiredDetails(array $data): void
    {
        $required = [
            'telefoon' => 'Telefoonnummer',
            'straat' => 'Straat en huisnummer',
            'postcode' => 'Postcode',
            'gemeente' => 'Gemeente',
            'land' => 'Land',
            'geboortedatum' => 'Geboortedatum',
            'geslacht' => 'Geslacht',
            'tshirtmaat' => 'T-shirtmaat',
        ];

        foreach ($required as $field => $label) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException($label . ' is verplicht.');
            }
        }

        $birthDate = (string) $data['geboortedatum'];
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
        if ($date === false || $date->format('Y-m-d') !== $birthDate) {
            throw new InvalidArgumentException('Ongeldige geboortedatum.');
        }

        if (!in_array((string) $data['geslacht'], ['M', 'V', 'X'], true)) {
            throw new InvalidArgumentException('Ongeldige keuze voor geslacht.');
        }

        if (!in_array(
            (string) $data['tshirtmaat'],
            ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            true
        )) {
            throw new InvalidArgumentException('Ongeldige T-shirtmaat.');
        }
    }
}
