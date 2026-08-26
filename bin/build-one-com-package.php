<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    throw new \RuntimeException(
        'Het one.com-pakket mag uitsluitend via de command line worden gebouwd.'
    );
}

if (!class_exists(\ZipArchive::class)) {
    throw new \RuntimeException('De PHP-extensie zip is vereist.');
}

$root = dirname(__DIR__);
$buildDirectory = $root . DIRECTORY_SEPARATOR . 'build';
$output = $buildDirectory
    . DIRECTORY_SEPARATOR
    . 'jan-primus-ledenbeheer-one-com.zip';

if (!is_dir($buildDirectory) && !mkdir($buildDirectory, 0775, true)) {
    throw new \RuntimeException('De buildmap kon niet worden aangemaakt.');
}

if (is_file($output) && !unlink($output)) {
    throw new \RuntimeException('Het bestaande deploymentpakket kon niet worden vervangen.');
}

$zip = new \ZipArchive();

if (
    $zip->open(
        $output,
        \ZipArchive::CREATE | \ZipArchive::OVERWRITE
    ) !== true
) {
    throw new \RuntimeException('Het deploymentpakket kon niet worden geopend.');
}

$normalize = static fn (string $path): string => str_replace('\\', '/', $path);

$addFile = static function (
    string $source,
    string $target
) use ($zip, $normalize): void {
    if (!is_file($source)) {
        throw new \RuntimeException(sprintf(
            'Vereist deploymentbestand ontbreekt: %s',
            $source
        ));
    }

    if (!$zip->addFile($source, $normalize($target))) {
        throw new \RuntimeException(sprintf(
            'Bestand kon niet aan het deploymentpakket worden toegevoegd: %s',
            $source
        ));
    }
};

$addTree = static function (
    string $sourceDirectory,
    string $targetDirectory,
    ?callable $include = null
) use ($zip, $normalize, $addFile): void {
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
            $sourceDirectory,
            \FilesystemIterator::SKIP_DOTS
        ),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $source = $file->getPathname();
        $relative = substr($source, strlen($sourceDirectory) + 1);
        $target = trim($targetDirectory, '/\\')
            . '/'
            . $normalize($relative);

        if ($include !== null && !$include($source, $target)) {
            continue;
        }

        $addFile($source, $target);
    }
};

foreach (['app', 'bootstrap', 'routes', 'src', 'vendor'] as $directory) {
    $addTree(
        $root . DIRECTORY_SEPARATOR . $directory,
        $directory
    );
}

$addTree(
    $root . DIRECTORY_SEPARATOR . 'config',
    'config',
    static function (string $source): bool {
        $localSegment = DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'local'
            . DIRECTORY_SEPARATOR;

        return !str_contains($source, $localSegment)
            || str_ends_with($source, '.example.php');
    }
);

$addTree(
    $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets',
    'assets'
);

foreach (
    [
        'storage/cache',
        'storage/logs',
        'storage/mail-attachments',
        'storage/sessions',
        'storage/temp',
        'storage/uploads',
        'config/local',
    ] as $directory
) {
    $zip->addEmptyDir($directory);
}

$addFile(
    $root . DIRECTORY_SEPARATOR . 'deployment'
        . DIRECTORY_SEPARATOR . 'one-com'
        . DIRECTORY_SEPARATOR . 'index.php',
    'index.php'
);
$addFile(
    $root . DIRECTORY_SEPARATOR . 'deployment'
        . DIRECTORY_SEPARATOR . 'one-com'
        . DIRECTORY_SEPARATOR . '.htaccess',
    '.htaccess'
);
$addFile(
    $root . DIRECTORY_SEPARATOR . 'bin'
        . DIRECTORY_SEPARATOR . 'process-mail-queue.php',
    'bin/process-mail-queue.php'
);
$addFile(
    $root . DIRECTORY_SEPARATOR . 'bin'
        . DIRECTORY_SEPARATOR . 'deployment-readiness.php',
    'bin/deployment-readiness.php'
);
$addFile(
    $root . DIRECTORY_SEPARATOR . 'composer.json',
    'composer.json'
);
$addFile(
    $root . DIRECTORY_SEPARATOR . 'composer.lock',
    'composer.lock'
);
$addFile(
    $root . DIRECTORY_SEPARATOR . 'docs'
        . DIRECTORY_SEPARATOR . '06-Deployment.md',
    'DEPLOYMENT.md'
);

$zip->setArchiveComment(
    'Jan Primus Ledenbeheer productiepackage voor one.com; bevat bewust geen lokale geheimen of databasedump.'
);
$zip->close();

fwrite(
    STDOUT,
    sprintf(
        "Deploymentpakket aangemaakt: %s (%s bytes)%s",
        $output,
        number_format((int) filesize($output), 0, ',', '.'),
        PHP_EOL
    )
);
