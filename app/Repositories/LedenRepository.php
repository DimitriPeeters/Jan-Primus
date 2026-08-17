<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\BaseRepository;
use AEFS\Core\Database;
use App\Models\Lid;
use PDO;

final class LedenRepository extends BaseRepository
{
    protected string $table = 'leden';

    protected string $primaryKey = 'lid_id';

    public function __construct(
        Database $database
    ) {
        parent::__construct($database);
    }

    /**
     * @return Lid[]
     */
    public function all(
        string $orderBy = 'voornaam',
        string $direction = 'ASC'
    ): array {

        return parent::all(
            $orderBy,
            $direction
        );
    }

    /**
     * @return Lid[]
     */
    public function search(
        string $zoekterm
    ): array {

        $zoekterm = '%' . trim($zoekterm) . '%';

        $rows = $this->fetchAll(
            "
            SELECT *
            FROM leden
            WHERE

                voornaam LIKE :zoek

                OR achternaam LIKE :zoek

                OR email LIKE :zoek

                OR gemeente LIKE :zoek

            ORDER BY

                voornaam,
                achternaam
            ",
            [
                'zoek' => $zoekterm
            ]
        );

        return array_map(
            [$this, 'map'],
            $rows
        );
    }

    /**
     * @return Lid[]
     */
    public function paginate(
        int $page,
        int $perPage = 25
    ): array {

        $offset = ($page - 1) * $perPage;

        $stmt = $this->database->prepare("
            SELECT *
            FROM leden
            ORDER BY
                voornaam,
                achternaam
            LIMIT :offset, :limit
        ");

        $stmt->bindValue(
            'offset',
            $offset,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            'limit',
            $perPage,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return array_map(

            [$this, 'map'],

            $stmt->fetchAll(PDO::FETCH_ASSOC)

        );
    }

    public function create(
        array $data
    ): int {

        $sql = "
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
                gdpr_timestamp
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
                NOW()
            )
        ";

        $this->execute(
            $sql,
            $data
        );

        return $this->lastInsertId();
    }

    public function update(
        int $id,
        array $data
    ): void {

        $data['lid_id'] = $id;

        $sql = "
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

                rijksregisternummer = :rijksregisternummer,

                tshirtmaat = :tshirtmaat,

                opmerkingen = :opmerkingen,

                actief = :actief,

                gdpr_consent = :gdpr_consent

            WHERE

                lid_id = :lid_id
        ";

        $this->execute(
            $sql,
            $data
        );
    }

    protected function map(
        array $row
    ): Lid {

        return new Lid(

            lidId: (int) $row['lid_id'],

            voornaam: $row['voornaam'],

            achternaam: $row['achternaam'],

            email: $row['email'],

            telefoon: $row['telefoon'],

            straat: $row['straat'],

            postcode: $row['postcode'],

            gemeente: $row['gemeente'],

            land: $row['land'],

            geslacht: $row['geslacht'],

            geboortedatum: $row['geboortedatum'],

            rekeningnummer: $row['rekeningnummer'],

            rijksregisternummer: $row['rijksregisternummer'],

            tshirtmaat: $row['tshirtmaat'],

            opmerkingen: $row['opmerkingen'],

            actief: (bool) $row['actief'],

            gdprConsent: (bool) $row['gdpr_consent']

        );
    }
}