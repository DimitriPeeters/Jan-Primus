<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException(
        'De mailworkerconfiguratie mag uitsluitend via de command line worden aangemaakt.'
    );
}

$root = dirname(__DIR__);
$options = getopt('', ['output:']);
$configuredOutput = $options['output'] ?? null;

if (is_array($configuredOutput)) {
    throw new RuntimeException('Er mag maar één uitvoerpad worden opgegeven.');
}

$output = is_string($configuredOutput) && trim($configuredOutput) !== ''
    ? trim($configuredOutput)
    : $root
        . DIRECTORY_SEPARATOR
        . 'config'
        . DIRECTORY_SEPARATOR
        . 'local'
        . DIRECTORY_SEPARATOR
        . 'mail_worker.php';

$isAbsolute = preg_match(
    '#^(?:[A-Za-z]:[\\\\/]|[\\\\/]{2}|/)#',
    $output
) === 1;

if (!$isAbsolute) {
    $output = $root . DIRECTORY_SEPARATOR . $output;
}

$directory = dirname($output);

if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    throw new RuntimeException('De doelmap kon niet worden aangemaakt.');
}

$rootPath = realpath($root);
$directoryPath = realpath($directory);

if (
    $rootPath === false
    || $directoryPath === false
    || (
        strcasecmp($directoryPath, $rootPath) !== 0
        && !str_starts_with(
            strtolower($directoryPath),
            strtolower(rtrim($rootPath, DIRECTORY_SEPARATOR))
                . strtolower(DIRECTORY_SEPARATOR)
        )
    )
) {
    throw new RuntimeException(
        'De mailworkerconfiguratie mag alleen binnen deze repository worden aangemaakt.'
    );
}

$handle = @fopen($output, 'x');

if ($handle === false) {
    throw new RuntimeException(
        'Het doelbestand bestaat al of kon niet veilig worden aangemaakt.'
    );
}

$token = bin2hex(random_bytes(32));
$contents = <<<PHP
<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'token' => '{$token}',
];
PHP;

try {
    if (fwrite($handle, $contents . PHP_EOL) === false) {
        throw new RuntimeException(
            'De mailworkerconfiguratie kon niet worden geschreven.'
        );
    }
} finally {
    fclose($handle);
}

fwrite(
    STDOUT,
    'Mailworkerconfiguratie veilig aangemaakt: '
        . $output
        . PHP_EOL
        . 'De token werd bewust niet in de terminal getoond.'
        . PHP_EOL
);
