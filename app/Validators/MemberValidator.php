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

        $this->validateIBAN($data);

        $this->validateNationalIdentificationNumber($data);
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

    private function validateIBAN(array $data): void
    {
        $iban = strtoupper(
            str_replace(
                ' ',
                '',
                trim((string)($data['rekeningnummer'] ?? ''))
            )
        );

        if ($iban === '') {
            return;
        }

        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {

            throw new InvalidArgumentException(
                'Ongeldig IBAN-rekeningnummer.'
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
            return;
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
}
