<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use PDO;

final class DailyAttendanceRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return array<int, array{walkieNumber: string, earpiece: bool}> */
    public function forDate(string $date): array
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT lid_id, nummer_walkie, oortje
            FROM dag_aanwezigheden
            WHERE datum = :datum
            SQL);
        $statement->execute(['datum' => $date]);
        $details = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $details[(int) $row['lid_id']] = [
                'walkieNumber' => trim((string) ($row['nummer_walkie'] ?? '')),
                'earpiece' => (bool) $row['oortje'],
            ];
        }

        return $details;
    }

    public function upsert(
        string $date,
        int $memberId,
        string $walkieNumber,
        bool $earpiece
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            INSERT INTO dag_aanwezigheden
                (datum, lid_id, nummer_walkie, oortje, aangemaakt_op, bijgewerkt_op)
            VALUES
                (:datum, :lid_id, :nummer_walkie, :oortje, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                nummer_walkie = VALUES(nummer_walkie),
                oortje = VALUES(oortje),
                bijgewerkt_op = NOW()
            SQL);
        $statement->execute([
            'datum' => $date,
            'lid_id' => $memberId,
            'nummer_walkie' => $walkieNumber !== '' ? $walkieNumber : null,
            'oortje' => $earpiece ? 1 : 0,
        ]);
    }
}
