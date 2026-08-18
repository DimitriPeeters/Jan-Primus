<?php

declare(strict_types=1);

namespace AEFS\Database\Cutover;

use App\Services\EncryptionService;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;
use ZipArchive;

final class OneComCutoverBuilder
{
    private const ARCHIVE_SUFFIX = '_onecom_legacy_20260818';

    private const LEGACY_SECURITY_ENTRY =
        'AEFS_ledenadministratie/config/security.php';

    /** @var array<string, int|string|array<string, int>> */
    private array $statistics = [];

    /** @var array<int, int> */
    private array $memberMap = [];

    /** @var array<int, int> */
    private array $userMap = [];

    /** @var array<int, int> */
    private array $groupMap = [];

    /** @var array<int, int> */
    private array $membershipTypeMap = [];

    /** @var array<int, int> */
    private array $eventMap = [];

    /** @var array<int, int> */
    private array $eventRegistrationMap = [];

    /** @var array<int, int> */
    private array $shiftMap = [];

    private readonly string $createdAt;

    public function __construct(
        private readonly PDO $pdo,
        private readonly EncryptionService $encryption,
        private readonly string $legacyKey,
        private readonly string $localDatabase,
        private readonly string $oneComDatabase,
        private readonly string $targetDatabase
    ) {
        foreach (
            [
                $this->localDatabase,
                $this->oneComDatabase,
                $this->targetDatabase,
            ] as $database
        ) {
            $this->assertIdentifier($database);
        }

        $this->createdAt = (new DateTimeImmutable())->format(
            'Y-m-d H:i:s'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $this->copyLocalDatabase();
        $this->archiveOneComShiftTables();

        $this->pdo->beginTransaction();

        try {
            $this->mergeMembers();
            $this->mergeUsers();
            $this->mergeGroups();
            $this->mergeMembershipTypes();
            $this->mergeGroupAssignments();
            $this->mergeMemberships();
            $this->mergePayments();
            $this->mergeEvents();
            $this->mergeEventRegistrations();
            $this->mergeLegacyShifts();
            $this->mergeLegacyShiftRegistrations();
            $this->mergeContactMessages();
            $this->mergeNotifications();
            $this->invalidatePasswordResetTokens();

            $this->pdo->commit();
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException(
                'De one.com-gegevens konden niet veilig worden samengevoegd.',
                0,
                $throwable
            );
        }

        return [
            'generated_at' => $this->createdAt,
            'source_databases' => [
                'local' => $this->localDatabase,
                'onecom' => $this->oneComDatabase,
                'target' => $this->targetDatabase,
            ],
            'merge_statistics' => $this->statistics,
            'verification' => $this->verify(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(): array
    {
        $requiredTables = [
            'leden',
            'gebruikers',
            'groepen',
            'leden_groepen',
            'evenementen',
            'event_inschrijvingen',
            'event_inschrijving_dagen',
            'shift_types',
            'shifts',
            'shift_inschrijvingen',
            'mailings',
            'mailing_ontvangers',
            'mailing_bijlagen',
            'instellingen',
        ];

        foreach ($requiredTables as $table) {
            $this->assert(
                $this->tableExists($this->targetDatabase, $table),
                sprintf('Vereiste doeltabel ontbreekt: %s.', $table)
            );
        }

        foreach (['event_shifts', 'shift_toewijzingen', 'shift_registrations'] as $table) {
            $this->assert(
                !$this->tableExists($this->targetDatabase, $table),
                sprintf('Legacytabel is nog actief in de doeldatabase: %s.', $table)
            );
        }

        $checks = [
            'duplicate_member_emails' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM (
                    SELECT LOWER(TRIM(email))
                    FROM {$this->qualified($this->targetDatabase, 'leden')}
                    WHERE email IS NOT NULL
                      AND TRIM(email) <> ''
                    GROUP BY LOWER(TRIM(email))
                    HAVING COUNT(*) > 1
                ) duplicate_emails
                SQL),
            'duplicate_user_emails' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM (
                    SELECT LOWER(TRIM(email))
                    FROM {$this->qualified($this->targetDatabase, 'gebruikers')}
                    GROUP BY LOWER(TRIM(email))
                    HAVING COUNT(*) > 1
                ) duplicate_emails
                SQL),
            'orphan_users' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'gebruikers')} u
                LEFT JOIN {$this->qualified($this->targetDatabase, 'leden')} l
                    ON l.lid_id = u.lid_id
                WHERE u.lid_id IS NOT NULL
                  AND l.lid_id IS NULL
                SQL),
            'orphan_event_registrations' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'event_inschrijvingen')} ei
                LEFT JOIN {$this->qualified($this->targetDatabase, 'evenementen')} e
                    ON e.event_id = ei.event_id
                LEFT JOIN {$this->qualified($this->targetDatabase, 'leden')} l
                    ON l.lid_id = ei.lid_id
                WHERE e.event_id IS NULL
                   OR l.lid_id IS NULL
                SQL),
            'duplicate_event_registrations' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM (
                    SELECT event_id, lid_id
                    FROM {$this->qualified($this->targetDatabase, 'event_inschrijvingen')}
                    GROUP BY event_id, lid_id
                    HAVING COUNT(*) > 1
                ) duplicate_registrations
                SQL),
            'orphan_event_days' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'event_inschrijving_dagen')} eid
                LEFT JOIN {$this->qualified($this->targetDatabase, 'event_inschrijvingen')} ei
                    ON ei.inschrijving_id = eid.inschrijving_id
                WHERE ei.inschrijving_id IS NULL
                SQL),
            'invalid_shifts' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'shifts')} s
                LEFT JOIN {$this->qualified($this->targetDatabase, 'evenementen')} e
                    ON e.event_id = s.event_id
                LEFT JOIN {$this->qualified($this->targetDatabase, 'shift_types')} st
                    ON st.type_id = s.type_id
                WHERE e.event_id IS NULL
                   OR st.type_id IS NULL
                   OR s.eind_op <= s.start_op
                   OR s.max_personen <= 0
                   OR s.vergoeding_bedrag < 0
                SQL),
            'orphan_shift_registrations' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'shift_inschrijvingen')} si
                LEFT JOIN {$this->qualified($this->targetDatabase, 'shifts')} s
                    ON s.shift_id = si.shift_id
                LEFT JOIN {$this->qualified($this->targetDatabase, 'leden')} l
                    ON l.lid_id = si.lid_id
                WHERE s.shift_id IS NULL
                   OR l.lid_id IS NULL
                SQL),
            'duplicate_shift_registrations' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM (
                    SELECT shift_id, lid_id
                    FROM {$this->qualified($this->targetDatabase, 'shift_inschrijvingen')}
                    GROUP BY shift_id, lid_id
                    HAVING COUNT(*) > 1
                ) duplicate_registrations
                SQL),
            'members_with_unencrypted_identifier' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'leden')}
                WHERE rijksregisternummer IS NOT NULL
                  AND TRIM(rijksregisternummer) <> ''
                  AND rijksregisternummer NOT LIKE 'enc:v1:%'
                SQL),
            'members_with_unencrypted_bank_account' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'leden')}
                WHERE rekeningnummer IS NOT NULL
                  AND TRIM(rekeningnummer) <> ''
                  AND rekeningnummer NOT LIKE 'enc:v1:%'
                SQL),
            'sensitive_audit_values' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'audit_logs')}
                WHERE (
                    JSON_CONTAINS_PATH(old_values, 'one', '$.rijksregisternummer') = 1
                    AND COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(old_values, '$.rijksregisternummer')),
                        ''
                    ) NOT IN ('', '[afgeschermd]')
                ) OR (
                    JSON_CONTAINS_PATH(new_values, 'one', '$.rijksregisternummer') = 1
                    AND COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.rijksregisternummer')),
                        ''
                    ) NOT IN ('', '[afgeschermd]')
                ) OR (
                    JSON_CONTAINS_PATH(old_values, 'one', '$.rekeningnummer') = 1
                    AND COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(old_values, '$.rekeningnummer')),
                        ''
                    ) NOT IN ('', '[afgeschermd]')
                ) OR (
                    JSON_CONTAINS_PATH(new_values, 'one', '$.rekeningnummer') = 1
                    AND COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.rekeningnummer')),
                        ''
                    ) NOT IN ('', '[afgeschermd]')
                )
                SQL),
        ];

        foreach ($checks as $name => $value) {
            $this->assert(
                $value === 0,
                sprintf('Integriteitscontrole %s gaf %d in plaats van 0.', $name, $value)
            );
        }

        $this->verifyEncryptedMemberValues();
        $this->verifyArchiveCoverage();

        return [
            'checks' => $checks,
            'table_counts' => $this->tableCounts($this->targetDatabase),
            'event_registration_statuses' => $this->statusCounts(
                'event_inschrijvingen'
            ),
            'shift_registration_statuses' => $this->statusCounts(
                'shift_inschrijvingen'
            ),
            'pending_mail_recipients' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM {$this->qualified($this->targetDatabase, 'mailing_ontvangers')}
                WHERE status IN ('in_wachtrij', 'bezig')
                SQL),
            'foreign_keys' => $this->scalar(<<<SQL
                SELECT COUNT(*)
                FROM information_schema.referential_constraints
                WHERE constraint_schema = {$this->pdo->quote($this->targetDatabase)}
                SQL),
        ];
    }

    public static function readLegacyEncryptionKey(string $zipPath): string
    {
        $archive = new ZipArchive();
        $result = $archive->open($zipPath);

        if ($result !== true) {
            throw new RuntimeException(
                'Het legacyproject kon niet als ZIP-bestand worden geopend.'
            );
        }

        try {
            $securitySource = $archive->getFromName(
                self::LEGACY_SECURITY_ENTRY
            );
        } finally {
            $archive->close();
        }

        if (!is_string($securitySource)) {
            throw new RuntimeException(
                'De legacy-securityconfiguratie ontbreekt in het ZIP-bestand.'
            );
        }

        $strings = [];

        foreach (token_get_all($securitySource) as $token) {
            if (
                is_array($token)
                && $token[0] === T_CONSTANT_ENCAPSED_STRING
            ) {
                $strings[] = stripcslashes(
                    substr($token[1], 1, -1)
                );
            }
        }

        $keyIndex = array_search('ENCRYPTION_KEY', $strings, true);
        $key = $keyIndex !== false
            ? ($strings[$keyIndex + 1] ?? null)
            : null;

        if (!is_string($key) || $key === '') {
            throw new RuntimeException(
                'De legacy-encryptiesleutel kon niet veilig worden gelezen.'
            );
        }

        return $key;
    }

    private function copyLocalDatabase(): void
    {
        $tables = $this->fetchColumn(<<<SQL
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = {$this->pdo->quote($this->targetDatabase)}
              AND table_type = 'BASE TABLE'
            ORDER BY table_name
            SQL);

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($tables as $table) {
                $this->assertIdentifier($table);

                if (!$this->tableExists($this->localDatabase, $table)) {
                    throw new RuntimeException(sprintf(
                        'De lokale brondatabase mist de tabel %s.',
                        $table
                    ));
                }

                $targetColumns = $this->tableColumns(
                    $this->targetDatabase,
                    $table
                );
                $sourceColumns = $this->tableColumns(
                    $this->localDatabase,
                    $table
                );

                if ($targetColumns !== $sourceColumns) {
                    throw new RuntimeException(sprintf(
                        'Het lokale schema van %s wijkt af van de actuele baseline.',
                        $table
                    ));
                }

                $columns = implode(
                    ', ',
                    array_map($this->quoteIdentifier(...), $targetColumns)
                );

                $this->pdo->exec(sprintf(
                    'INSERT INTO %s (%s) SELECT %s FROM %s',
                    $this->qualified($this->targetDatabase, $table),
                    $columns,
                    $columns,
                    $this->qualified($this->localDatabase, $table)
                ));
            }
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->statistics['local_tables_copied'] = count($tables);
    }

    private function archiveOneComShiftTables(): void
    {
        $archived = [];

        foreach (
            [
                'event_shifts',
                'shift_inschrijvingen',
                'shift_registrations',
                'shift_toewijzingen',
                'shift_types',
                'shifts',
            ] as $sourceTable
        ) {
            if (!$this->tableExists($this->oneComDatabase, $sourceTable)) {
                continue;
            }

            $archiveTable = $sourceTable . self::ARCHIVE_SUFFIX;
            $this->assertIdentifier($archiveTable);

            $this->pdo->exec(sprintf(
                'CREATE TABLE %s LIKE %s',
                $this->qualified($this->targetDatabase, $archiveTable),
                $this->qualified($this->oneComDatabase, $sourceTable)
            ));
            $this->pdo->exec(sprintf(
                'INSERT INTO %s SELECT * FROM %s',
                $this->qualified($this->targetDatabase, $archiveTable),
                $this->qualified($this->oneComDatabase, $sourceTable)
            ));

            $archived[$sourceTable] = $this->scalar(sprintf(
                'SELECT COUNT(*) FROM %s',
                $this->qualified($this->targetDatabase, $archiveTable)
            ));
        }

        $this->statistics['archived_onecom_shift_tables'] = $archived;
    }

    private function mergeMembers(): void
    {
        $targetRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY lid_id',
            $this->qualified($this->targetDatabase, 'leden')
        ));
        $remoteRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY lid_id',
            $this->qualified($this->oneComDatabase, 'leden')
        ));

        $targetByEmail = [];
        $usedIds = [];

        foreach ($targetRows as $row) {
            $email = $this->normalizeEmail($row['email'] ?? null);

            if ($email === null) {
                throw new RuntimeException(
                    'Een lokaal lid zonder e-mailadres verhindert veilige matching.'
                );
            }

            if (isset($targetByEmail[$email])) {
                throw new RuntimeException(
                    'De lokale ledentabel bevat dubbele e-mailadressen.'
                );
            }

            $targetByEmail[$email] = $row;
            $usedIds[(int) $row['lid_id']] = true;
        }

        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;
        $inserted = 0;
        $matched = 0;
        $remoteNewer = 0;

        foreach ($remoteRows as $remote) {
            $remoteId = (int) $remote['lid_id'];
            $email = $this->normalizeEmail($remote['email'] ?? null);

            if ($email === null) {
                throw new RuntimeException(sprintf(
                    'One.com-lid %d heeft geen e-mailadres voor veilige matching.',
                    $remoteId
                ));
            }

            $target = $targetByEmail[$email] ?? null;

            if ($target !== null) {
                $targetId = (int) $target['lid_id'];
                $preferRemote = $this->dateIsLater(
                    $remote['bijgewerkt_op'] ?? null,
                    $target['bijgewerkt_op'] ?? null
                );
                $chosen = $preferRemote ? $remote : $target;

                if ($preferRemote) {
                    $remoteNewer++;
                }

                $targetBank = $this->secureBankAccount(
                    $target['rekeningnummer'] ?? null
                );
                $remoteBank = $this->secureBankAccount(
                    $remote['rekeningnummer'] ?? null
                );
                $targetIdentifier = $this->secureNationalIdentifier(
                    $target['rijksregisternummer'] ?? null
                );
                $remoteIdentifier = $this->secureNationalIdentifier(
                    $remote['rijksregisternummer'] ?? null
                );

                $bank = $preferRemote && $remoteBank !== null
                    ? $remoteBank
                    : ($targetBank ?? $remoteBank);
                $identifier = $preferRemote && $remoteIdentifier !== null
                    ? $remoteIdentifier
                    : ($targetIdentifier ?? $remoteIdentifier);

                $this->updateMember(
                    $targetId,
                    $chosen,
                    $bank,
                    $identifier,
                    $this->earliestDate(
                        $target['aangemaakt_op'] ?? null,
                        $remote['aangemaakt_op'] ?? null
                    ),
                    $this->latestDate(
                        $target['bijgewerkt_op'] ?? null,
                        $remote['bijgewerkt_op'] ?? null
                    )
                );

                $this->backupRemoteLegacyIdentifier(
                    $targetId,
                    $remote['rijksregisternummer'] ?? null,
                    $remote['bijgewerkt_op'] ?? null
                );

                $this->memberMap[$remoteId] = $targetId;
                $targetByEmail[$email] = array_merge(
                    $target,
                    ['lid_id' => $targetId]
                );
                $matched++;

                continue;
            }

            $targetId = $this->allocateId($remoteId, $usedIds, $nextId);
            $bank = $this->secureBankAccount(
                $remote['rekeningnummer'] ?? null
            );
            $identifier = $this->secureNationalIdentifier(
                $remote['rijksregisternummer'] ?? null
            );

            $this->insertMember(
                $targetId,
                $remote,
                $bank,
                $identifier
            );
            $this->backupRemoteLegacyIdentifier(
                $targetId,
                $remote['rijksregisternummer'] ?? null,
                $remote['bijgewerkt_op'] ?? null
            );

            $this->memberMap[$remoteId] = $targetId;
            $targetByEmail[$email] = array_merge(
                $remote,
                ['lid_id' => $targetId]
            );
            $inserted++;
        }

        $securedLocalOnly = $this->secureAllTargetMemberValues();

        $this->statistics['members_matched'] = $matched;
        $this->statistics['members_inserted_from_onecom'] = $inserted;
        $this->statistics['members_onecom_newer_applied'] = $remoteNewer;
        $this->statistics['local_only_members_secured'] = $securedLocalOnly;
    }

    private function mergeUsers(): void
    {
        $targetRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY gebruiker_id',
            $this->qualified($this->targetDatabase, 'gebruikers')
        ));
        $remoteRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY gebruiker_id',
            $this->qualified($this->oneComDatabase, 'gebruikers')
        ));

        $targetByEmail = [];
        $usedIds = [];

        foreach ($targetRows as $row) {
            $email = $this->normalizeEmail($row['email'] ?? null);

            if ($email === null || isset($targetByEmail[$email])) {
                throw new RuntimeException(
                    'De lokale gebruikerstabel kan niet eenduidig op e-mail worden gematcht.'
                );
            }

            $targetByEmail[$email] = $row;
            $usedIds[(int) $row['gebruiker_id']] = true;
        }

        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;
        $inserted = 0;
        $matched = 0;

        foreach ($remoteRows as $remote) {
            $remoteId = (int) $remote['gebruiker_id'];
            $email = $this->normalizeEmail($remote['email'] ?? null);

            if ($email === null) {
                throw new RuntimeException(sprintf(
                    'One.com-gebruiker %d heeft geen geldig e-mailadres.',
                    $remoteId
                ));
            }

            $remoteMemberId = $remote['lid_id'] !== null
                ? (int) $remote['lid_id']
                : null;
            $targetMemberId = $remoteMemberId !== null
                ? ($this->memberMap[$remoteMemberId] ?? null)
                : null;
            $target = $targetByEmail[$email] ?? null;

            if ($target !== null) {
                $targetId = (int) $target['gebruiker_id'];
                $role = (
                    (string) $target['rol'] === 'admin'
                    || (string) $remote['rol'] === 'admin'
                ) ? 'admin' : 'lid';
                $blacklisted = (
                    (bool) $target['mail_blacklist']
                    || (bool) $remote['mail_blacklist']
                ) ? 1 : 0;

                $statement = $this->pdo->prepare(sprintf(
                    'UPDATE %s
                    SET lid_id = COALESCE(lid_id, ?),
                        rol = ?,
                        mail_blacklist = ?
                    WHERE gebruiker_id = ?',
                    $this->qualified($this->targetDatabase, 'gebruikers')
                ));
                $statement->execute([
                    $targetMemberId,
                    $role,
                    $blacklisted,
                    $targetId,
                ]);

                $this->userMap[$remoteId] = $targetId;
                $matched++;

                continue;
            }

            $targetId = $this->allocateId($remoteId, $usedIds, $nextId);
            $active = (bool) $remote['actief'];
            $approvalStatus = $active ? 'goedgekeurd' : 'wachtend';
            $approvedAt = $active
                ? (string) $remote['aangemaakt_op']
                : null;

            $this->insert(
                'gebruikers',
                [
                    'gebruiker_id' => $targetId,
                    'lid_id' => $targetMemberId,
                    'email' => (string) $remote['email'],
                    'wachtwoord_hash' => (string) $remote['wachtwoord_hash'],
                    'rol' => (string) $remote['rol'],
                    'goedkeuringsstatus' => $approvalStatus,
                    'goedgekeurd_op' => $approvedAt,
                    'actief' => $active ? 1 : 0,
                    'aangemaakt_op' => (string) $remote['aangemaakt_op'],
                    'wachtwoord_moet_wijzigen' => (bool) $remote['wachtwoord_moet_wijzigen'] ? 1 : 0,
                    'reset_token' => null,
                    'reset_token_expires' => null,
                    'mail_blacklist' => (bool) $remote['mail_blacklist'] ? 1 : 0,
                ]
            );

            $this->userMap[$remoteId] = $targetId;
            $targetByEmail[$email] = array_merge(
                $remote,
                ['gebruiker_id' => $targetId]
            );
            $inserted++;
        }

        $this->statistics['users_matched'] = $matched;
        $this->statistics['users_inserted_from_onecom'] = $inserted;
    }

    private function mergeGroups(): void
    {
        $this->groupMap = $this->mergeNamedReferenceTable(
            table: 'groepen',
            idColumn: 'groep_id',
            nameColumn: 'naam',
            additionalColumns: ['beschrijving']
        );
    }

    private function mergeMembershipTypes(): void
    {
        $this->membershipTypeMap = $this->mergeNamedReferenceTable(
            table: 'lidtypes',
            idColumn: 'lidtype_id',
            nameColumn: 'naam',
            additionalColumns: ['bedrag']
        );
    }

    private function mergeGroupAssignments(): void
    {
        if (!$this->tableExists($this->oneComDatabase, 'leden_groepen')) {
            return;
        }

        $inserted = 0;
        $rows = $this->fetchAll(sprintf(
            'SELECT lid_id, groep_id FROM %s',
            $this->qualified($this->oneComDatabase, 'leden_groepen')
        ));

        foreach ($rows as $row) {
            $memberId = $this->memberMap[(int) $row['lid_id']] ?? null;
            $groupId = $this->groupMap[(int) $row['groep_id']] ?? null;

            if ($memberId === null || $groupId === null) {
                throw new RuntimeException(
                    'Een one.com-groepskoppeling kon niet worden gemapt.'
                );
            }

            $existingGroup = $this->scalarPrepared(
                sprintf(
                    'SELECT COUNT(*) FROM %s WHERE lid_id = ?',
                    $this->qualified($this->targetDatabase, 'leden_groepen')
                ),
                [$memberId]
            );

            if ($existingGroup > 0) {
                continue;
            }

            $this->insert(
                'leden_groepen',
                [
                    'lid_id' => $memberId,
                    'groep_id' => $groupId,
                ]
            );
            $inserted++;
        }

        $this->statistics['group_assignments_inserted_from_onecom'] = $inserted;
    }

    private function mergeMemberships(): void
    {
        if (!$this->tableExists($this->oneComDatabase, 'lidmaatschappen')) {
            return;
        }

        $inserted = 0;
        $usedIds = $this->usedIds('lidmaatschappen', 'lidmaatschap_id');
        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY lidmaatschap_id',
                $this->qualified($this->oneComDatabase, 'lidmaatschappen')
            )) as $row
        ) {
            $memberId = $this->memberMap[(int) $row['lid_id']] ?? null;
            $typeId = $this->membershipTypeMap[(int) $row['lidtype_id']] ?? null;

            if ($memberId === null || $typeId === null) {
                throw new RuntimeException(
                    'Een one.com-lidmaatschap kon niet worden gemapt.'
                );
            }

            $exists = $this->scalarPrepared(
                sprintf(
                    'SELECT COUNT(*) FROM %s
                    WHERE lid_id = ? AND lidtype_id = ? AND jaar = ?',
                    $this->qualified($this->targetDatabase, 'lidmaatschappen')
                ),
                [$memberId, $typeId, (int) $row['jaar']]
            );

            if ($exists > 0) {
                continue;
            }

            $id = $this->allocateId(
                (int) $row['lidmaatschap_id'],
                $usedIds,
                $nextId
            );
            $this->insert(
                'lidmaatschappen',
                [
                    'lidmaatschap_id' => $id,
                    'lid_id' => $memberId,
                    'lidtype_id' => $typeId,
                    'jaar' => (int) $row['jaar'],
                    'aangemaakt_op' => (string) $row['aangemaakt_op'],
                ]
            );
            $inserted++;
        }

        $this->statistics['memberships_inserted_from_onecom'] = $inserted;
    }

    private function mergePayments(): void
    {
        if (!$this->tableExists($this->oneComDatabase, 'betalingen')) {
            return;
        }

        $inserted = 0;
        $usedIds = $this->usedIds('betalingen', 'betaling_id');
        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY betaling_id',
                $this->qualified($this->oneComDatabase, 'betalingen')
            )) as $row
        ) {
            $memberId = $this->memberMap[(int) $row['lid_id']] ?? null;

            if ($memberId === null) {
                throw new RuntimeException(
                    'Een one.com-betaling kon niet aan een lid worden gekoppeld.'
                );
            }

            $exists = $this->scalarPrepared(
                sprintf(
                    'SELECT COUNT(*) FROM %s
                    WHERE lid_id = ? AND jaar = ? AND bedrag = ?
                      AND betaald_op = ? AND COALESCE(methode, \'\') = COALESCE(?, \'\')',
                    $this->qualified($this->targetDatabase, 'betalingen')
                ),
                [
                    $memberId,
                    (int) $row['jaar'],
                    (string) $row['bedrag'],
                    (string) $row['betaald_op'],
                    $row['methode'],
                ]
            );

            if ($exists > 0) {
                continue;
            }

            $id = $this->allocateId(
                (int) $row['betaling_id'],
                $usedIds,
                $nextId
            );
            $this->insert(
                'betalingen',
                [
                    'betaling_id' => $id,
                    'lid_id' => $memberId,
                    'jaar' => (int) $row['jaar'],
                    'bedrag' => (string) $row['bedrag'],
                    'betaald_op' => (string) $row['betaald_op'],
                    'methode' => $row['methode'],
                ]
            );
            $inserted++;
        }

        $this->statistics['payments_inserted_from_onecom'] = $inserted;
    }

    private function mergeEvents(): void
    {
        $targetRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY event_id',
            $this->qualified($this->targetDatabase, 'evenementen')
        ));
        $targetByKey = [];
        $usedIds = [];

        foreach ($targetRows as $row) {
            $key = $this->eventKey($row);

            if (isset($targetByKey[$key])) {
                throw new RuntimeException(
                    'De lokale evenemententabel bevat dubbelzinnige natuurlijke sleutels.'
                );
            }

            $targetByKey[$key] = $row;
            $usedIds[(int) $row['event_id']] = true;
        }

        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;
        $matched = 0;
        $inserted = 0;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY event_id',
                $this->qualified($this->oneComDatabase, 'evenementen')
            )) as $remote
        ) {
            $remoteId = (int) $remote['event_id'];
            $key = $this->eventKey($remote);
            $target = $targetByKey[$key] ?? null;

            if ($target !== null) {
                $this->eventMap[$remoteId] = (int) $target['event_id'];
                $matched++;

                continue;
            }

            $targetId = $this->allocateId($remoteId, $usedIds, $nextId);
            $this->insert(
                'evenementen',
                [
                    'event_id' => $targetId,
                    'titel' => (string) $remote['titel'],
                    'beschrijving' => $remote['beschrijving'],
                    'locatie' => $remote['locatie'],
                    'max_deelnemers' => $remote['max_deelnemers'],
                    'aangemaakt_op' => (string) $remote['aangemaakt_op'],
                    'bijgewerkt_op' => null,
                    'startdatum' => (string) $remote['startdatum'],
                    'einddatum' => $remote['einddatum'],
                    'planning_verstuurd' => $remote['planning_verstuurd'],
                    'status' => $this->mapEventStatus(
                        (string) $remote['status']
                    ),
                    'werkt_met_groepen' => 0,
                    'groepstoeslag_bedrag' => $this->settingValue(
                        'group_supplement',
                        '10.00'
                    ),
                ]
            );

            $this->eventMap[$remoteId] = $targetId;
            $targetByKey[$key] = array_merge(
                $remote,
                ['event_id' => $targetId]
            );
            $inserted++;
        }

        $this->statistics['events_matched'] = $matched;
        $this->statistics['events_inserted_from_onecom'] = $inserted;
    }

    private function mergeEventRegistrations(): void
    {
        $targetRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY inschrijving_id',
            $this->qualified($this->targetDatabase, 'event_inschrijvingen')
        ));
        $targetByKey = [];
        $usedIds = [];

        foreach ($targetRows as $row) {
            $targetByKey[$this->registrationKey(
                (int) $row['event_id'],
                (int) $row['lid_id']
            )] = $row;
            $usedIds[(int) $row['inschrijving_id']] = true;
        }

        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;
        $matched = 0;
        $inserted = 0;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY inschrijving_id',
                $this->qualified($this->oneComDatabase, 'event_inschrijvingen')
            )) as $remote
        ) {
            $remoteId = (int) $remote['inschrijving_id'];
            $eventId = $this->eventMap[(int) $remote['event_id']] ?? null;
            $memberId = $this->memberMap[(int) $remote['lid_id']] ?? null;

            if ($eventId === null || $memberId === null) {
                throw new RuntimeException(sprintf(
                    'One.com-eventinschrijving %d kon niet worden gemapt.',
                    $remoteId
                ));
            }

            $key = $this->registrationKey($eventId, $memberId);
            $target = $targetByKey[$key] ?? null;

            if ($target !== null) {
                $this->eventRegistrationMap[$remoteId] =
                    (int) $target['inschrijving_id'];
                $matched++;

                continue;
            }

            $targetId = $this->allocateId($remoteId, $usedIds, $nextId);
            $this->insert(
                'event_inschrijvingen',
                [
                    'inschrijving_id' => $targetId,
                    'event_id' => $eventId,
                    'lid_id' => $memberId,
                    'status' => (string) $remote['status'],
                    'aangemeld_op' => (string) $remote['aangemeld_op'],
                    'uitschrijfreden' => $remote['uitschrijfreden'],
                    'annulatie_aangevraagd_op' => null,
                    'uitgeschreven_op' => $remote['uitgeschreven_op'],
                    'annulatie_bevestigd_door' => null,
                ]
            );

            $this->eventRegistrationMap[$remoteId] = $targetId;
            $targetByKey[$key] = [
                'inschrijving_id' => $targetId,
                'event_id' => $eventId,
                'lid_id' => $memberId,
            ];
            $inserted++;
        }

        $dayUsedIds = $this->usedIds(
            'event_inschrijving_dagen',
            'inschrijving_dag_id'
        );
        $nextDayId = $dayUsedIds === []
            ? 1
            : max(array_keys($dayUsedIds)) + 1;
        $targetDayKeys = [];

        foreach (
            $this->fetchAll(sprintf(
                'SELECT inschrijving_id, datum FROM %s',
                $this->qualified(
                    $this->targetDatabase,
                    'event_inschrijving_dagen'
                )
            )) as $targetDay
        ) {
            $targetDayKeys[$this->eventRegistrationDayKey(
                (int) $targetDay['inschrijving_id'],
                (string) $targetDay['datum']
            )] = true;
        }

        $daysInserted = 0;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY inschrijving_dag_id',
                $this->qualified($this->oneComDatabase, 'event_inschrijving_dagen')
            )) as $day
        ) {
            $remoteRegistrationId = (int) $day['inschrijving_id'];
            $targetRegistrationId =
                $this->eventRegistrationMap[$remoteRegistrationId] ?? null;

            if ($targetRegistrationId === null) {
                throw new RuntimeException(
                    'Een one.com-inschrijvingsdag kon niet worden gemapt.'
                );
            }

            $dayKey = $this->eventRegistrationDayKey(
                $targetRegistrationId,
                (string) $day['datum']
            );

            if (isset($targetDayKeys[$dayKey])) {
                continue;
            }

            $dayId = $this->allocateId(
                (int) $day['inschrijving_dag_id'],
                $dayUsedIds,
                $nextDayId
            );
            $this->insert(
                'event_inschrijving_dagen',
                [
                    'inschrijving_dag_id' => $dayId,
                    'inschrijving_id' => $targetRegistrationId,
                    'datum' => (string) $day['datum'],
                ]
            );
            $targetDayKeys[$dayKey] = true;
            $daysInserted++;
        }

        $this->statistics['event_registrations_matched'] = $matched;
        $this->statistics['event_registrations_inserted_from_onecom'] = $inserted;
        $this->statistics['event_registration_days_inserted_from_onecom'] =
            $daysInserted;
    }

    private function mergeLegacyShifts(): void
    {
        $stewardTypeId = $this->scalar(<<<SQL
            SELECT type_id
            FROM {$this->qualified($this->targetDatabase, 'shift_types')}
            WHERE LOWER(TRIM(naam)) = 'steward'
            ORDER BY type_id
            LIMIT 1
            SQL);

        if ($stewardTypeId <= 0) {
            throw new RuntimeException(
                'Het definitieve shifttype Steward ontbreekt.'
            );
        }

        $targetRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY shift_id',
            $this->qualified($this->targetDatabase, 'shifts')
        ));
        $targetByKey = [];
        $usedIds = [];

        foreach ($targetRows as $row) {
            $targetByKey[$this->targetShiftKey($row)] = $row;
            $usedIds[(int) $row['shift_id']] = true;
        }

        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;
        $matched = 0;
        $inserted = 0;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY shift_id',
                $this->qualified($this->oneComDatabase, 'event_shifts')
            )) as $remote
        ) {
            $remoteId = (int) $remote['shift_id'];
            $eventId = $this->eventMap[(int) $remote['event_id']] ?? null;

            if ($eventId === null) {
                throw new RuntimeException(sprintf(
                    'One.com-shift %d verwijst naar een ongemapt evenement.',
                    $remoteId
                ));
            }

            [$start, $end] = $this->legacyShiftPeriod($remote);
            $key = $this->shiftKey($eventId, $start, $end);
            $target = $targetByKey[$key] ?? null;

            if ($target !== null) {
                $this->shiftMap[$remoteId] = (int) $target['shift_id'];
                $matched++;

                continue;
            }

            $targetId = $this->allocateId($remoteId, $usedIds, $nextId);
            $this->insert(
                'shifts',
                [
                    'shift_id' => $targetId,
                    'event_id' => $eventId,
                    'type_id' => $stewardTypeId,
                    'naam' => $this->nullableString($remote['naam'] ?? null),
                    'start_op' => $start,
                    'eind_op' => $end,
                    'max_personen' => (int) $remote['max_personen'],
                    'status' => 'actief',
                    'aangemaakt_op' => $this->createdAt,
                    'bijgewerkt_op' => null,
                    'vergoeding_bedrag' => $this->settingValue(
                        'default_shift_compensation',
                        '30.00'
                    ),
                ]
            );

            $this->shiftMap[$remoteId] = $targetId;
            $targetByKey[$key] = [
                'shift_id' => $targetId,
                'event_id' => $eventId,
                'start_op' => $start,
                'eind_op' => $end,
            ];
            $inserted++;
        }

        $this->statistics['shifts_matched'] = $matched;
        $this->statistics['shifts_inserted_from_onecom'] = $inserted;
    }

    private function mergeLegacyShiftRegistrations(): void
    {
        $targetRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY inschrijving_id',
            $this->qualified($this->targetDatabase, 'shift_inschrijvingen')
        ));
        $targetByKey = [];
        $usedIds = [];

        foreach ($targetRows as $row) {
            $targetByKey[$this->registrationKey(
                (int) $row['shift_id'],
                (int) $row['lid_id']
            )] = $row;
            $usedIds[(int) $row['inschrijving_id']] = true;
        }

        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;
        $matched = 0;
        $inserted = 0;
        $presenceUpdated = 0;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY id',
                $this->qualified($this->oneComDatabase, 'shift_inschrijvingen')
            )) as $remote
        ) {
            $remoteId = (int) $remote['id'];
            $shiftId = $this->shiftMap[(int) $remote['shift_id']] ?? null;
            $memberId = $this->memberMap[(int) $remote['lid_id']] ?? null;

            if ($shiftId === null || $memberId === null) {
                throw new RuntimeException(sprintf(
                    'One.com-shiftinschrijving %d kon niet worden gemapt.',
                    $remoteId
                ));
            }

            $key = $this->registrationKey($shiftId, $memberId);
            $target = $targetByKey[$key] ?? null;

            if ($target !== null) {
                $remotePresenceAt = $this->nullableString(
                    $remote['aanwezig_afgevinkt_op'] ?? null
                );
                $targetPresenceAt = $this->nullableString(
                    $target['aanwezig_afgevinkt_op'] ?? null
                );
                $remoteWins = $this->dateIsLater(
                    $remotePresenceAt,
                    $targetPresenceAt
                ) || (
                    $targetPresenceAt === null
                    && $remotePresenceAt === null
                    && (bool) $remote['aanwezig']
                    && !(bool) $target['aanwezig']
                );

                if ($remoteWins) {
                    $statement = $this->pdo->prepare(sprintf(
                        'UPDATE %s
                        SET aanwezig = ?, aanwezig_afgevinkt_op = ?
                        WHERE inschrijving_id = ?',
                        $this->qualified(
                            $this->targetDatabase,
                            'shift_inschrijvingen'
                        )
                    ));
                    $statement->execute([
                        (bool) $remote['aanwezig'] ? 1 : 0,
                        $remotePresenceAt,
                        (int) $target['inschrijving_id'],
                    ]);
                    $presenceUpdated++;
                }

                $matched++;

                continue;
            }

            $targetId = $this->allocateId($remoteId, $usedIds, $nextId);
            $this->insert(
                'shift_inschrijvingen',
                [
                    'inschrijving_id' => $targetId,
                    'shift_id' => $shiftId,
                    'lid_id' => $memberId,
                    'status' => 'bevestigd',
                    'opmerking_lid' => null,
                    'goedgekeurd_door' => null,
                    'goedgekeurd_op' => null,
                    'geannuleerd_door' => null,
                    'geannuleerd_op' => null,
                    'annulatie_reden' => null,
                    'aanwezig' => (bool) $remote['aanwezig'] ? 1 : 0,
                    'aanwezig_afgevinkt_op' => $remote['aanwezig_afgevinkt_op'],
                    'aangemaakt_op' => $remote['aangemaakt_op']
                        ?? $this->createdAt,
                    'bijgewerkt_op' => null,
                ]
            );

            $targetByKey[$key] = [
                'inschrijving_id' => $targetId,
                'shift_id' => $shiftId,
                'lid_id' => $memberId,
                'aanwezig' => $remote['aanwezig'],
                'aanwezig_afgevinkt_op' => $remote['aanwezig_afgevinkt_op'],
            ];
            $inserted++;
        }

        $this->statistics['shift_registrations_matched'] = $matched;
        $this->statistics['shift_registrations_inserted_from_onecom'] = $inserted;
        $this->statistics['shift_presence_updated_from_onecom'] = $presenceUpdated;
    }

    private function mergeContactMessages(): void
    {
        $this->statistics['contact_messages_inserted_from_onecom'] =
            $this->mergeStandaloneRows(
                table: 'contact_berichten',
                idColumn: 'bericht_id',
                columns: [
                    'naam',
                    'email',
                    'bericht',
                    'gdpr_consent',
                    'consent_timestamp',
                    'ip_adres',
                    'aangemaakt_op',
                ]
            );
    }

    private function mergeNotifications(): void
    {
        $this->statistics['notifications_inserted_from_onecom'] =
            $this->mergeStandaloneRows(
                table: 'meldingen',
                idColumn: 'melding_id',
                columns: [
                    'type',
                    'titel',
                    'bericht',
                    'gelezen',
                    'aangemaakt_op',
                ]
            );
    }

    private function invalidatePasswordResetTokens(): void
    {
        $statement = $this->pdo->prepare(sprintf(
            'UPDATE %s
            SET reset_token = NULL, reset_token_expires = NULL
            WHERE reset_token IS NOT NULL OR reset_token_expires IS NOT NULL',
            $this->qualified($this->targetDatabase, 'gebruikers')
        ));
        $statement->execute();

        $this->statistics['password_reset_tokens_invalidated'] =
            $statement->rowCount();
    }

    /**
     * @param string[] $additionalColumns
     *
     * @return array<int, int>
     */
    private function mergeNamedReferenceTable(
        string $table,
        string $idColumn,
        string $nameColumn,
        array $additionalColumns
    ): array {
        if (!$this->tableExists($this->oneComDatabase, $table)) {
            return [];
        }

        $targetRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY %s',
            $this->qualified($this->targetDatabase, $table),
            $this->quoteIdentifier($idColumn)
        ));
        $targetByName = [];
        $usedIds = [];

        foreach ($targetRows as $row) {
            $name = strtolower(trim((string) $row[$nameColumn]));
            $targetByName[$name] = $row;
            $usedIds[(int) $row[$idColumn]] = true;
        }

        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;
        $map = [];
        $inserted = 0;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY %s',
                $this->qualified($this->oneComDatabase, $table),
                $this->quoteIdentifier($idColumn)
            )) as $remote
        ) {
            $remoteId = (int) $remote[$idColumn];
            $name = strtolower(trim((string) $remote[$nameColumn]));
            $target = $targetByName[$name] ?? null;

            if ($target !== null) {
                $map[$remoteId] = (int) $target[$idColumn];

                continue;
            }

            $targetId = $this->allocateId($remoteId, $usedIds, $nextId);
            $values = [
                $idColumn => $targetId,
                $nameColumn => (string) $remote[$nameColumn],
            ];

            foreach ($additionalColumns as $column) {
                $values[$column] = $remote[$column] ?? null;
            }

            $this->insert($table, $values);
            $map[$remoteId] = $targetId;
            $targetByName[$name] = array_merge(
                $remote,
                [$idColumn => $targetId]
            );
            $inserted++;
        }

        $this->statistics[$table . '_inserted_from_onecom'] = $inserted;

        return $map;
    }

    /**
     * @param string[] $columns
     */
    private function mergeStandaloneRows(
        string $table,
        string $idColumn,
        array $columns
    ): int {
        if (!$this->tableExists($this->oneComDatabase, $table)) {
            return 0;
        }

        $targetRows = $this->fetchAll(sprintf(
            'SELECT * FROM %s ORDER BY %s',
            $this->qualified($this->targetDatabase, $table),
            $this->quoteIdentifier($idColumn)
        ));
        $fingerprints = [];
        $usedIds = [];

        foreach ($targetRows as $row) {
            $fingerprints[$this->rowFingerprint($row, $columns)] = true;
            $usedIds[(int) $row[$idColumn]] = true;
        }

        $nextId = $usedIds === [] ? 1 : max(array_keys($usedIds)) + 1;
        $inserted = 0;

        foreach (
            $this->fetchAll(sprintf(
                'SELECT * FROM %s ORDER BY %s',
                $this->qualified($this->oneComDatabase, $table),
                $this->quoteIdentifier($idColumn)
            )) as $remote
        ) {
            $fingerprint = $this->rowFingerprint($remote, $columns);

            if (isset($fingerprints[$fingerprint])) {
                continue;
            }

            $id = $this->allocateId(
                (int) $remote[$idColumn],
                $usedIds,
                $nextId
            );
            $values = [$idColumn => $id];

            foreach ($columns as $column) {
                $values[$column] = $remote[$column] ?? null;
            }

            $this->insert($table, $values);
            $fingerprints[$fingerprint] = true;
            $inserted++;
        }

        return $inserted;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insertMember(
        int $memberId,
        array $row,
        ?string $bankAccount,
        ?string $identifier
    ): void {
        $this->insert(
            'leden',
            [
                'lid_id' => $memberId,
                'voornaam' => (string) $row['voornaam'],
                'achternaam' => (string) $row['achternaam'],
                'email' => $row['email'],
                'actief' => (bool) $row['actief'] ? 1 : 0,
                'straat' => $row['straat'],
                'postcode' => $row['postcode'],
                'gemeente' => $row['gemeente'],
                'land' => $row['land'],
                'telefoon' => $row['telefoon'],
                'geboortedatum' => $row['geboortedatum'],
                'geslacht' => $row['geslacht'],
                'opmerkingen' => $row['opmerkingen'],
                'gdpr_consent' => (bool) $row['gdpr_consent'] ? 1 : 0,
                'gdpr_timestamp' => $row['gdpr_timestamp'],
                'aangemaakt_op' => (string) $row['aangemaakt_op'],
                'bijgewerkt_op' => (string) $row['bijgewerkt_op'],
                'rekeningnummer' => $bankAccount,
                'rijksregisternummer' => $identifier,
                'tshirtmaat' => $row['tshirtmaat'],
            ]
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function updateMember(
        int $memberId,
        array $row,
        ?string $bankAccount,
        ?string $identifier,
        ?string $createdAt,
        ?string $updatedAt
    ): void {
        $statement = $this->pdo->prepare(sprintf(
            'UPDATE %s SET
                voornaam = ?, achternaam = ?, email = ?, actief = ?,
                straat = ?, postcode = ?, gemeente = ?, land = ?, telefoon = ?,
                geboortedatum = ?, geslacht = ?, opmerkingen = ?,
                gdpr_consent = ?, gdpr_timestamp = ?, aangemaakt_op = ?,
                bijgewerkt_op = ?, rekeningnummer = ?, rijksregisternummer = ?,
                tshirtmaat = ?
            WHERE lid_id = ?',
            $this->qualified($this->targetDatabase, 'leden')
        ));
        $statement->execute([
            (string) $row['voornaam'],
            (string) $row['achternaam'],
            $row['email'],
            (bool) $row['actief'] ? 1 : 0,
            $row['straat'],
            $row['postcode'],
            $row['gemeente'],
            $row['land'],
            $row['telefoon'],
            $row['geboortedatum'],
            $row['geslacht'],
            $row['opmerkingen'],
            (bool) $row['gdpr_consent'] ? 1 : 0,
            $row['gdpr_timestamp'],
            $createdAt ?? $this->createdAt,
            $updatedAt ?? $this->createdAt,
            $bankAccount,
            $identifier,
            $row['tshirtmaat'],
            $memberId,
        ]);
    }

    private function backupRemoteLegacyIdentifier(
        int $targetMemberId,
        mixed $value,
        mixed $updatedAt
    ): void {
        $legacy = $this->nullableString($value);

        if ($legacy === null || !$this->looksLikeLegacyCiphertext($legacy)) {
            return;
        }

        $exists = $this->scalarPrepared(
            sprintf(
                'SELECT COUNT(*) FROM %s WHERE lid_id = ?',
                $this->qualified(
                    $this->targetDatabase,
                    'leden_identificatie_legacy_backup_20260812'
                )
            ),
            [$targetMemberId]
        );

        if ($exists > 0) {
            return;
        }

        $this->insert(
            'leden_identificatie_legacy_backup_20260812',
            [
                'lid_id' => $targetMemberId,
                'rijksregisternummer_legacy' => $legacy,
                'bijgewerkt_op_legacy' => $this->nullableString($updatedAt),
                'gebackupt_op' => $this->createdAt,
            ]
        );
    }

    private function secureBankAccount(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        if ($this->encryption->isEncrypted($value)) {
            $this->encryption->decrypt($value);

            return $value;
        }

        return $this->encryption->encrypt($value);
    }

    private function secureNationalIdentifier(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        if ($this->encryption->isEncrypted($value)) {
            $this->encryption->decrypt($value);

            return $value;
        }

        $plain = $this->looksLikeLegacyCiphertext($value)
            ? $this->decryptLegacyIdentifier($value)
            : $value;

        return $this->encryption->encrypt($plain);
    }

    private function secureAllTargetMemberValues(): int
    {
        $rows = $this->fetchAll(sprintf(
            'SELECT lid_id, rekeningnummer, rijksregisternummer FROM %s',
            $this->qualified($this->targetDatabase, 'leden')
        ));
        $statement = $this->pdo->prepare(sprintf(
            'UPDATE %s
            SET rekeningnummer = ?, rijksregisternummer = ?
            WHERE lid_id = ?',
            $this->qualified($this->targetDatabase, 'leden')
        ));
        $secured = 0;

        foreach ($rows as $row) {
            $bank = $this->secureBankAccount(
                $row['rekeningnummer'] ?? null
            );
            $identifier = $this->secureExistingTargetIdentifier(
                $row['rijksregisternummer'] ?? null
            );

            if (
                $bank === $this->nullableString($row['rekeningnummer'] ?? null)
                && $identifier === $this->nullableString(
                    $row['rijksregisternummer'] ?? null
                )
            ) {
                continue;
            }

            $statement->execute([
                $bank,
                $identifier,
                (int) $row['lid_id'],
            ]);
            $secured++;
        }

        return $secured;
    }

    private function secureExistingTargetIdentifier(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        if ($this->encryption->isEncrypted($value)) {
            $this->encryption->decrypt($value);

            return $value;
        }

        $currentPlain = $this->encryption->decrypt($value);

        if ($currentPlain !== $value) {
            return $this->encryption->encrypt($currentPlain);
        }

        return $this->secureNationalIdentifier($value);
    }

    private function decryptLegacyIdentifier(string $value): string
    {
        $binary = base64_decode($value, true);

        if (!is_string($binary)) {
            throw new RuntimeException(
                'Een legacy-identificatienummer heeft een ongeldig formaat.'
            );
        }

        $plain = openssl_decrypt(
            substr($binary, 16),
            'AES-256-CBC',
            $this->legacyKey,
            OPENSSL_RAW_DATA,
            substr($binary, 0, 16)
        );

        if (!is_string($plain) || $plain === '') {
            throw new RuntimeException(
                'Een legacy-identificatienummer kon niet worden ontsleuteld.'
            );
        }

        return $plain;
    }

    private function looksLikeLegacyCiphertext(string $value): bool
    {
        if (
            $value === ''
            || strlen($value) % 4 !== 0
            || preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $value) !== 1
        ) {
            return false;
        }

        $binary = base64_decode($value, true);

        return is_string($binary)
            && strlen($binary) > 16
            && strlen(substr($binary, 16)) % 16 === 0;
    }

    private function verifyEncryptedMemberValues(): void
    {
        $rows = $this->fetchAll(sprintf(
            'SELECT lid_id, rekeningnummer, rijksregisternummer FROM %s',
            $this->qualified($this->targetDatabase, 'leden')
        ));

        foreach ($rows as $row) {
            foreach (['rekeningnummer', 'rijksregisternummer'] as $column) {
                $value = $this->nullableString($row[$column] ?? null);

                if ($value === null) {
                    continue;
                }

                if (!$this->encryption->isEncrypted($value)) {
                    throw new RuntimeException(sprintf(
                        'Gevoelig veld %s voor lid %d is niet versleuteld.',
                        $column,
                        (int) $row['lid_id']
                    ));
                }

                $plain = $this->encryption->decrypt($value);

                if ($plain === null || trim($plain) === '') {
                    throw new RuntimeException(sprintf(
                        'Gevoelig veld %s voor lid %d is niet ontsleutelbaar.',
                        $column,
                        (int) $row['lid_id']
                    ));
                }
            }
        }
    }

    private function verifyArchiveCoverage(): void
    {
        foreach (
            [
                'event_shifts',
                'shift_inschrijvingen',
                'shift_registrations',
                'shift_toewijzingen',
                'shift_types',
                'shifts',
            ] as $sourceTable
        ) {
            if (!$this->tableExists($this->oneComDatabase, $sourceTable)) {
                continue;
            }

            $archiveTable = $sourceTable . self::ARCHIVE_SUFFIX;
            $sourceCount = $this->scalar(sprintf(
                'SELECT COUNT(*) FROM %s',
                $this->qualified($this->oneComDatabase, $sourceTable)
            ));
            $archiveCount = $this->scalar(sprintf(
                'SELECT COUNT(*) FROM %s',
                $this->qualified($this->targetDatabase, $archiveTable)
            ));

            $this->assert(
                $sourceCount === $archiveCount,
                sprintf(
                    'Legacyback-up %s bevat %d in plaats van %d rijen.',
                    $archiveTable,
                    $archiveCount,
                    $sourceCount
                )
            );
        }
    }

    /**
     * @return array<string, int>
     */
    private function tableCounts(string $database): array
    {
        $counts = [];

        foreach (
            $this->fetchColumn(<<<SQL
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = {$this->pdo->quote($database)}
                  AND table_type = 'BASE TABLE'
                ORDER BY table_name
                SQL) as $table
        ) {
            $counts[$table] = $this->scalar(sprintf(
                'SELECT COUNT(*) FROM %s',
                $this->qualified($database, $table)
            ));
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(string $table): array
    {
        $counts = [];
        $statement = $this->pdo->query(sprintf(
            'SELECT status, COUNT(*) AS aantal FROM %s GROUP BY status ORDER BY status',
            $this->qualified($this->targetDatabase, $table)
        ));

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['status']] = (int) $row['aantal'];
        }

        return $counts;
    }

    private function settingValue(string $key, string $default): string
    {
        $statement = $this->pdo->prepare(sprintf(
            'SELECT waarde FROM %s WHERE sleutel = ? LIMIT 1',
            $this->qualified($this->targetDatabase, 'instellingen')
        ));
        $statement->execute([$key]);
        $value = $statement->fetchColumn();

        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : $default;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function eventKey(array $row): string
    {
        return implode('|', [
            strtolower(trim((string) $row['titel'])),
            (string) $row['startdatum'],
            (string) ($row['einddatum'] ?? ''),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function targetShiftKey(array $row): string
    {
        return $this->shiftKey(
            (int) $row['event_id'],
            (string) $row['start_op'],
            (string) $row['eind_op']
        );
    }

    private function shiftKey(int $eventId, string $start, string $end): string
    {
        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);

        return implode('|', [
            (string) $eventId,
            $startDate->format('Y-m-d'),
            $startDate->format('H:i:s'),
            $endDate->format('H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{0: string, 1: string}
     */
    private function legacyShiftPeriod(array $row): array
    {
        $date = (string) $row['shift_datum'];
        $startTime = $this->nullableString($row['starttijd'] ?? null);
        $endTime = $this->nullableString($row['eindtijd'] ?? null);

        if ($date === '' || $startTime === null || $endTime === null) {
            throw new RuntimeException(
                'Een legacyshift mist datum, starttijd of eindtijd.'
            );
        }

        $start = new DateTimeImmutable($date . ' ' . $startTime);
        $end = new DateTimeImmutable($date . ' ' . $endTime);

        if ($end <= $start) {
            $end = $end->modify('+1 day');
        }

        return [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ];
    }

    private function mapEventStatus(string $status): string
    {
        return match ($status) {
            'open' => 'gepubliceerd',
            'concept', 'afgesloten', 'geannuleerd' => $status,
            default => throw new RuntimeException(
                'Onbekende legacy-eventstatus aangetroffen.'
            ),
        };
    }

    private function registrationKey(int $parentId, int $memberId): string
    {
        return $parentId . ':' . $memberId;
    }

    private function eventRegistrationDayKey(
        int $registrationId,
        string $date
    ): string {
        return $registrationId . ':' . $date;
    }

    /**
     * @param array<string, mixed> $row
     * @param string[] $columns
     */
    private function rowFingerprint(array $row, array $columns): string
    {
        $values = [];

        foreach ($columns as $column) {
            $values[$column] = $row[$column] ?? null;
        }

        return hash(
            'sha256',
            (string) json_encode(
                $values,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }

    /**
     * @param array<int, true> $usedIds
     */
    private function allocateId(
        int $preferredId,
        array &$usedIds,
        int &$nextId
    ): int {
        if ($preferredId > 0 && !isset($usedIds[$preferredId])) {
            $usedIds[$preferredId] = true;
            $nextId = max($nextId, $preferredId + 1);

            return $preferredId;
        }

        while (isset($usedIds[$nextId])) {
            $nextId++;
        }

        $id = $nextId;
        $usedIds[$id] = true;
        $nextId++;

        return $id;
    }

    /**
     * @return array<int, true>
     */
    private function usedIds(string $table, string $idColumn): array
    {
        $used = [];

        foreach (
            $this->fetchColumn(sprintf(
                'SELECT %s FROM %s ORDER BY %s',
                $this->quoteIdentifier($idColumn),
                $this->qualified($this->targetDatabase, $table),
                $this->quoteIdentifier($idColumn)
            )) as $id
        ) {
            $used[(int) $id] = true;
        }

        return $used;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function insert(string $table, array $values): void
    {
        $this->assertIdentifier($table);

        if ($values === []) {
            throw new RuntimeException('Een lege insert is niet toegestaan.');
        }

        $columns = array_keys($values);

        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }

        $statement = $this->pdo->prepare(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->qualified($this->targetDatabase, $table),
            implode(', ', array_map($this->quoteIdentifier(...), $columns)),
            implode(', ', array_fill(0, count($columns), '?'))
        ));
        $statement->execute(array_values($values));
    }

    /**
     * @return array<int, string>
     */
    private function tableColumns(string $database, string $table): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = ?
              AND table_name = ?
            ORDER BY ordinal_position
            SQL);
        $statement->execute([$database, $table]);

        return array_map(
            static fn (mixed $value): string => (string) $value,
            $statement->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    private function tableExists(string $database, string $table): bool
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = ?
              AND table_name = ?
            SQL);
        $statement->execute([$database, $table]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $sql): array
    {
        $statement = $this->pdo->query($sql);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, string>
     */
    private function fetchColumn(string $sql): array
    {
        $statement = $this->pdo->query($sql);

        return array_map(
            static fn (mixed $value): string => (string) $value,
            $statement->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    private function scalar(string $sql): int
    {
        $value = $this->pdo->query($sql)->fetchColumn();

        return (int) $value;
    }

    /**
     * @param array<int, mixed> $values
     */
    private function scalarPrepared(string $sql, array $values): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);

        return (int) $statement->fetchColumn();
    }

    private function qualified(string $database, string $table): string
    {
        return $this->quoteIdentifier($database)
            . '.'
            . $this->quoteIdentifier($table);
    }

    private function quoteIdentifier(string $identifier): string
    {
        $this->assertIdentifier($identifier);

        return '`' . $identifier . '`';
    }

    private function assertIdentifier(string $identifier): void
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $identifier) !== 1) {
            throw new RuntimeException(
                'Ongeldige database- of tabelidentifier aangetroffen.'
            );
        }
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $email = $this->nullableString($email);

        return $email === null ? null : strtolower($email);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function dateIsLater(mixed $left, mixed $right): bool
    {
        $left = $this->nullableString($left);
        $right = $this->nullableString($right);

        if ($left === null) {
            return false;
        }

        if ($right === null) {
            return true;
        }

        return new DateTimeImmutable($left) > new DateTimeImmutable($right);
    }

    private function earliestDate(mixed $left, mixed $right): ?string
    {
        $left = $this->nullableString($left);
        $right = $this->nullableString($right);

        if ($left === null) {
            return $right;
        }

        if ($right === null) {
            return $left;
        }

        return new DateTimeImmutable($left) <= new DateTimeImmutable($right)
            ? $left
            : $right;
    }

    private function latestDate(mixed $left, mixed $right): ?string
    {
        $left = $this->nullableString($left);
        $right = $this->nullableString($right);

        if ($left === null) {
            return $right;
        }

        if ($right === null) {
            return $left;
        }

        return new DateTimeImmutable($left) >= new DateTimeImmutable($right)
            ? $left
            : $right;
    }

    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }
}
