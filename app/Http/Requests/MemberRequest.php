<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\BelgianDateTime;

final class MemberRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->sanitize($this->input);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        $data['voornaam'] = trim(
            (string) ($data['voornaam'] ?? '')
        );

        $data['achternaam'] = trim(
            (string) ($data['achternaam'] ?? '')
        );

        $data['email'] = strtolower(
            trim((string) ($data['email'] ?? ''))
        );

        $data['telefoon'] = trim(
            (string) ($data['telefoon'] ?? '')
        );

        $data['straat'] = trim(
            (string) ($data['straat'] ?? '')
        );

        $data['postcode'] = trim(
            (string) ($data['postcode'] ?? '')
        );

        $data['gemeente'] = trim(
            (string) ($data['gemeente'] ?? '')
        );

        $data['land'] = trim(
            (string) ($data['land'] ?? 'België')
        );

        $data['geslacht'] = trim(
            (string) ($data['geslacht'] ?? '')
        );

        $geboortedatum = BelgianDateTime::normalizeDateInput(
            $data['geboortedatum'] ?? ''
        );

        $data['geboortedatum'] = $geboortedatum !== ''
            ? $geboortedatum
            : null;

        $data['rekeningnummer'] = strtoupper(
            str_replace(
                ' ',
                '',
                (string) ($data['rekeningnummer'] ?? '')
            )
        );

        $data['rijksregisternummer'] = trim(
            (string) ($data['rijksregisternummer'] ?? '')
        );

        $data['tshirtmaat'] = trim(
            (string) ($data['tshirtmaat'] ?? '')
        );

        $data['opmerkingen'] = trim(
            (string) ($data['opmerkingen'] ?? '')
        );

        $data['actief'] = isset($data['actief'])
            && (string) $data['actief'] === '1';

        $data['gdpr_consent'] = isset($data['gdpr_consent'])
            && (string) $data['gdpr_consent'] === '1';

        return $data;
    }
}
