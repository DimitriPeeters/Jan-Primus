<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Auth;
use AEFS\Core\Database;
use App\Models\User;
use App\Repositories\MemberRepository;
use App\Repositories\UserRepository;
use InvalidArgumentException;

final class UserService
{
    public function __construct(
        private readonly Database $database,
        private readonly UserRepository $users,
        private readonly MemberRepository $members,
        private readonly AuditLogService $auditLog
    ) {
    }

    /**
     * @return User[]
     */
    public function all(): array
    {
        return $this->users->all();
    }

    /**
     * @return User[]
     */
    public function search(string $search): array
    {
        $search = trim($search);

        return $search === ''
            ? $this->all()
            : $this->users->search($search);
    }

    public function find(int $id): ?User
    {
        if ($id <= 0) {
            return null;
        }

        return $this->users->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $id,
        array $data
    ): void {
        $user = $this->find($id);

        if ($user === null) {
            throw new InvalidArgumentException(
                'Gebruiker niet gevonden.'
            );
        }

        $data = $this->sanitize($data);
        $this->validate($data);

        $member = $this->members->find($user->lidId);

        if ($member === null) {
            throw new InvalidArgumentException(
                'Het gekoppelde ledenprofiel werd niet gevonden.'
            );
        }

        if (Auth::id() === $id) {
            if (!$data['actief']) {
                throw new InvalidArgumentException(
                    'Je kunt je eigen gebruikersaccount niet deactiveren.'
                );
            }

            if ($data['rol'] !== User::ROLE_ADMIN) {
                throw new InvalidArgumentException(
                    'Je kunt je eigen administratorrol niet verwijderen.'
                );
            }
        }

        $approvePendingRegistration = $user->isPending()
            && $data['actief'];

        $this->database->transaction(
            function () use (
                $id,
                $user,
                $data,
                $approvePendingRegistration
            ): void {
                if ($approvePendingRegistration) {
                    $this->users->approve(
                        $id,
                        $data
                    );
                } else {
                    $this->users->updateAccess(
                        $id,
                        $data
                    );
                }

                $this->members->updateActiveStatus(
                    $user->lidId,
                    $data['actief']
                );
            }
        );

        $newApprovalStatus = $approvePendingRegistration
            ? User::APPROVAL_APPROVED
            : $user->goedkeuringsstatus;

        $this->auditLog->updated(
            entity: 'user',
            id: $id,
            userId: Auth::id(),
            oldValues: [
                'rol' => $user->rol,
                'goedkeuringsstatus' => $user->goedkeuringsstatus,
                'actief' => $user->actief,
                'mail_blacklist' => $user->mailBlacklist,
            ],
            newValues: [
                'rol' => $data['rol'],
                'goedkeuringsstatus' => $newApprovalStatus,
                'actief' => $data['actief'],
                'mail_blacklist' => $data['mail_blacklist'],
            ]
        );

        if ($member->actief !== $data['actief']) {
            $this->auditLog->updated(
                entity: 'member',
                id: $member->lidId,
                userId: Auth::id(),
                oldValues: [
                    'actief' => $member->actief,
                ],
                newValues: [
                    'actief' => $data['actief'],
                ]
            );
        }
    }

    public function approve(int $id): void
    {
        $user = $this->find($id);

        if ($user === null) {
            throw new InvalidArgumentException(
                'Gebruiker niet gevonden.'
            );
        }

        if (!$user->isPending()) {
            throw new InvalidArgumentException(
                'Deze registratie is al beoordeeld.'
            );
        }

        $this->update(
            $id,
            [
                'rol' => $user->rol,
                'actief' => true,
                'mail_blacklist' => $user->mailBlacklist,
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validate(array $data): void
    {
        if (
            !in_array(
                $data['rol'],
                [
                    User::ROLE_ADMIN,
                    User::ROLE_MEMBER,
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Ongeldige rol.'
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{rol: string, actief: bool, mail_blacklist: bool}
     */
    private function sanitize(array $data): array
    {
        return [
            'rol' => trim((string) ($data['rol'] ?? '')),
            'actief' => filter_var(
                $data['actief'] ?? false,
                FILTER_VALIDATE_BOOL
            ),
            'mail_blacklist' => filter_var(
                $data['mail_blacklist'] ?? false,
                FILTER_VALIDATE_BOOL
            ),
        ];
    }
}