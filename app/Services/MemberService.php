<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Auth;
use AEFS\Core\Database;
use App\Models\Member;
use App\Repositories\MemberRepository;
use App\Repositories\UserRepository;
use App\Validators\MemberValidator;
use InvalidArgumentException;

final class MemberService
{
    public function __construct(
        private readonly Database $database,
        private readonly MemberRepository $members,
        private readonly UserRepository $users,
        private readonly MemberValidator $validator,
        private readonly AuditLogService $auditLog
    ) {
    }

    /**
     * @return Member[]
     */
    public function all(): array
    {
        return $this->members->all();
    }

    /**
     * @return Member[]
     */
    public function search(string $zoekterm): array
    {
        $zoekterm = trim($zoekterm);

        return $zoekterm === ''
            ? $this->all()
            : $this->members->search($zoekterm);
    }

    public function find(int $id): ?Member
    {
        if ($id <= 0) {
            return null;
        }

        return $this->members->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $id,
        array $data
    ): void {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ongeldig lid.'
            );
        }

        $this->validator->validate($data);

        $member = $this->members->find($id);

        if ($member === null) {
            throw new InvalidArgumentException(
                'Lid niet gevonden.'
            );
        }

        $data = $this->sanitize($data);
        $data['gdpr_timestamp'] = $member->gdprTimestamp;

        $preserveNationalIdentificationNumber =
            $member->nationaalIdentificatienummerOnleesbaar
            && trim(
                (string) ($data['rijksregisternummer'] ?? '')
            ) === '';

        $account = $this->users->findByMemberId($id);

        if ($account === null) {
            throw new InvalidArgumentException(
                'Ieder lid moet een gekoppeld gebruikersaccount hebben.'
            );
        }

        $email = strtolower(
            trim((string) ($data['email'] ?? ''))
        );

        if ($email === '') {
            throw new InvalidArgumentException(
                'E-mailadres is verplicht voor een gekoppeld gebruikersaccount.'
            );
        }

        if ($email !== '') {
            $userWithEmail = $this->users->findByEmail($email);

            if (
                $userWithEmail !== null
                && $userWithEmail->lidId !== $id
            ) {
                throw new InvalidArgumentException(
                    'Dit e-mailadres is reeds in gebruik.'
                );
            }
        }

        $data['toegetreden_op'] = $member->toegetredenOp;
        $data['uitgetreden_op'] = $member->uitgetredenOp;

        if ((bool) $data['actief'] && !$member->actief) {
            $data['toegetreden_op'] ??= date('Y-m-d');
            $data['uitgetreden_op'] = null;
        } elseif (!(bool) $data['actief'] && $member->actief) {
            $data['uitgetreden_op'] ??= date('Y-m-d');
        }

        $this->database->transaction(
            function () use (
                $id,
                $data,
                $account,
                $email,
                $preserveNationalIdentificationNumber
            ): void {
                $this->members->update(
                    $id,
                    $data,
                    $preserveNationalIdentificationNumber
                );

                $this->users->updateEmailAndActiveByMemberId(
                    $id,
                    $email,
                    (bool) $data['actief']
                );
            }
        );

        $this->auditLog->updated(
            entity: 'member',
            id: $id,
            userId: Auth::id(),
            oldValues: $this->auditValues(
                get_object_vars($member)
            ),
            newValues: $this->auditValues($data)
        );


        if (
            (
                $account->email !== $email
                || $account->actief !== (bool) $data['actief']
            )
        ) {
            $this->auditLog->updated(
                entity: 'user',
                id: $account->gebruikerId,
                userId: Auth::id(),
                oldValues: [
                    'email' => $account->email,
                    'rol' => $account->rol,
                    'actief' => $account->actief,
                    'mail_blacklist' => $account->mailBlacklist,
                ],
                newValues: [
                    'email' => $email,
                    'rol' => $account->rol,
                    'actief' => (bool) $data['actief'],
                    'mail_blacklist' => $account->mailBlacklist,
                ]
            );
        }
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

        $data['email'] = strtolower(
            trim((string) ($data['email'] ?? ''))
        );

        $data['actief'] = filter_var(
            $data['actief'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        $data['gdpr_consent'] = filter_var(
            $data['gdpr_consent'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        if (empty($data['land'])) {
            $data['land'] = 'België';
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function auditValues(array $values): array
    {
        if (array_key_exists('rijksregisternummer', $values)) {
            $values['rijksregisternummer'] = '[afgeschermd]';
        }

        return $values;
    }
}
