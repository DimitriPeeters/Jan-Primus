<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;

final class BelgianDateTime
{
    public const DATE_FORMAT = 'd/m/Y';
    public const TIME_FORMAT = 'H:i';
    public const DATE_TIME_FORMAT = 'd/m/Y H:i';

    public static function formatDate(
        DateTimeInterface|string|null $value,
        string $fallback = '—'
    ): string {
        return self::format(
            $value,
            self::DATE_FORMAT,
            $fallback
        );
    }

    public static function formatTime(
        DateTimeInterface|string|null $value,
        string $fallback = '—'
    ): string {
        return self::format(
            $value,
            self::TIME_FORMAT,
            $fallback
        );
    }

    public static function formatDateTime(
        DateTimeInterface|string|null $value,
        string $fallback = '—'
    ): string {
        return self::format(
            $value,
            self::DATE_TIME_FORMAT,
            $fallback
        );
    }

    public static function normalizeDateInput(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        foreach (
            [
                'd/m/Y',
                'Y-m-d',
            ] as $format
        ) {
            $date = self::createExact($format, $value);

            if ($date instanceof DateTimeImmutable) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }

    private static function format(
        DateTimeInterface|string|null $value,
        string $format,
        string $fallback
    ): string {
        $date = self::parse($value);

        return $date instanceof DateTimeImmutable
            ? $date->format($format)
            : $fallback;
    }

    private static function parse(
        DateTimeInterface|string|null $value
    ): ?DateTimeImmutable {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (
            [
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'Y-m-d',
                'd/m/Y H:i:s',
                'd/m/Y H:i',
                'd/m/Y',
            ] as $format
        ) {
            $date = self::createExact($format, $value);

            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        return null;
    }

    private static function createExact(
        string $format,
        string $value
    ): ?DateTimeImmutable {
        $date = DateTimeImmutable::createFromFormat(
            '!' . $format,
            $value
        );

        $errors = DateTimeImmutable::getLastErrors();

        if (
            !$date instanceof DateTimeImmutable
            || (
                is_array($errors)
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
            || $date->format($format) !== $value
        ) {
            return null;
        }

        return $date;
    }
}
