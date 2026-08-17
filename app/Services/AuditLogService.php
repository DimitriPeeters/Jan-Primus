<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;

final class AuditLogService
{
    public function __construct(
        private AuditLogRepository $repository
    ) {
    }

    public function created(
        string $entity,
        int $id,
        ?int $userId,
        array $values
    ): void {

        $this->repository->create(

            entity: $entity,

            entityId: $id,

            action: 'create',

            userId: $userId,

            oldValues: [],

            newValues: $values

        );
    }

    public function updated(
        string $entity,
        int $id,
        ?int $userId,
        array $oldValues,
        array $newValues
    ): void {

        $this->repository->create(

            entity: $entity,

            entityId: $id,

            action: 'update',

            userId: $userId,

            oldValues: $oldValues,

            newValues: $newValues

        );
    }

    public function deleted(
        string $entity,
        int $id,
        ?int $userId,
        array $oldValues
    ): void {

        $this->repository->create(

            entity: $entity,

            entityId: $id,

            action: 'delete',

            userId: $userId,

            oldValues: $oldValues,

            newValues: []

        );
    }

    public function history(
        string $entity,
        int $id
    ): array {

        return $this->repository->forEntity(
            $entity,
            $id
        );
    }

    public function latest(
        int $limit = 50
    ): array {

        return $this->repository->latest($limit);
    }
}