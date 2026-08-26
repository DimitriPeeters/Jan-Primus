<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Dit controleprogramma mag alleen via de opdrachtregel worden uitgevoerd.\n");
    exit(1);
}

$options = getopt('', ['wordpress-prefix:']);
$wordpressPrefix = trim((string) ($options['wordpress-prefix'] ?? ''));

if ($wordpressPrefix === '') {
    fwrite(
        STDERR,
        "Gebruik: php bin/check-shared-database.php --wordpress-prefix=wp_\n"
    );
    exit(1);
}

if (preg_match('/^[A-Za-z0-9_]+$/', $wordpressPrefix) !== 1) {
    fwrite(STDERR, "De WordPress-prefix bevat ongeldige tekens.\n");
    exit(1);
}

$configuration = require dirname(__DIR__) . '/config/database.php';
$connectionName = (string) ($configuration['default'] ?? 'mysql');
$connection = $configuration['connections'][$connectionName] ?? null;

if (!is_array($connection)) {
    fwrite(STDERR, "De actieve databaseverbinding is niet geconfigureerd.\n");
    exit(1);
}

$database = trim((string) ($connection['database'] ?? ''));

if ($database === '') {
    fwrite(STDERR, "De databasenaam ontbreekt in de lokale configuratie.\n");
    exit(1);
}

$tables = [
    'leden',
    'gebruikers',
    'audit_logs',
    'instellingen',
    'evenementen',
    'event_inschrijvingen',
    'event_inschrijving_dagen',
    'shift_types',
    'shifts',
    'shift_inschrijvingen',
    'dag_aanwezigheden',
    'mailings',
    'mailing_bijlagen',
    'mailing_ontvangers',
    'ledenbeheer_migraties',
];

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    (string) ($connection['host'] ?? 'localhost'),
    (int) ($connection['port'] ?? 3306),
    $database,
    (string) ($connection['charset'] ?? 'utf8mb4')
);

try {
    $pdo = new PDO(
        $dsn,
        (string) ($connection['username'] ?? ''),
        (string) ($connection['password'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_COLUMN,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $statement = $pdo->prepare(
        'SELECT table_name
         FROM information_schema.tables
         WHERE table_schema = :database'
    );
    $statement->execute(['database' => $database]);

    $existingTables = array_map(
        static fn (mixed $table): string => (string) $table,
        $statement->fetchAll()
    );
} catch (PDOException $exception) {
    fwrite(
        STDERR,
        "Databasecontrole mislukt. Controleer de lokale verbindingsgegevens.\n"
    );
    exit(1);
}

$wordpressTables = array_values(
    array_filter(
        $existingTables,
        static fn (string $table): bool => str_starts_with(
            $table,
            $wordpressPrefix
        )
    )
);

if ($wordpressTables === []) {
    fwrite(
        STDERR,
        "Geen WordPress-tabellen gevonden met prefix '{$wordpressPrefix}'. "
        . "Stop: controleer database en prefix.\n"
    );
    exit(1);
}

$conflicts = array_values(array_intersect($tables, $existingTables));

echo sprintf(
    "Databaseverbinding geslaagd; %d WordPress-tabellen gevonden met prefix '%s'.\n",
    count($wordpressTables),
    $wordpressPrefix
);

if ($conflicts !== []) {
    fwrite(
        STDERR,
        "Installatie niet veilig: deze Ledenbeheer-tabellen bestaan al:\n- "
        . implode("\n- ", $conflicts)
        . "\n"
    );
    exit(2);
}

echo "Geen tabelconflicten gevonden. Het additieve installatieschema kan worden beoordeeld.\n";
exit(0);
