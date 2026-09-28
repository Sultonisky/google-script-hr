<?php

namespace App\Support;

/**
 * Resolves the structured-data source of truth without mutating Sheets.
 *
 * Explicit HRIS_DATA_DRIVER wins; otherwise GOOGLE_SHEETS_ENABLED maps to
 * sheets (true) or local (false). PostgreSQL cutover is opt-in via "pgsql".
 */
final class HrisDataDriver
{
    public const SHEETS = 'sheets';
    public const PGSQL = 'pgsql';
    public const LOCAL = 'local';

    public static function current(): string
    {
        $configured = strtolower(trim((string) config('hris.data_driver', '')));

        if (in_array($configured, [self::SHEETS, self::PGSQL, self::LOCAL], true)) {
            return $configured;
        }

        return config('google.enabled', false) ? self::SHEETS : self::LOCAL;
    }

    public static function usesSheets(): bool
    {
        return self::current() === self::SHEETS;
    }

    public static function usesPgsql(): bool
    {
        return self::current() === self::PGSQL;
    }

    public static function usesLocal(): bool
    {
        return self::current() === self::LOCAL;
    }
}
