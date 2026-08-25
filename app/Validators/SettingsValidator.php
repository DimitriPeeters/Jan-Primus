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

    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value)
            : strlen($value);
    }
}
