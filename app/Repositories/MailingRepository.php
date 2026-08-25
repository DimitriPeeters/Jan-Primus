<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use App\Mappers\MailingMapper;
use App\Models\Mailing;
use App\Models\MailingRecipient;
use PDO;

final class MailingRepository
{
    private const SELECT_MAILING = <<<'SQL'
        SELECT
            m.*,
            e.titel AS event_titel,
            NULLIF(
                TRIM(CONCAT_WS(' ', maker.voornaam, maker.achternaam)),
                ''
            ) AS maker_naam,
            (
                SELECT COUNT(*)
                FROM mailing_ontvangers total
                WHERE total.mailing_id = m.mailing_id
            ) AS ontvangers_aantal,
            (
                SELECT COUNT(*)
                FROM mailing_ontvangers queued
                WHERE queued.mailing_id = m.mailing_id
                  AND queued.status IN ('in_wachtrij', 'bezig')
            ) AS wachtrij_aantal,
            (
                SELECT COUNT(*)
                FROM mailing_ontvangers sent
                WHERE sent.mailing_id = m.mailing_id
                  AND sent.status = 'verzonden'
            ) AS verzonden_aantal,
            (
                SELECT COUNT(*)
                FROM mailing_ontvangers failed
                WHERE failed.mailing_id = m.mailing_id
                  AND failed.status = 'mislukt'
            ) AS mislukt_aantal
        FROM mailings m
        LEFT JOIN evenementen e
            ON e.event_id = m.event_id
        LEFT JOIN gebruikers creator
            ON creator.gebruiker_id = m.aangemaakt_door
        LEFT JOIN leden maker
            ON maker.lid_id = creator.lid_id
        SQL;

    public function __construct(
        private readonly Database $database,
        private readonly MailingMapper $mapper
    ) {
    }

    /**
     * @return Mailing[]
     */
    public function latest(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $statement = $this->database->query(
            self::SELECT_MAILING
            . PHP_EOL
            . 'ORDER BY m.mailing_id DESC'
            . PHP_EOL
            . 'LIMIT ' . $limit
        );

        return array_map(
            fn(array $row): Mailing => $this->mapper->mailing($row),
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function find(int $mailingId): ?Mailing
    {
        $statement = $this->database->prepare(
            self::SELECT_MAILING
            . PHP_EOL
            . 'WHERE m.mailing_id = :mailing_id'
            . PHP_EOL
            . 'LIMIT 1'
        );
        $statement->execute([
            'mailing_id' => $mailingId,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->mapper->mailing($row)
            : null;
    }

    /**
     * @return MailingRecipient[]
     */
    public function recipients(int $mailingId): array
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT
                ontvanger_id,
                mailing_id,
                lid_id,
                email,
                naam,
                status,
                pogingen,
                volgende_poging_op,
                verzonden_op,
                foutmelding
            FROM mailing_ontvangers
            WHERE mailing_id = :mailing_id
            ORDER BY
                FIELD(status, 'mislukt', 'bezig', 'in_wachtrij', 'verzonden'),
                naam ASC,
                email ASC
            SQL);
        $statement->execute([
            'mailing_id' => $mailingId,
        ]);

        return array_map(
            fn(array $row): MailingRecipient => $this->mapper->recipient($row),
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return array{queued: int, sent: int, failed: int, total: int}
     */
    public function totals(): array
    {
        $row = $this->database->query(<<<'SQL'
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status IN ('in_wachtrij', 'bezig')), 0) AS queued,
                COALESCE(SUM(status = 'verzonden'), 0) AS sent,
                COALESCE(SUM(status = 'mislukt'), 0) AS failed
            FROM mailing_ontvangers
            SQL)->fetch(PDO::FETCH_ASSOC);

        return [
            'queued' => (int) ($row['queued'] ?? 0),
            'sent' => (int) ($row['sent'] ?? 0),
            'failed' => (int) ($row['failed'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
        ];
    }

    /**
     * @return array{
     *     events: array<int, array{id: int, label: string}>,
     *     shifts: array<int, array{id: int, event_id: int, label: string}>
     * }
     */
    public function audienceOptions(): array
    {
        $events = $this->database->query(<<<'SQL'
            SELECT
                event_id AS id,
                CONCAT(
                    titel,
                    ' · ',
                    DATE_FORMAT(startdatum, '%d/%m/%Y')
                ) AS label
            FROM evenementen
            ORDER BY startdatum DESC, titel ASC
            SQL)->fetchAll(PDO::FETCH_ASSOC);

        $shifts = $this->database->query(<<<'SQL'
            SELECT
                s.shift_id AS id,
                s.event_id,
                CONCAT(
                    e.titel,
                    ' · ',
                    COALESCE(NULLIF(s.naam, ''), st.naam),
                    ' · ',
                    DATE_FORMAT(s.start_op, '%d/%m/%Y %H:%i')
                ) AS label
            FROM shifts s
            INNER JOIN evenementen e
                ON e.event_id = s.event_id
            INNER JOIN shift_types st
                ON st.type_id = s.type_id
            WHERE s.status = 'actief'
            ORDER BY e.startdatum DESC, s.start_op ASC, s.shift_id ASC
            SQL)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'events' => array_map(
                static fn(array $row): array => [
                    'id' => (int) $row['id'],
                    'label' => (string) $row['label'],
                ],
                $events
            ),
            'shifts' => array_map(
                static fn(array $row): array => [
                    'id' => (int) $row['id'],
                    'event_id' => (int) $row['event_id'],
                    'label' => (string) $row['label'],
                ],
                $shifts
            ),
        ];
    }

    /**
     * @return array<int, array{
     *     lid_id: int,
     *     voornaam: string,
     *     achternaam: string,
     *     naam: string,
     *     email: string
     * }>
     */
    public function eligibleAllMembers(): array
    {
        return $this->eligibleMembers();
    }

    /**
     * @param int[] $eventIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function eligibleMembersByEvents(array $eventIds): array
    {
        return $this->eligibleMembers(
            'EXISTS (
                SELECT 1
                FROM event_inschrijvingen ei
                WHERE ei.lid_id = l.lid_id
                  AND ei.event_id IN (' . $this->integerList($eventIds) . ')
                  AND ei.status IN (\'wachtend\', \'bevestigd\', \'reserve\')
                  AND ei.uitgeschreven_op IS NULL
            )'
        );
    }

    /**
     * @param int[] $shiftIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function eligibleMembersByShifts(array $shiftIds): array
    {
        return $this->eligibleMembers(
            'EXISTS (
                SELECT 1
                FROM shift_inschrijvingen si
                WHERE si.lid_id = l.lid_id
                  AND si.shift_id IN (' . $this->integerList($shiftIds) . ')
                  AND si.status IN (\'bevestigd\', \'reserve\')
            )'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function eligibleMember(int $memberId): ?array
    {
        $members = $this->eligibleMembers(
            'l.lid_id = ' . max(0, $memberId)
        );

        return $members[0] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function eligibleEventCancellationRecipients(
        int $eventId
    ): array {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT
                l.lid_id,
                l.voornaam,
                l.achternaam,
                NULLIF(
                    TRIM(CONCAT_WS(' ', l.voornaam, l.achternaam)),
                    ''
                ) AS naam,
                LOWER(TRIM(u.email)) AS email,
                EXISTS (
                    SELECT 1
                    FROM shift_inschrijvingen shift_registration
                    INNER JOIN shifts shift_record
                        ON shift_record.shift_id = shift_registration.shift_id
                    WHERE shift_record.event_id = :shift_event_id
                      AND shift_registration.lid_id = l.lid_id
                      AND shift_registration.status = 'bevestigd'
                ) AS heeft_bevestigde_shift
            FROM leden l
            INNER JOIN gebruikers u ON u.lid_id = l.lid_id
            WHERE l.actief = 1
              AND u.email IS NOT NULL
              AND TRIM(u.email) <> ''
              AND u.email REGEXP '^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$'
              AND NOT EXISTS (
                  SELECT 1
                  FROM gebruikers blacklist
                  WHERE blacklist.lid_id = l.lid_id
                    AND blacklist.mail_blacklist = 1
              )
              AND (
                  EXISTS (
                      SELECT 1
                      FROM event_inschrijvingen event_registration
                      WHERE event_registration.event_id = :registration_event_id
                        AND event_registration.lid_id = l.lid_id
                        AND event_registration.status IN (
                            'wachtend',
                            'bevestigd',
                            'reserve'
                        )
                        AND event_registration.uitgeschreven_op IS NULL
                  )
                  OR EXISTS (
                      SELECT 1
                      FROM shift_inschrijvingen confirmed_shift_registration
                      INNER JOIN shifts confirmed_shift
                          ON confirmed_shift.shift_id = confirmed_shift_registration.shift_id
                      WHERE confirmed_shift.event_id = :confirmed_shift_event_id
                        AND confirmed_shift_registration.lid_id = l.lid_id
                        AND confirmed_shift_registration.status = 'bevestigd'
                  )
              )
            ORDER BY l.voornaam ASC, l.achternaam ASC, l.lid_id ASC
            SQL);
        $statement->execute([
            'shift_event_id' => $eventId,
            'registration_event_id' => $eventId,
            'confirmed_shift_event_id' => $eventId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function confirmedShiftPlanning(int $eventId): array
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT
                l.lid_id,
                l.voornaam,
                l.achternaam,
                u.email,
                NULLIF(TRIM(CONCAT_WS(' ', l.voornaam, l.achternaam)), '') AS naam,
                s.shift_id,
                s.naam AS shift_naam,
                st.naam AS type_naam,
                s.start_op,
                s.eind_op
            FROM shifts s
            INNER JOIN shift_inschrijvingen si
                ON si.shift_id = s.shift_id
               AND si.status = 'bevestigd'
            INNER JOIN leden l
                ON l.lid_id = si.lid_id
            INNER JOIN gebruikers u
                ON u.lid_id = l.lid_id
            INNER JOIN shift_types st
                ON st.type_id = s.type_id
            WHERE s.event_id = :event_id
              AND s.status = 'actief'
              AND l.actief = 1
              AND u.email IS NOT NULL
              AND TRIM(u.email) <> ''
              AND NOT EXISTS (
                  SELECT 1
                  FROM gebruikers blacklist
                  WHERE blacklist.lid_id = l.lid_id
                    AND blacklist.mail_blacklist = 1
              )
            ORDER BY l.lid_id ASC, s.start_op ASC, s.shift_id ASC
            SQL);
        $statement->execute([
            'event_id' => $eventId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $mailing
     * @param array<int, array<string, mixed>> $recipients
     */
    public function create(
        array $mailing,
        array $recipients
    ): int {
        $statement = $this->database->prepare(<<<'SQL'
            INSERT INTO mailings
            (
                type,
                doelgroep_type,
                doelgroep_json,
                event_id,
                aangemaakt_door,
                onderwerp,
                inhoud_html,
                inhoud_tekst,
                status,
                aangemaakt_op
            )
            VALUES
            (
                :type,
                :doelgroep_type,
                :doelgroep_json,
                :event_id,
                :aangemaakt_door,
                :onderwerp,
                :inhoud_html,
                :inhoud_tekst,
                :status,
                NOW()
            )
            SQL);
        $statement->execute([
            'type' => $mailing['type'],
            'doelgroep_type' => $mailing['doelgroep_type'],
            'doelgroep_json' => $mailing['doelgroep_json'] ?? null,
            'event_id' => $mailing['event_id'] ?? null,
            'aangemaakt_door' => $mailing['aangemaakt_door'] ?? null,
            'onderwerp' => $mailing['onderwerp'],
            'inhoud_html' => $mailing['inhoud_html'],
            'inhoud_tekst' => $mailing['inhoud_tekst'],
            'status' => $recipients === []
                ? Mailing::STATUS_SENT
                : Mailing::STATUS_QUEUED,
        ]);
        $mailingId = $this->database->lastInsertId();

        $insertRecipient = $this->database->prepare(<<<'SQL'
            INSERT INTO mailing_ontvangers
            (
                mailing_id,
                lid_id,
                email,
                naam,
                onderwerp,
                inhoud_html,
                inhoud_tekst,
                status,
                aangemaakt_op
            )
            VALUES
            (
                :mailing_id,
                :lid_id,
                :email,
                :naam,
                :onderwerp,
                :inhoud_html,
                :inhoud_tekst,
                'in_wachtrij',
                NOW()
            )
            SQL);

        foreach ($recipients as $recipient) {
            $insertRecipient->execute([
                'mailing_id' => $mailingId,
                'lid_id' => $recipient['lid_id'] ?? null,
                'email' => $recipient['email'],
                'naam' => $recipient['naam'],
                'onderwerp' => $recipient['onderwerp'],
                'inhoud_html' => $recipient['inhoud_html'],
                'inhoud_tekst' => $recipient['inhoud_tekst'],
            ]);
        }

        if ($recipients === []) {
            $this->database->execute(
                'UPDATE mailings
                SET voltooid_op = NOW()
                WHERE mailing_id = :mailing_id',
                ['mailing_id' => $mailingId]
            );
        }

        return $mailingId;
    }

    /**
     * @param array{
     *     name: string,
     *     path: string,
     *     mime: string,
     *     size: int,
     *     sha256: string
     * } $attachment
     */
    public function addAttachment(
        int $mailingId,
        array $attachment
    ): void {
        $this->database->execute(<<<'SQL'
            INSERT INTO mailing_bijlagen
            (
                mailing_id,
                originele_naam,
                opslagpad,
                mime_type,
                bestandsgrootte,
                sha256
            )
            VALUES
            (
                :mailing_id,
                :originele_naam,
                :opslagpad,
                :mime_type,
                :bestandsgrootte,
                :sha256
            )
            SQL, [
            'mailing_id' => $mailingId,
            'originele_naam' => $attachment['name'],
            'opslagpad' => $attachment['path'],
            'mime_type' => $attachment['mime'],
            'bestandsgrootte' => $attachment['size'],
            'sha256' => $attachment['sha256'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function claimNext(int $maxAttempts): ?array
    {
        return $this->database->transaction(
            function () use ($maxAttempts): ?array {
                $statement = $this->database->prepare(<<<'SQL'
                    SELECT
                        mo.ontvanger_id,
                        mo.mailing_id,
                        mo.email,
                        mo.naam,
                        mo.onderwerp,
                        mo.inhoud_html,
                        mo.inhoud_tekst
                    FROM mailing_ontvangers mo
                    WHERE (
                        mo.status = 'in_wachtrij'
                        OR (
                            mo.status = 'mislukt'
                            AND mo.pogingen < :max_attempts
                            AND mo.volgende_poging_op <= NOW()
                        )
                    )
                    ORDER BY
                        COALESCE(mo.volgende_poging_op, mo.aangemaakt_op) ASC,
                        mo.ontvanger_id ASC
                    LIMIT 1
                    FOR UPDATE SKIP LOCKED
                    SQL);
                $statement->execute([
                    'max_attempts' => $maxAttempts,
                ]);
                $row = $statement->fetch(PDO::FETCH_ASSOC);

                if (!is_array($row)) {
                    return null;
                }

                $this->database->execute(<<<'SQL'
                    UPDATE mailing_ontvangers
                    SET
                        status = 'bezig',
                        pogingen = pogingen + 1,
                        vergrendeld_op = NOW(),
                        foutmelding = NULL
                    WHERE ontvanger_id = :ontvanger_id
                    SQL, [
                    'ontvanger_id' => $row['ontvanger_id'],
                ]);

                $this->database->execute(<<<'SQL'
                    UPDATE mailings
                    SET status = 'bezig'
                    WHERE mailing_id = :mailing_id
                      AND status = 'in_wachtrij'
                    SQL, [
                    'mailing_id' => $row['mailing_id'],
                ]);

                return $row;
            }
        );
    }

    /**
     * @return array<int, array{path: string, name: string, sha256: string}>
     */
    public function attachments(int $mailingId): array
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT
                opslagpad AS path,
                originele_naam AS name,
                sha256
            FROM mailing_bijlagen
            WHERE mailing_id = :mailing_id
            ORDER BY bijlage_id ASC
            SQL);
        $statement->execute([
            'mailing_id' => $mailingId,
        ]);

        return array_map(
            static fn(array $row): array => [
                'path' => (string) $row['path'],
                'name' => (string) $row['name'],
                'sha256' => (string) $row['sha256'],
            ],
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function markSent(
        int $recipientId,
        ?string $messageId
    ): void {
        $this->database->execute(<<<'SQL'
            UPDATE mailing_ontvangers
            SET
                status = 'verzonden',
                volgende_poging_op = NULL,
                vergrendeld_op = NULL,
                verzonden_op = NOW(),
                message_id = :message_id,
                foutmelding = NULL,
                inhoud_html = CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM mailings sensitive_mailing
                        WHERE sensitive_mailing.mailing_id = mailing_ontvangers.mailing_id
                          AND sensitive_mailing.type = 'wachtwoord_reset'
                    )
                    THEN '<p>De beveiligde herstelkoppeling werd na verzending uit de mailwachtrij verwijderd.</p>'
                    ELSE inhoud_html
                END,
                inhoud_tekst = CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM mailings sensitive_mailing
                        WHERE sensitive_mailing.mailing_id = mailing_ontvangers.mailing_id
                          AND sensitive_mailing.type = 'wachtwoord_reset'
                    )
                    THEN 'De beveiligde herstelkoppeling werd na verzending uit de mailwachtrij verwijderd.'
                    ELSE inhoud_tekst
                END
            WHERE ontvanger_id = :ontvanger_id
            SQL, [
            'ontvanger_id' => $recipientId,
            'message_id' => $messageId,
        ]);
    }

    public function markFailed(
        int $recipientId,
        string $error,
        int $maxAttempts
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT pogingen
            FROM mailing_ontvangers
            WHERE ontvanger_id = :ontvanger_id
            LIMIT 1
            SQL);
        $statement->execute([
            'ontvanger_id' => $recipientId,
        ]);
        $attempts = (int) $statement->fetchColumn();
        $retryMinutes = match (true) {
            $attempts <= 1 => 5,
            $attempts === 2 => 30,
            $attempts === 3 => 120,
            default => 360,
        };

        $this->database->execute(<<<'SQL'
            UPDATE mailing_ontvangers
            SET
                status = 'mislukt',
                volgende_poging_op = CASE
                    WHEN pogingen < :max_attempts
                    THEN DATE_ADD(NOW(), INTERVAL :retry_minutes MINUTE)
                    ELSE NULL
                END,
                vergrendeld_op = NULL,
                foutmelding = :foutmelding
            WHERE ontvanger_id = :ontvanger_id
            SQL, [
            'ontvanger_id' => $recipientId,
            'max_attempts' => $maxAttempts,
            'retry_minutes' => $retryMinutes,
            'foutmelding' => substr(trim($error), 0, 2000),
        ]);
    }

    public function releaseStaleLocks(): void
    {
        $this->database->execute(<<<'SQL'
            UPDATE mailing_ontvangers
            SET
                status = 'in_wachtrij',
                vergrendeld_op = NULL,
                foutmelding = 'Een eerdere worker werd onderbroken; de mail is opnieuw ingepland.'
            WHERE status = 'bezig'
              AND vergrendeld_op < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            SQL);
    }

    /**
     * @return int[]
     */
    public function pendingEventCancellationCompletions(): array
    {
        $statement = $this->database->query(<<<'SQL'
            SELECT m.mailing_id
            FROM mailings m
            INNER JOIN evenementen e
                ON e.event_id = m.event_id
               AND e.status = 'geannuleerd'
            WHERE m.type = 'event_geannuleerd'
              AND m.status = 'verzonden'
              AND (
                  EXISTS (
                      SELECT 1
                      FROM event_inschrijvingen ei
                      WHERE ei.event_id = m.event_id
                        AND ei.status IN ('wachtend', 'bevestigd', 'reserve')
                        AND ei.uitgeschreven_op IS NULL
                  )
                  OR EXISTS (
                      SELECT 1
                      FROM shifts s
                      WHERE s.event_id = m.event_id
                        AND s.status = 'actief'
                  )
              )
            ORDER BY m.mailing_id ASC
            LIMIT 100
            SQL);

        return array_map(
            'intval',
            $statement->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function refreshMailingStatus(
        int $mailingId,
        int $maxAttempts
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'verzonden'), 0) AS sent,
                COALESCE(SUM(status = 'bezig'), 0) AS processing,
                COALESCE(SUM(status = 'in_wachtrij'), 0) AS queued,
                COALESCE(SUM(
                    status = 'mislukt'
                    AND pogingen < :max_attempts
                    AND volgende_poging_op IS NOT NULL
                ), 0) AS retryable,
                COALESCE(SUM(
                    status = 'mislukt'
                    AND (
                        pogingen >= :terminal_attempts
                        OR volgende_poging_op IS NULL
                    )
                ), 0) AS terminal_failed
            FROM mailing_ontvangers
            WHERE mailing_id = :mailing_id
            SQL);
        $statement->execute([
            'mailing_id' => $mailingId,
            'max_attempts' => $maxAttempts,
            'terminal_attempts' => $maxAttempts,
        ]);
        $counts = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        $total = (int) ($counts['total'] ?? 0);
        $sent = (int) ($counts['sent'] ?? 0);
        $processing = (int) ($counts['processing'] ?? 0);
        $queued = (int) ($counts['queued'] ?? 0);
        $retryable = (int) ($counts['retryable'] ?? 0);

        if ($total === 0 || $sent === $total) {
            $status = Mailing::STATUS_SENT;
        } elseif ($processing > 0) {
            $status = Mailing::STATUS_PROCESSING;
        } elseif ($queued > 0 || $retryable > 0) {
            $status = Mailing::STATUS_QUEUED;
        } elseif ($sent > 0) {
            $status = Mailing::STATUS_PARTIAL;
        } else {
            $status = Mailing::STATUS_FAILED;
        }

        $terminal = in_array(
            $status,
            [
                Mailing::STATUS_SENT,
                Mailing::STATUS_PARTIAL,
                Mailing::STATUS_FAILED,
            ],
            true
        );

        $this->database->execute(<<<'SQL'
            UPDATE mailings
            SET
                status = :status,
                voltooid_op = CASE
                    WHEN :is_terminal = 1 THEN COALESCE(voltooid_op, NOW())
                    ELSE NULL
                END
            WHERE mailing_id = :mailing_id
            SQL, [
            'mailing_id' => $mailingId,
            'status' => $status,
            'is_terminal' => $terminal ? 1 : 0,
        ]);

        if ($status === Mailing::STATUS_SENT) {
            $this->markPlanningSentWhenApplicable($mailingId);
        }
    }

    public function retryFailed(int $mailingId): int
    {
        $statement = $this->database->execute(<<<'SQL'
            UPDATE mailing_ontvangers
            SET
                status = 'in_wachtrij',
                pogingen = 0,
                volgende_poging_op = NULL,
                vergrendeld_op = NULL,
                foutmelding = NULL
            WHERE mailing_id = :mailing_id
              AND status = 'mislukt'
            SQL, [
            'mailing_id' => $mailingId,
        ]);

        if ($statement->rowCount() > 0) {
            $this->database->execute(<<<'SQL'
                UPDATE mailings
                SET
                    status = 'in_wachtrij',
                    voltooid_op = NULL
                WHERE mailing_id = :mailing_id
                SQL, [
                'mailing_id' => $mailingId,
            ]);
        }

        return $statement->rowCount();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function eligibleMembers(?string $extraCondition = null): array
    {
        $conditions = [
            'l.actief = 1',
            'u.email IS NOT NULL',
            "TRIM(u.email) <> ''",
            'NOT EXISTS (
                SELECT 1
                FROM gebruikers blacklist
                WHERE blacklist.lid_id = l.lid_id
                  AND blacklist.mail_blacklist = 1
            )',
        ];

        if ($extraCondition !== null) {
            $conditions[] = $extraCondition;
        }

        $statement = $this->database->query(
            'SELECT
                l.lid_id,
                l.voornaam,
                l.achternaam,
                NULLIF(
                    TRIM(CONCAT_WS(\' \', l.voornaam, l.achternaam)),
                    \'\'
                ) AS naam,
                LOWER(TRIM(u.email)) AS email
            FROM leden l
            INNER JOIN gebruikers u ON u.lid_id = l.lid_id
            WHERE ' . implode(PHP_EOL . ' AND ', $conditions) . '
              AND u.email REGEXP \'^[^[:space:]@]+@[^[:space:]@]+\\.[^[:space:]@]+$\'
            ORDER BY l.voornaam ASC, l.achternaam ASC, l.lid_id ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int[] $ids
     */
    private function integerList(array $ids): string
    {
        $ids = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $ids),
                    static fn(int $id): bool => $id > 0
                )
            )
        );

        return $ids !== [] ? implode(',', $ids) : '0';
    }

    private function markPlanningSentWhenApplicable(int $mailingId): void
    {
        $this->database->execute(<<<'SQL'
            UPDATE evenementen e
            INNER JOIN mailings m
                ON m.event_id = e.event_id
               AND m.mailing_id = :mailing_id
               AND m.type = 'shift_planning'
            SET e.planning_verstuurd = NOW()
            SQL, [
            'mailing_id' => $mailingId,
        ]);
    }
}
