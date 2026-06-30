<?php

declare(strict_types=1);

namespace AEFS\Core;

use RuntimeException;

final class Logger
{
    private static ?Logger $instance = null;

    private string $logDirectory;

    private function __construct()
    {
        $this->logDirectory = dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($this->logDirectory)) {

            if (!mkdir($this->logDirectory, 0775, true)) {
                throw new RuntimeException('Kan logmap niet aanmaken.');
            }

        }
    }

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    private function write(string $level, string $message): void
    {
        $bestand = $this->logDirectory . '/'
            . date('Y-m-d')
            . '.log';

        $regel =
            '[' . date('Y-m-d H:i:s') . '] '
            . '[' . strtoupper($level) . '] '
            . $message
            . PHP_EOL;

        file_put_contents(
            $bestand,
            $regel,
            FILE_APPEND | LOCK_EX
        );
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->write('WARNING', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    public function debug(string $message): void
    {
        $this->write('DEBUG', $message);
    }
}