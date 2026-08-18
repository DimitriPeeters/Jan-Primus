<?php

declare(strict_types=1);

use AEFS\Core\Config;
use AEFS\Database\Cutover\OneComCutoverBuilder;
use App\Services\EncryptionService;

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException(
        'De one.com-cutoverbuilder mag uitsluitend via de command line worden uitgevoerd.'
    );
}

$root = dirname(__DIR__, 2);

require $root
    . DIRECTORY_SEPARATOR
    . 'vendor'
    . DIRECTORY_SEPARATOR
    . 'autoload.php';
require dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'Cutover'
    . DIRECTORY_SEPARATOR
    . 'OneComCutoverBuilder.php';

/**
 * @param array<string, mixed> $options
 */
function optionString(array $options, string $name, string $default): string
{
    $value = $options[$name] ?? $default;

    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException(sprintf(
            'Optie --%s moet een niet-lege waarde bevatten.',
            $name
        ));
    }

    return trim($value);
}

function absolutePath(string $root, string $path): string
{
    if (
        preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
        || str_starts_with($path, DIRECTORY_SEPARATOR)
    ) {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    return $root
        . DIRECTORY_SEPARATOR
        . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function assertDatabaseIdentifier(string $database): void
{
    if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
        throw new RuntimeException(sprintf(
            'Ongeldige databasenaam: %s.',
            $database
        ));
    }
}

function quoteIdentifier(string $identifier): string
{
    assertDatabaseIdentifier($identifier);

    return '`' . $identifier . '`';
}

function databaseExists(PDO $pdo, string $database): bool
{
    $statement = $pdo->prepare(<<<SQL
        SELECT COUNT(*)
        FROM information_schema.schemata
        WHERE schema_name = :database
        SQL);
    $statement->execute(['database' => $database]);

    return (int) $statement->fetchColumn() > 0;
}

function dropTemporaryDatabase(PDO $pdo, string $database): void
{
    if (!str_starts_with($database, 'aefs_v2_cutover_')) {
        throw new RuntimeException(sprintf(
            'Weigering om niet-tijdelijke database %s te verwijderen.',
            $database
        ));
    }

    $pdo->exec('DROP DATABASE IF EXISTS ' . quoteIdentifier($database));
}

function createTemporaryDatabase(PDO $pdo, string $database): void
{
    if (!str_starts_with($database, 'aefs_v2_cutover_')) {
        throw new RuntimeException(sprintf(
            'Tijdelijke database %s mist het verplichte cutover-prefix.',
            $database
        ));
    }

    $pdo->exec(sprintf(
        'CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        quoteIdentifier($database)
    ));
}

/**
 * @param string[] $command
 */
function runProcess(array $command, ?string $inputFile = null): void
{
    $descriptors = [
        0 => $inputFile === null
            ? ['pipe', 'r']
            : ['file', $inputFile, 'rb'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $pipes = [];
    $process = proc_open(
        $command,
        $descriptors,
        $pipes,
        null,
        null,
        ['bypass_shell' => true]
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Een MySQL-proces kon niet worden gestart.');
    }

    if ($inputFile === null) {
        fclose($pipes[0]);
    }

    $standardOutput = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $standardError = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        $safeError = trim((string) $standardError);

        throw new RuntimeException(sprintf(
            'MySQL-proces stopte met code %d%s.',
            $exitCode,
            $safeError === '' ? '' : ': ' . $safeError
        ));
    }

    if (trim((string) $standardOutput) !== '') {
        fwrite(STDOUT, trim((string) $standardOutput) . PHP_EOL);
    }
}

function mysqlOptionValue(string $value): string
{
    return '"'
        . str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            $value
        )
        . '"';
}

function writeMysqlDefaultsFile(
    string $path,
    string $host,
    int $port,
    string $username,
    string $password
): void {
    $content = implode(
        PHP_EOL,
        [
            '[client]',
            'host=' . mysqlOptionValue($host),
            'port=' . $port,
            'user=' . mysqlOptionValue($username),
            'password=' . mysqlOptionValue($password),
            'default-character-set=utf8mb4',
            '',
        ]
    );

    if (file_put_contents($path, $content, LOCK_EX) === false) {
        throw new RuntimeException(
            'Het tijdelijke MySQL-configuratiebestand kon niet worden geschreven.'
        );
    }

    @chmod($path, 0600);
}

function validateSqlExport(string $path, string $label): void
{
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException(sprintf(
            '%s ontbreekt of is niet leesbaar: %s.',
            $label,
            $path
        ));
    }

    $contents = file_get_contents($path);

    if ($contents === false || trim($contents) === '') {
        throw new RuntimeException($label . ' is leeg.');
    }

    if (
        preg_match('/^\s*CREATE\s+DATABASE\b/im', $contents) === 1
        || preg_match('/^\s*USE\s+[`\w-]+\s*;/im', $contents) === 1
    ) {
        throw new RuntimeException(sprintf(
            '%s mag geen CREATE DATABASE- of USE-opdracht bevatten.',
            $label
        ));
    }
}

function addPreImportCleanupStatements(string $path): void
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException(
            'De gebouwde cutoverdump kon niet voor pre-importopruiming worden gelezen.'
        );
    }

    $tableMatches = [];
    $matchCount = preg_match_all(
        '/^CREATE TABLE `([A-Za-z0-9_]+)`/m',
        $contents,
        $tableMatches
    );

    if ($matchCount === false || $matchCount === 0) {
        throw new RuntimeException(
            'De cutoverdump bevat geen herkenbare doeltabellen.'
        );
    }

    $tables = array_values(array_unique(array_merge(
        $tableMatches[1],
        [
            'mail_logs',
            'shift_registrations',
            'shift_toewijzingen',
            'event_shifts',
        ]
    )));
    sort($tables, SORT_STRING);

    $marker = '/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;';
    $cleanupStatements = array_map(
        static fn (string $table): string => sprintf(
            'DROP TABLE IF EXISTS `%s`;',
            $table
        ),
        $tables
    );
    $cleanup = $marker
        . PHP_EOL
        . implode(PHP_EOL, $cleanupStatements);
    $updated = str_replace(
        $marker,
        $cleanup,
        $contents,
        $replacements
    );

    if ($replacements !== 1) {
        throw new RuntimeException(
            'De foreign-keymarkering voor veilige pre-importopruiming ontbreekt of is dubbel.'
        );
    }

    if (file_put_contents($path, $updated, LOCK_EX) === false) {
        throw new RuntimeException(
            'De veilige pre-importopruiming kon niet aan de cutoverdump worden toegevoegd.'
        );
    }
}

/**
 * @param array<string, mixed> $left
 * @param array<string, mixed> $right
 */
function assertSameTableCounts(array $left, array $right): void
{
    if ($left !== $right) {
        throw new RuntimeException(
            'De herimporteerde cutoverdump heeft afwijkende tabelaantallen.'
        );
    }
}

$options = getopt(
    '',
    [
        'onecom-export:',
        'legacy-project:',
        'source-database:',
        'onecom-database:',
        'target-database:',
        'verify-database:',
        'output:',
        'report:',
        'mysql:',
        'mysqldump:',
        'replace-target',
        'keep-temporary',
    ]
);

if ($options === false) {
    throw new RuntimeException('De command-lineopties konden niet worden gelezen.');
}

$config = new Config($root . DIRECTORY_SEPARATOR . 'config');
$timezone = trim((string) $config->get('app.timezone', 'Europe/Brussels'));

if ($timezone === '' || !date_default_timezone_set($timezone)) {
    throw new RuntimeException('De applicatietijdzone is ongeldig.');
}

$connection = $config->get('database.connections.mysql', []);

if (!is_array($connection)) {
    throw new RuntimeException('De lokale MySQL-configuratie ontbreekt.');
}

$host = trim((string) ($connection['host'] ?? 'localhost'));
$port = (int) ($connection['port'] ?? 3306);
$username = trim((string) ($connection['username'] ?? 'root'));
$password = (string) ($connection['password'] ?? '');
$sourceDatabase = optionString(
    $options,
    'source-database',
    (string) ($connection['database'] ?? 'aefs_v2')
);
$oneComDatabase = optionString(
    $options,
    'onecom-database',
    'aefs_v2_cutover_onecom_20260818'
);
$targetDatabase = optionString(
    $options,
    'target-database',
    'aefs_v2_cutover_merged_20260818'
);
$verifyDatabase = optionString(
    $options,
    'verify-database',
    'aefs_v2_cutover_verify_20260818'
);

foreach (
    [
        $sourceDatabase,
        $oneComDatabase,
        $targetDatabase,
        $verifyDatabase,
    ] as $database
) {
    assertDatabaseIdentifier($database);
}

if (
    count(array_unique([
        $sourceDatabase,
        $oneComDatabase,
        $targetDatabase,
        $verifyDatabase,
    ])) !== 4
) {
    throw new RuntimeException(
        'Bron-, one.com-, doel- en verificatiedatabase moeten verschillend zijn.'
    );
}

foreach ([$oneComDatabase, $targetDatabase, $verifyDatabase] as $database) {
    if (!str_starts_with($database, 'aefs_v2_cutover_')) {
        throw new RuntimeException(sprintf(
            'Tijdelijke database %s mist het verplichte cutover-prefix.',
            $database
        ));
    }
}

$oneComExport = absolutePath(
    $root,
    optionString(
        $options,
        'onecom-export',
        'build/onecom-before-aefs-20260818.sql'
    )
);
$legacyProject = absolutePath(
    $root,
    optionString(
        $options,
        'legacy-project',
        'D:/AEFS_ledenadministratie.zip'
    )
);
$outputPath = absolutePath(
    $root,
    optionString(
        $options,
        'output',
        'build/aefs-v2-one-com-cutover.sql'
    )
);
$reportPath = absolutePath(
    $root,
    optionString(
        $options,
        'report',
        'build/aefs-v2-one-com-cutover-report.json'
    )
);
$baselinePath = $root
    . DIRECTORY_SEPARATOR
    . 'database'
    . DIRECTORY_SEPARATOR
    . 'database.sql';
$mysqlPath = absolutePath(
    $root,
    optionString(
        $options,
        'mysql',
        'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe'
    )
);
$mysqldumpPath = absolutePath(
    $root,
    optionString(
        $options,
        'mysqldump',
        'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump.exe'
    )
);
$buildDirectory = realpath($root . DIRECTORY_SEPARATOR . 'build');

if ($buildDirectory === false) {
    throw new RuntimeException('De genegeerde buildmap ontbreekt.');
}

foreach ([$outputPath, $reportPath] as $privatePath) {
    $parent = realpath(dirname($privatePath));

    if (
        $parent === false
        || strcasecmp($parent, $buildDirectory) !== 0
    ) {
        throw new RuntimeException(
            'Private cutoverbestanden moeten rechtstreeks in de genegeerde buildmap staan.'
        );
    }
}

validateSqlExport($oneComExport, 'De one.com-export');
validateSqlExport($baselinePath, 'De actuele databasebaseline');

foreach ([$legacyProject, $mysqlPath, $mysqldumpPath] as $requiredPath) {
    if (!is_file($requiredPath)) {
        throw new RuntimeException(sprintf(
            'Vereist bestand ontbreekt: %s.',
            $requiredPath
        ));
    }
}

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%d;charset=utf8mb4',
        $host,
        $port
    ),
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

if (!databaseExists($pdo, $sourceDatabase)) {
    throw new RuntimeException(sprintf(
        'De lokale brondatabase %s bestaat niet.',
        $sourceDatabase
    ));
}

$temporaryDatabases = [
    $oneComDatabase,
    $targetDatabase,
    $verifyDatabase,
];
$replaceTarget = array_key_exists('replace-target', $options);
$keepTemporary = array_key_exists('keep-temporary', $options);

foreach ($temporaryDatabases as $database) {
    if (!databaseExists($pdo, $database)) {
        continue;
    }

    if (!$replaceTarget) {
        throw new RuntimeException(sprintf(
            'Tijdelijke database %s bestaat al. Gebruik --replace-target om uitsluitend deze cutoverdatabases opnieuw op te bouwen.',
            $database
        ));
    }

    dropTemporaryDatabase($pdo, $database);
}

$temporaryDirectory = $root
    . DIRECTORY_SEPARATOR
    . 'storage'
    . DIRECTORY_SEPARATOR
    . 'temp';

if (!is_dir($temporaryDirectory) && !mkdir($temporaryDirectory, 0775, true)) {
    throw new RuntimeException('De tijdelijke opslagmap kon niet worden aangemaakt.');
}

$defaultsFile = tempnam($temporaryDirectory, 'aefs-mysql-');

if ($defaultsFile === false) {
    throw new RuntimeException('Een tijdelijk MySQL-configuratiebestand kon niet worden aangemaakt.');
}

$completed = false;

try {
    writeMysqlDefaultsFile(
        $defaultsFile,
        $host,
        $port,
        $username,
        $password
    );

    createTemporaryDatabase($pdo, $oneComDatabase);
    createTemporaryDatabase($pdo, $targetDatabase);
    createTemporaryDatabase($pdo, $verifyDatabase);

    fwrite(STDOUT, '[1/5] one.com-export importeren in tijdelijke database...' . PHP_EOL);
    runProcess(
        [
            $mysqlPath,
            '--defaults-extra-file=' . $defaultsFile,
            '--database=' . $oneComDatabase,
        ],
        $oneComExport
    );

    fwrite(STDOUT, '[2/5] Actuele schema-baseline voorbereiden...' . PHP_EOL);
    runProcess(
        [
            $mysqlPath,
            '--defaults-extra-file=' . $defaultsFile,
            '--database=' . $targetDatabase,
        ],
        $baselinePath
    );

    fwrite(STDOUT, '[3/5] Lokale en one.com-gegevens veilig samenvoegen...' . PHP_EOL);
    $legacyKey = OneComCutoverBuilder::readLegacyEncryptionKey(
        $legacyProject
    );
    $builder = new OneComCutoverBuilder(
        $pdo,
        new EncryptionService($config),
        $legacyKey,
        $sourceDatabase,
        $oneComDatabase,
        $targetDatabase
    );
    $buildReport = $builder->build();

    fwrite(STDOUT, '[4/5] Private cutoverdump genereren...' . PHP_EOL);

    if (is_file($outputPath) && !unlink($outputPath)) {
        throw new RuntimeException('De vorige private cutoverdump kon niet worden vervangen.');
    }

    runProcess([
        $mysqldumpPath,
        '--defaults-extra-file=' . $defaultsFile,
        '--default-character-set=utf8mb4',
        '--single-transaction',
        '--routines',
        '--events',
        '--triggers',
        '--hex-blob',
        '--set-gtid-purged=OFF',
        '--no-tablespaces',
        '--column-statistics=0',
        '--skip-add-locks',
        '--skip-comments',
        '--skip-dump-date',
        '--result-file=' . $outputPath,
        $targetDatabase,
    ]);
    addPreImportCleanupStatements($outputPath);
    validateSqlExport($outputPath, 'De gebouwde cutoverdump');

    fwrite(STDOUT, '[5/5] Bestaande one.com-database vervangen en integraal verifiëren...' . PHP_EOL);
    runProcess(
        [
            $mysqlPath,
            '--defaults-extra-file=' . $defaultsFile,
            '--database=' . $verifyDatabase,
        ],
        $oneComExport
    );
    runProcess(
        [
            $mysqlPath,
            '--defaults-extra-file=' . $defaultsFile,
            '--database=' . $verifyDatabase,
        ],
        $outputPath
    );
    $verifyBuilder = new OneComCutoverBuilder(
        $pdo,
        new EncryptionService($config),
        $legacyKey,
        $sourceDatabase,
        $oneComDatabase,
        $verifyDatabase
    );
    $importVerification = $verifyBuilder->verify();
    assertSameTableCounts(
        $buildReport['verification']['table_counts'],
        $importVerification['table_counts']
    );

    $report = [
        'generated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        'source_export' => [
            'file' => basename($oneComExport),
            'sha256' => hash_file('sha256', $oneComExport),
        ],
        'cutover_export' => [
            'file' => basename($outputPath),
            'sha256' => hash_file('sha256', $outputPath),
            'bytes' => filesize($outputPath),
        ],
        'build' => $buildReport,
        'onecom_replacement_verification' => $importVerification,
    ];
    $json = json_encode(
        $report,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );

    if (file_put_contents($reportPath, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Het private cutoverrapport kon niet worden geschreven.');
    }

    $completed = true;

    fwrite(STDOUT, PHP_EOL . '[OK] De private cutoverdump is volledig reproduceerbaar.' . PHP_EOL);
    fwrite(STDOUT, 'Dump: ' . $outputPath . PHP_EOL);
    fwrite(STDOUT, 'Rapport: ' . $reportPath . PHP_EOL);
} catch (Throwable $throwable) {
    fwrite(
        STDERR,
        PHP_EOL
            . '[FOUT] '
            . $throwable->getMessage()
            . PHP_EOL
    );

    $previous = $throwable->getPrevious();

    if ($previous !== null) {
        fwrite(STDERR, 'Oorzaak: ' . $previous->getMessage() . PHP_EOL);
    }
} finally {
    if (is_file($defaultsFile)) {
        unlink($defaultsFile);
    }

    if ($completed && !$keepTemporary) {
        foreach (array_reverse($temporaryDatabases) as $database) {
            dropTemporaryDatabase($pdo, $database);
        }
    }
}

if (!$completed) {
    fwrite(
        STDERR,
        'De tijdelijke cutoverdatabases zijn behouden voor diagnose; de lokale brondatabase is niet gewijzigd.'
            . PHP_EOL
    );
}

exit($completed ? 0 : 1);
