<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use PDO;

final class SettingsRepository
{
    public function __construct(
        private readonly Database $database
    ) {
    }

    /**
     * @return array<string, array{id: int, value: string}>
     */
    public function all(): array
    {
        $rows = $this->database->query(<<<'SQL'
            SELECT instelling_id, sleutel, waarde
            FROM instellingen
            ORDER BY instelling_id ASC
            SQL)->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];

        foreach ($rows as $row) {
            $settings[(string) $row['sleutel']] = [
                'id' => (int) $row['instelling_id'],
                'value' => (string) $row['waarde'],
            ];
        }

        return $settings;
    }

    public function upsert(
        string $key,
        string $value,
        ?int $userId
    ): int {
        $statement = $this->database->prepare(<<<'SQL'
            INSERT INTO instellingen
            (
                sleutel,
                waarde,
                bijgewerkt_door,
                aangemaakt_op,
                bijgewerkt_op
            )
            VALUES
            (
                :sleutel,
                :waarde,
                :bijgewerkt_door,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                instelling_id = LAST_INSERT_ID(instelling_id),
                waarde = VALUES(waarde),
                bijgewerkt_door = VALUES(bijgewerkt_door),
                bijgewerkt_op = NOW()
            SQL);

        $statement->execute([
            'sleutel' => $key,
            'waarde' => $value,
            'bijgewerkt_door' => $userId,
        ]);

        return $this->database->lastInsertId();
    }
}
