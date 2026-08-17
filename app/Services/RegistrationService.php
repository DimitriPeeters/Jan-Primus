<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Database;
use App\Models\User;
use App\Repositories\MemberRepository;
use App\Repositories\UserRepository;
use App\Validators\MemberValidator;
use InvalidArgumentException;

final class RegistrationService
{
    public function __construct(
        private readonly Database $database,
        private readonly MemberRepository $members,
        private readonly UserRepository $users,
        private readonly MemberValidator $memberValidator,
        private readonly AuditLogService $auditLog
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function register(array $data): void
    {
        $this->validate($data);

        $email = strtolower(
            trim((string) $data['email'])
        );

        if (
            $this->members->findByEmail($email) !== null
            || $this->users->findByEmail($email) !== null
        ) {
            throw new InvalidArgumentException(
                'Er bestaat al een registratie met dit e-mailadres.'
            );
        }

        $memberData = $data;
        $memberData['email'] = $email;
        $memberData['actief'] = false;
        $memberData['gdpr_consent'] = true;
        $memberData['opmerkingen'] = '';

        $this->database->transaction(
            function () use ($memberData, $data, $email): void {
                $memberId = $this->members->create(
                    $memberData
                );

                $userId = $this->users->create([
                    'lid_id' => $memberId,
                    'email' => $email,
                    'password' => (string) $data['password'],
                    'rol' => User::ROLE_MEMBER,
                    'goedkeuringsstatus' => User::APPROVAL_PENDING,
                    'goedgekeurd_op' => null,
                    'actief' => false,
                    'mail_blacklist' => false,
                ]);

                $this->auditLog->created(
                    entity: 'member',
                    id: $memberId,
                    userId: null,
                    values: $this->memberAuditValues($memberData)
                );

                $this->auditLog->created(
                    entity: 'user',
                    id: $userId,
                    userId: null,
                    values: [
                        'lid_id' => $memberId,
                        'email' => $email,
                        'rol' => User::ROLE_MEMBER,
                        'goedkeuringsstatus' => User::APPROVAL_PENDING,
                        'actief' => false,
                        'mail_blacklist' => false,
                    ]
                );
            }
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validate(array $data): void
    {
        $this->memberValidator->validate($data);

        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '') {
            throw new InvalidArgumentException(
                'E-mailadres is verplicht.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'Ongeldig e-mailadres.'
            );
        }

        $password = (string) ($data['password'] ?? '');
        $confirmation = (string) (
            $data['password_confirmation'] ?? ''
        );

        if (strlen($password) < 8) {
            throw new InvalidArgumentException(
                'Het wachtwoord moet minstens 8 tekens bevatten.'
            );
        }

        if ($password !== $confirmation) {
            throw new InvalidArgumentException(
                'De wachtwoorden komen niet overeen.'
            );
        }

        if (empty($data['gdpr_consent'])) {
            throw new InvalidArgumentException(
                'Je moet akkoord gaan met de privacyverklaring.'
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function memberAuditValues(array $data): array
    {
        unset(
            $data['password'],
            $data['password_confirmation'],
            $data['rekeningnummer'],
            $data['rijksregisternummer'],
            $data['_token']
        );

        return $data;
    }
}