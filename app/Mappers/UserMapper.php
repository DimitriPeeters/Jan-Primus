<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\User;

final class UserMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public function fromDatabase(array $row): User
    {
        return new User(
            gebruikerId: (int) ($row['gebruiker_id'] ?? 0),
            lidId: (int) ($row['lid_id'] ?? 0),
            email: (string) ($row['email'] ?? ''),
            rol: (string) ($row['rol'] ?? User::ROLE_MEMBER),
            actief: (bool) ($row['actief'] ?? false),
            mailBlacklist: (bool) ($row['mail_blacklist'] ?? false),
            passwordHash: (string) ($row['wachtwoord_hash'] ?? ''),
            resetToken: $this->nullableString($row['reset_token'] ?? null),
            resetTokenExpires: $this->nullableString(
                $row['reset_token_expires'] ?? null
            ),
            voornaam: (string) ($row['voornaam'] ?? ''),
            achternaam: (string) ($row['achternaam'] ?? '')
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function toDatabase(array $data): array
    {
        return [
            'lid_id' => (int) ($data['lid_id'] ?? 0),
            'email' => strtolower(trim((string) ($data['email'] ?? ''))),
            'rol' => (string) ($data['rol'] ?? User::ROLE_MEMBER),
            'actief' => !empty($data['actief']) ? 1 : 0,
            'mail_blacklist' => !empty($data['mail_blacklist']) ? 1 : 0,
            'wachtwoord_hash' => (string) ($data['wachtwoord_hash'] ?? ''),
            'reset_token' => $data['reset_token'] ?? null,
            'reset_token_expires' => $data['reset_token_expires'] ?? null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }
}
