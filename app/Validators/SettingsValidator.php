<?php

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

final class SettingsValidator
{
    /**
     * @param array<string, string> $data
     */
    public function validate(array $data): void
    {
        foreach (
            [
                'application_name' => 'De platformnaam',
                'organization_name' => 'De organisatienaam',
                'mail_from_name' => 'De afzendernaam',
                'mail_reply_to_name' => 'De antwoordnaam',
            ] as $key => $label
        ) {
            $value = trim((string) ($data[$key] ?? ''));

            if ($value === '') {
                throw new InvalidArgumentException(
                    $label . ' is verplicht.'
                );
            }

            if ($this->length($value) > 150) {
                throw new InvalidArgumentException(
                    $label . ' mag maximaal 150 tekens bevatten.'
                );
            }
        }

        $replyTo = trim(
            (string) ($data['mail_reply_to_address'] ?? '')
        );

        if (
            $replyTo !== ''
            && filter_var($replyTo, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new InvalidArgumentException(
                'Het antwoordadres is geen geldig e-mailadres.'
            );
        }

        foreach (
            [
                'default_shift_compensation' => 'De standaard shiftvergoeding',
                'group_supplement' => 'De groepstoeslag',
            ] as $key => $label
        ) {
            $value = (string) ($data[$key] ?? '');

            if (
                preg_match('/^\d{1,8}\.\d{2}$/', $value) !== 1
                || (float) $value < 0
            ) {
                throw new InvalidArgumentException(
                    $label . ' moet een geldig positief bedrag met maximaal twee decimalen zijn.'
                );
            }
        }

        if (
            !in_array(
                (string) ($data['default_event_uses_groups'] ?? ''),
                ['0', '1'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'De standaardkeuze voor groepsevenementen is ongeldig.'
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
