<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Auth;
use AEFS\Core\Database;
use App\Models\MemberGroup;
use App\Repositories\MemberGroupRepository;
use App\Repositories\MemberRepository;
use App\Validators\MemberGroupValidator;
use InvalidArgumentException;

final class MemberGroupService
{
    public function __construct(
        private readonly Database $database,
        private readonly MemberGroupRepository $groups,
        private readonly MemberRepository $members,
        private readonly MemberGroupValidator $validator,
        private readonly AuditLogService $auditLog
    ) {
    }

    /**
     * @return MemberGroup[]
     */
    public function all(): array
    {
        return $this->groups->all();
    }

    public function find(int $id): ?MemberGroup
    {
        return $this->groups->find($id);
    }

    /**
     * @return MemberGroup[]
     */
    public function forMember(int $memberId): array
    {
        if ($memberId <= 0) {
            return [];
        }

        return $this->groups->forMember($memberId);
    }

    /**
     * @return array<int, MemberGroup>
     */
    public function membershipByMember(): array
    {
        return $this->groups->membershipByMember();
    }

    /**
     * @return int[]
     */
    public function memberIds(int $groupId): array
    {
        if ($this->groups->find($groupId) === null) {
            return [];
        }

        return $this->groups->memberIds($groupId);
    }

    /**
     * @param array{naam: string, beschrijving: string} $data
     */
    public function create(array $data): int
    {
        $this->validator->validate($data);

        if ($this->groups->existsByName($data['naam'])) {
            throw new InvalidArgumentException(
                'Er bestaat al een groep met deze naam.'
            );
        }

        return $this->database->transaction(
            function () use ($data): int {
                $groupId = $this->groups->create($data);

                $this->auditLog->created(
                    entity: 'member_group',
                    id: $groupId,
                    userId: Auth::id(),
                    values: $data
                );

                return $groupId;
            }
        );
    }

    /**
     * @param int[] $memberIds
     */
    public function syncMembers(
        int $groupId,
        array $memberIds
    ): void {
        $group = $this->groups->find($groupId);

        if ($group === null) {
            throw new InvalidArgumentException(
                'Groep niet gevonden.'
            );
        }

        $memberIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (int $memberId): int => $memberId,
                        $memberIds
                    ),
                    static fn (int $memberId): bool => $memberId > 0
                )
            )
        );

        sort($memberIds);

        $existingMemberIds = $this->members->existingIds($memberIds);

        if ($existingMemberIds !== $memberIds) {
            throw new InvalidArgumentException(
                'Een of meer geselecteerde leden bestaan niet meer.'
            );
        }

        if (
            $this->groups->memberIdsAssignedToOtherGroup(
                $groupId,
                $memberIds
            ) !== []
        ) {
            throw new InvalidArgumentException(
                'Een lid kan slechts tot één groep behoren. Verwijder het lid eerst uit de andere groep.'
            );
        }

        $oldMemberIds = $this->groups->memberIds($groupId);

        if ($oldMemberIds === $memberIds) {
            return;
        }

        $this->database->transaction(
            function () use (
                $group,
                $groupId,
                $memberIds,
                $oldMemberIds
            ): void {
                $this->groups->syncMembers(
                    $groupId,
                    $memberIds
                );

                $this->auditLog->updated(
                    entity: 'member_group',
                    id: $groupId,
                    userId: Auth::id(),
                    oldValues: [
                        'naam' => $group->naam,
                        'lid_ids' => $oldMemberIds,
                    ],
                    newValues: [
                        'naam' => $group->naam,
                        'lid_ids' => $memberIds,
                    ]
                );
            }
        );
    }
}
