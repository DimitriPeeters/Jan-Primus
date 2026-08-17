<?php

declare(strict_types=1);

namespace AEFS\Core;

use DateTimeImmutable;
use RuntimeException;

final class Logger
{
    private string $logDirectory;

    public function __construct(
        string $logDirectory
    ) {
        $this->logDirectory = rtrim($logDirectory, DIRECTORY_SEPARATOR);

        if (!is_dir($this->logDirectory) && !mkdir($concurrentDirectory = $this->logDirectory, 0755, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf(
                'Unable to create log directory [%s].',
                $this->logDirectory
            ));
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->write('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->write('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->write('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->write('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->write('DEBUG', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->write(strtoupper($level), $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $date = new DateTimeImmutable();

        $file = sprintf(
            '%s%s%s.log',
            $this->logDirectory,
            DIRECTORY_SEPARATOR,
            $date->format('Y-m-d')
        );

        $line = sprintf(
            "[%s] %-9s %s%s",
            $date->format('Y-m-d H:i:s'),
            $level,
            $this->interpolate($message, $context),
            PHP_EOL
        );

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $replace['{' . $key . '}'] = (string) $value;
            } else {
                $replace['{' . $key . '}'] = json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) ?: '[unserializable]';
            }
        }

        return strtr($message, $replace);
    }
}