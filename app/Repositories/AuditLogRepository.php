<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use PDO;

final class AuditLogRepository
{
    public function __construct(
        private Database $database
    ) {
    }

    public function create(
        string $entity,
        int $entityId,
        string $action,
        ?int $userId,
        array $oldValues = [],
        array $newValues = []
    ): void {

        $stmt = $this->database->prepare("
            INSERT INTO audit_logs
            (
                entity,
                entity_id,
                action,
                user_id,
                old_values,
                new_values,
                ip_address,
                user_agent,
                created_at
            )
            VALUES
            (
                :entity,
                :entity_id,
                :action,
                :user_id,
                :old_values,
                :new_values,
                :ip_address,
                :user_agent,
                NOW()
            )
        ");

        $stmt->execute([

            'entity'      => $entity,

            'entity_id'   => $entityId,

            'action'      => $action,

            'user_id'     => $userId,

            'old_values'  => json_encode(
                $oldValues,
                JSON_UNESCAPED_UNICODE
            ),

            'new_values'  => json_encode(
                $newValues,
                JSON_UNESCAPED_UNICODE
            ),

            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,

            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,

        ]);
    }

    public function forEntity(
        string $entity,
        int $entityId
    ): array {

        $stmt = $this->database->prepare("
            SELECT *
            FROM audit_logs
            WHERE entity = :entity
              AND entity_id = :entity_id
            ORDER BY created_at DESC
        ");

        $stmt->execute([

            'entity' => $entity,

            'entity_id' => $entityId

        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function latest(
        int $limit = 50
    ): array {

        $stmt = $this->database->prepare("
            SELECT *
            FROM audit_logs
            ORDER BY created_at DESC
            LIMIT :limit
        ");

        $stmt->bindValue(
            'limit',
            $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}