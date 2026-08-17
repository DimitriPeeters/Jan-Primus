<?php

declare(strict_types=1);

use AEFS\Core\Config;
use AEFS\Core\Database;
use App\Services\EncryptionService;

require dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR
    . 'vendor'
    . DIRECTORY_SEPARATOR
    . 'autoload.php';

const LEGACY_SECURITY_ENTRY =
    'AEFS_ledenadministratie/config/security.php';

const BACKUP_TABLE =
    'leden_identificatie_legacy_backup_20260812';

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException(
        'Deze migratie mag uitsluitend via de command line worden uitgevoerd.'
    );
}

$legacyProjectPath = null;

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--legacy-project=')) {
        $legacyProjectPath = trim(
            substr($argument, strlen('--legacy-project=')),
            " \t\n\r\0\x0B\"'"
        );
    }
}

if ($legacyProjectPath === null || $legacyProjectPath === '') {
    throw new RuntimeException(
        'Gebruik --legacy-project=pad/naar/AEFS_ledenadministratie.zip.'
    );
}

if (!is_file($legacyProjectPath)) {
    throw new RuntimeException(
        'Het opgegeven legacyproject werd niet gevonden.'
    );
}

$projectRoot = dirname(__DIR__, 2);
$config = new Config(
    $projectRoot . DIRECTORY_SEPARATOR . 'config'
);
$database = new Database($config);
$encryption = new EncryptionService();
$legacyKey = readLegacyEncryptionKey($legacyProjectPath);

createBackupTable($database);

$candidates = legacyCandidates($database);

if ($candidates === []) {
    fwrite(
        STDOUT,
        'Geen migreerbare legacy-identificatienummers gevonden.'
        . PHP_EOL
    );

    return;
}

/**
 * @var array<int, array{
 *     lid_id: int,
 *     legacy: string,
 *     bijgewerkt_op: string,
 *     plain: string,
 *     current: string
 * }> $migrationRows
 */
$migrationRows = [];

foreach ($candidates as $candidate) {
    $plain = decryptLegacyValue(
        $candidate['legacy'],
        $legacyKey
    );

    if ($plain === null) {
        throw new RuntimeException(
            sprintf(
                'De legacywaarde voor lid %d kon niet worden ontsleuteld; '
                . 'de migratie is afgebroken.',
                $candidate['lid_id']
            )
        );
    }

    if (
        $plain === ''
        || preg_match('//u', $plain) !== 1
    ) {
        throw new RuntimeException(
            sprintf(
                'De ontsleutelde waarde voor lid %d is ongeldig.',
                $candidate['lid_id']
            )
        );
    }

    $current = $encryption->encrypt($plain);

    if (
        $current === null
        || !str_starts_with($current, 'enc:v1:')
        || $encryption->decrypt($current) !== $plain
    ) {
        throw new RuntimeException(
            sprintf(
                'De nieuwe encryptie kon niet worden geverifieerd voor lid %d.',
                $candidate['lid_id']
            )
        );
    }

    $migrationRows[] = [
        'lid_id' => $candidate['lid_id'],
        'legacy' => $candidate['legacy'],
        'bijgewerkt_op' => $candidate['bijgewerkt_op'],
        'plain' => $plain,
        'current' => $current,
    ];
}

if ($migrationRows === []) {
    throw new RuntimeException(
        'Geen enkele legacywaarde kon met de aangetroffen sleutel worden ontsleuteld.'
    );
}

try {
    $database->transaction(
        function () use ($database, $migrationRows): void {
            $backup = $database->prepare(
                'INSERT INTO `' . BACKUP_TABLE . '`
                (
                    lid_id,
                    rijksregisternummer_legacy,
                    bijgewerkt_op_legacy,
                    gebackupt_op
                )
                VALUES
                (
                    :lid_id,
                    :legacy,
                    :bijgewerkt_op,
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    rijksregisternummer_legacy =
                        rijksregisternummer_legacy,
                    bijgewerkt_op_legacy = COALESCE(
                        bijgewerkt_op_legacy,
                        :backup_bijgewerkt_op
                    )'
            );

            $update = $database->prepare(<<<'SQL'
                UPDATE leden
                SET
                    rijksregisternummer = :current,
                    bijgewerkt_op = :bijgewerkt_op
                WHERE lid_id = :lid_id
                  AND rijksregisternummer = :legacy
                SQL);

            foreach ($migrationRows as $row) {
                assertCompatibleBackup(
                    database: $database,
                    lidId: $row['lid_id'],
                    legacy: $row['legacy'],
                    bijgewerktOp: $row['bijgewerkt_op']
                );

                $backup->execute([
                    'lid_id' => $row['lid_id'],
                    'legacy' => $row['legacy'],
                    'bijgewerkt_op' => $row['bijgewerkt_op'],
                    'backup_bijgewerkt_op' => $row['bijgewerkt_op'],
                ]);

                $update->execute([
                    'lid_id' => $row['lid_id'],
                    'legacy' => $row['legacy'],
                    'current' => $row['current'],
                    'bijgewerkt_op' => $row['bijgewerkt_op'],
                ]);

                if ($update->rowCount() !== 1) {
                    throw new RuntimeException(
                        sprintf(
                            'Lid %d werd gelijktijdig gewijzigd; de migratie is afgebroken.',
                            $row['lid_id']
                        )
                    );
                }
            }
        }
    );
} catch (Throwable $throwable) {
    throw new RuntimeException(
        'De legacy-identificatienummers konden niet veilig worden gemigreerd.',
        0,
        $throwable
    );
}

verifyMigration(
    database: $database,
    encryption: $encryption,
    migrationRows: $migrationRows
);

$migratedCount = count($migrationRows);
$skippedCount = count($candidates) - $migratedCount;

unset($legacyKey, $migrationRows);

fwrite(
    STDOUT,
    sprintf(
        'Migratie geslaagd: %d waarden omgezet, %d overgeslagen, %d back-ups aanwezig.',
        $migratedCount,
        $skippedCount,
        backupCount($database)
    ) . PHP_EOL
);

/**
 * @return array<int, array{
 *     lid_id: int,
 *     legacy: string,
 *     bijgewerkt_op: string
 * }>
 */
function legacyCandidates(Database $database): array
{
    $statement = $database->query(<<<'SQL'
        SELECT
            lid_id,
            rijksregisternummer,
            bijgewerkt_op
        FROM leden
        WHERE rijksregisternummer IS NOT NULL
          AND TRIM(rijksregisternummer) <> ''
          AND rijksregisternummer NOT LIKE 'enc:v1:%'
        ORDER BY lid_id ASC
        SQL);

    $candidates = [];

    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $legacy = trim(
            (string) ($row['rijksregisternummer'] ?? '')
        );

        if (!looksLikeLegacyCiphertext($legacy)) {
            continue;
        }

        $candidates[] = [
            'lid_id' => (int) ($row['lid_id'] ?? 0),
            'legacy' => $legacy,
            'bijgewerkt_op' => (string) ($row['bijgewerkt_op'] ?? ''),
        ];
    }

    return $candidates;
}

function createBackupTable(Database $database): void
{
    $database->execute(
        'CREATE TABLE IF NOT EXISTS `' . BACKUP_TABLE . '` (
            `lid_id` INT NOT NULL,
            `rijksregisternummer_legacy` VARCHAR(512) NOT NULL,
            `bijgewerkt_op_legacy` TIMESTAMP NULL DEFAULT NULL,
            `gebackupt_op` DATETIME NOT NULL,
            PRIMARY KEY (`lid_id`)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_general_ci'
    );

    $columnExists = (int) $database->query(
        "SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = '" . BACKUP_TABLE . "'
          AND column_name = 'bijgewerkt_op_legacy'"
    )->fetchColumn() > 0;

    if (!$columnExists) {
        $database->execute(
            'ALTER TABLE `' . BACKUP_TABLE . '`
            ADD COLUMN `bijgewerkt_op_legacy`
                TIMESTAMP NULL DEFAULT NULL
            AFTER `rijksregisternummer_legacy`'
        );
    }
}

function assertCompatibleBackup(
    Database $database,
    int $lidId,
    string $legacy,
    string $bijgewerktOp
): void {
    $statement = $database->prepare(
        'SELECT
            rijksregisternummer_legacy,
            bijgewerkt_op_legacy
        FROM `' . BACKUP_TABLE . '`
        WHERE lid_id = :lid_id
        LIMIT 1'
    );

    $statement->execute([
        'lid_id' => $lidId,
    ]);

    $backup = $statement->fetch(PDO::FETCH_ASSOC);

    if ($backup === false) {
        return;
    }

    if (
        !hash_equals(
            (string) ($backup['rijksregisternummer_legacy'] ?? ''),
            $legacy
        )
        || (
            ($backup['bijgewerkt_op_legacy'] ?? null) !== null
            && (string) $backup['bijgewerkt_op_legacy'] !== $bijgewerktOp
        )
    ) {
        throw new RuntimeException(
            sprintf(
                'De bestaande back-up voor lid %d wijkt af; de migratie is afgebroken.',
                $lidId
            )
        );
    }
}

function backupCount(Database $database): int
{
    return (int) $database->query(
        'SELECT COUNT(*) FROM `' . BACKUP_TABLE . '`'
    )->fetchColumn();
}

function readLegacyEncryptionKey(string $zipPath): string
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
            LEGACY_SECURITY_ENTRY
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

    $keyIndex = array_search(
        'ENCRYPTION_KEY',
        $strings,
        true
    );

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

function looksLikeLegacyCiphertext(string $value): bool
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

function decryptLegacyValue(
    string $value,
    string $legacyKey
): ?string {
    if (!looksLikeLegacyCiphertext($value)) {
        return null;
    }

    $binary = base64_decode($value, true);

    if (!is_string($binary)) {
        return null;
    }

    $plain = openssl_decrypt(
        substr($binary, 16),
        'AES-256-CBC',
        $legacyKey,
        OPENSSL_RAW_DATA,
        substr($binary, 0, 16)
    );

    return is_string($plain)
        ? $plain
        : null;
}

/**
 * @param array<int, array{
 *     lid_id: int,
 *     legacy: string,
 *     bijgewerkt_op: string,
 *     plain: string,
 *     current: string
 * }> $migrationRows
 */
function verifyMigration(
    Database $database,
    EncryptionService $encryption,
    array $migrationRows
): void {
    $select = $database->prepare(<<<'SQL'
        SELECT rijksregisternummer
        FROM leden
        WHERE lid_id = :lid_id
        LIMIT 1
        SQL);

    foreach ($migrationRows as $row) {
        $select->execute([
            'lid_id' => $row['lid_id'],
        ]);

        $current = $select->fetchColumn();

        if (
            !is_string($current)
            || !str_starts_with($current, 'enc:v1:')
            || $encryption->decrypt($current) !== $row['plain']
        ) {
            throw new RuntimeException(
                sprintf(
                    'De eindcontrole faalde voor lid %d.',
                    $row['lid_id']
                )
            );
        }
    }
}
