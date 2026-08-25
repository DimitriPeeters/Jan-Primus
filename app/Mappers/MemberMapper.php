<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\Member;
use App\Services\EncryptionService;

final class MemberMapper
{
    public function __construct(
        private readonly EncryptionService $encryption
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function fromDatabase(array $row): Member
    {
        $encryptedNationalIdentificationNumber = $this->nullableString(
            $row['rijksregisternummer'] ?? null
        );

        $nationalIdentificationNumberIsUnreadable = $this->encryption
            ->isUndecryptableLegacyValue(
                $encryptedNationalIdentificationNumber
            );

        return new Member(
            lidId: (int) ($row['lid_id'] ?? 0),

            voornaam: $this->stringValue(
                $row['voornaam'] ?? ''
            ),

            achternaam: $this->stringValue(
                $row['achternaam'] ?? ''
            ),

            email: $this->nullableString(
                $row['email'] ?? null
            ),

            telefoon: $this->nullableString(
                $row['telefoon'] ?? null
            ),

            straat: $this->nullableString(
                $row['straat'] ?? null
            ),

            postcode: $this->nullableString(
                $row['postcode'] ?? null
            ),

            gemeente: $this->nullableString(
                $row['gemeente'] ?? null
            ),

            land: $this->nullableString(
                $row['land'] ?? null
            ),

            geslacht: $this->nullableString(
                $row['geslacht'] ?? null
            ),

            geboortedatum: $this->nullableString(
                $row['geboortedatum'] ?? null
            ),

            rijksregisternummer: $nationalIdentificationNumberIsUnreadable
                ? null
                : $this->encryption->decrypt(
                    $encryptedNationalIdentificationNumber
                ),

            tshirtmaat: $this->nullableString(
                $row['tshirtmaat'] ?? null
            ),

            actief: (bool) ($row['actief'] ?? false),

            gdprConsent: (bool) ($row['gdpr_consent'] ?? false),

            gdprTimestamp: $this->nullableString(
                $row['gdpr_timestamp'] ?? null
            ),

            opmerkingen: $this->nullableString(
                $row['opmerkingen'] ?? null
            ),

            aangemaaktOp: $this->nullableString(
                $row['aangemaakt_op'] ?? null
            ),

            bijgewerktOp: $this->nullableString(
                $row['bijgewerkt_op'] ?? null
            ),

            toegetredenOp: $this->nullableString(
                $row['toegetreden_op'] ?? null
            ),

            uitgetredenOp: $this->nullableString(
                $row['uitgetreden_op'] ?? null
            ),

            nationaalIdentificatienummerOnleesbaar:
                $nationalIdentificationNumberIsUnreadable
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function toDatabase(array $data): array
    {
        $gdprConsent = $this->booleanValue(
            $data['gdpr_consent'] ?? false
        );

        return [
            'voornaam' => $this->stringValue(
                $data['voornaam'] ?? ''
            ),

            'achternaam' => $this->stringValue(
                $data['achternaam'] ?? ''
            ),

            'telefoon' => $this->nullableString(
                $data['telefoon'] ?? null
            ),

            'straat' => $this->nullableString(
                $data['straat'] ?? null
            ),

            'postcode' => $this->nullableString(
                $data['postcode'] ?? null
            ),

            'gemeente' => $this->nullableString(
                $data['gemeente'] ?? null
            ),

            'land' => $this->nullableString(
                $data['land'] ?? null
            ),

            'geslacht' => $this->nullableString(
                $data['geslacht'] ?? null
            ),

            'geboortedatum' => $this->nullableString(
                $data['geboortedatum'] ?? null
            ),

            'rijksregisternummer' => $this->encryption->encrypt(
                $this->nullableString(
                    $data['rijksregisternummer'] ?? null
                )
            ),

            'tshirtmaat' => $this->nullableString(
                $data['tshirtmaat'] ?? null
            ),

            'opmerkingen' => $this->nullableString(
                $data['opmerkingen'] ?? null
            ),

            'actief' => $this->booleanValue(
                $data['actief'] ?? false
            ) ? 1 : 0,

            'gdpr_consent' => $gdprConsent ? 1 : 0,

            'gdpr_timestamp' => $gdprConsent
                ? $this->gdprTimestamp($data)
                : null,

            'toegetreden_op' => $this->nullableString(
                $data['toegetreden_op'] ?? null
            ),

            'uitgetreden_op' => $this->nullableString(
                $data['uitgetreden_op'] ?? null
            ),
        ];
    }

    private function stringValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        $value = strtolower(
            trim((string) $value)
        );

        return in_array(
            $value,
            [
                '1',
                'true',
                'on',
                'yes',
                'ja',
            ],
            true
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function gdprTimestamp(array $data): string
    {
        $existingTimestamp = $this->nullableString(
            $data['gdpr_timestamp'] ?? null
        );

        return $existingTimestamp ?? date('Y-m-d H:i:s');
    }
}
