<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use App\Mappers\MemberMapper;
use App\Models\Member;
use PDO;

final class MemberRepository
{
    private const DEFAULT_ORDER = 'voornaam ASC, achternaam ASC';

    public function __construct(
        private readonly Database $database,
        private readonly MemberMapper $mapper
    ) {
    }

    /**
     * @return Member[]
     */
    public function all(): array
    {
        $statement = $this->database->query(
            '
            SELECT *
            FROM leden
            ORDER BY ' . self::DEFAULT_ORDER
        );

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return Member[]
     */
    public function search(string $zoekterm): array
    {
        $zoekterm = trim($zoekterm);

        if ($zoekterm === '') {
            return $this->all();
        }

        $zoekwaarde = '%' . $zoekterm . '%';

        $statement = $this->database->prepare(
            '
            SELECT *
            FROM leden
            WHERE voornaam LIKE :zoek_voornaam
               OR achternaam LIKE :zoek_achternaam
               OR email LIKE :zoek_email
               OR gemeente LIKE :zoek_gemeente
            ORDER BY ' . self::DEFAULT_ORDER
        );

        $statement->execute([
            'zoek_voornaam' => $zoekwaarde,
            'zoek_achternaam' => $zoekwaarde,
            'zoek_email' => $zoekwaarde,
            'zoek_gemeente' => $zoekwaarde,
        ]);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return Member[]
     */
    public function paginate(
        int $page = 1,
        int $perPage = 25
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $offset = ($page - 1) * $perPage;

        $statement = $this->database->prepare(
            '
            SELECT *
            FROM leden
            ORDER BY ' . self::DEFAULT_ORDER . '
            LIMIT :offset, :limit
            '
        );

        $statement->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':limit',
            $perPage,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function count(): int
    {
        $statement = $this->database->query(
            '
            SELECT COUNT(*)
            FROM leden
            '
        );

        return (int) $statement->fetchColumn();
    }

    /**
     * @param int[] $ids
     *
     * @return int[]
     */
    public function existingIds(array $ids): array
    {
        $ids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (mixed $id): int => (int) $id,
                        $ids
                    ),
                    static fn (int $id): bool => $id > 0
                )
            )
        );

        if ($ids === []) {
            return [];
        }

        sort($ids);

        $placeholders = implode(
            ', ',
            array_fill(0, count($ids), '?')
        );

        $statement = $this->database->prepare(
            'SELECT lid_id
            FROM leden
            WHERE lid_id IN (' . $placeholders . ')
            ORDER BY lid_id ASC'
        );

        $statement->execute($ids);

        return array_map(
            static fn (mixed $value): int => (int) $value,
            $statement->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function find(int $id): ?Member
    {
        if ($id <= 0) {
            return null;
        }

        $statement = $this->database->prepare(
            '
            SELECT *
            FROM leden
            WHERE lid_id = :id
            LIMIT 1
            '
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->mapper->fromDatabase($row)
            : null;
    }

    public function findByEmail(string $email): ?Member
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        $statement = $this->database->prepare(
            '
            SELECT *
            FROM leden
            WHERE LOWER(email) = :email
            LIMIT 1
            '
        );

        $statement->execute([
            'email' => $email,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->mapper->fromDatabase($row)
            : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $data = $this->mapper->toDatabase($data);

        $statement = $this->database->prepare(
            '
            INSERT INTO leden
            (
                voornaam,
                achternaam,
                email,
                telefoon,
                straat,
                postcode,
                gemeente,
                land,
                geslacht,
                geboortedatum,
                rekeningnummer,
                rijksregisternummer,
                tshirtmaat,
                opmerkingen,
                actief,
                gdpr_consent,
                gdpr_timestamp,
                aangemaakt_op,
                bijgewerkt_op
            )
            VALUES
            (
                :voornaam,
                :achternaam,
                :email,
                :telefoon,
                :straat,
                :postcode,
                :gemeente,
                :land,
                :geslacht,
                :geboortedatum,
                :rekeningnummer,
                :rijksregisternummer,
                :tshirtmaat,
                :opmerkingen,
                :actief,
                :gdpr_consent,
                :gdpr_timestamp,
                NOW(),
                NOW()
            )
            '
        );

        $statement->execute($data);

        return $this->database->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $id,
        array $data,
        bool $preserveNationalIdentificationNumber = false
    ): void {
        $data = $this->mapper->toDatabase($data);
        $data['lid_id'] = $id;

        $nationalIdentificationNumberUpdate = '';

        if ($preserveNationalIdentificationNumber) {
            unset($data['rijksregisternummer']);
        } else {
            $nationalIdentificationNumberUpdate =
                'rijksregisternummer = :rijksregisternummer,';
        }

        $statement = $this->database->prepare(
            '
            UPDATE leden
            SET
                voornaam = :voornaam,
                achternaam = :achternaam,
                email = :email,
                telefoon = :telefoon,
                straat = :straat,
                postcode = :postcode,
                gemeente = :gemeente,
                land = :land,
                geslacht = :geslacht,
                geboortedatum = :geboortedatum,
                rekeningnummer = :rekeningnummer,
                ' . $nationalIdentificationNumberUpdate . '
                tshirtmaat = :tshirtmaat,
                opmerkingen = :opmerkingen,
                actief = :actief,
                gdpr_consent = :gdpr_consent,
                gdpr_timestamp = :gdpr_timestamp,
                bijgewerkt_op = NOW()
            WHERE lid_id = :lid_id
            '
        );

        $statement->execute($data);
    }

    public function updateActiveStatus(
        int $id,
        bool $active
    ): void {
        $statement = $this->database->prepare(
            '
            UPDATE leden
            SET
                actief = :actief,
                bijgewerkt_op = NOW()
            WHERE lid_id = :id
            '
        );

        $statement->execute([
            'id' => $id,
            'actief' => $active ? 1 : 0,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return Member[]
     */
    private function mapRows(array $rows): array
    {
        return array_map(
            fn (array $row): Member => $this->mapper->fromDatabase($row),
            $rows
        );
    }
}
