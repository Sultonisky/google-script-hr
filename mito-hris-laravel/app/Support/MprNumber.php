<?php

namespace App\Support;

/**
 * Document id: MPR-2026-001. The sequence restarts each calendar year in Asia/Jakarta.
 * Older MPR-YYYYMMDD-XXXX values are left as-is and do not consume the sequence.
 */
final class MprNumber
{
    public const PATTERN = '/^MPR-(\d{4})-(\d+)$/i';

    public const LEGACY_PATTERN = '/^MPR-\d{8}-\d+$/i';

    public const PLAIN_PATTERN = '/^MPR-(\d+)$/i';

    /**
     * @param  iterable<mixed>  $existing
     */
    public static function next(iterable $existing, ?string $year = null): string
    {
        $year = $year ?? now()->timezone('Asia/Jakarta')->format('Y');
        $max = 0;

        foreach ($existing as $number) {
            $sequence = self::sequenceInYear($number, $year);
            if ($sequence !== null) {
                $max = max($max, $sequence);
            }
        }

        return self::format($year, $max + 1);
    }

    public static function format(string $year, int $sequence): string
    {
        return 'MPR-'.$year.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public static function isCurrent(string $number): bool
    {
        return (bool) preg_match(self::PATTERN, trim($number));
    }

    public static function isLegacy(string $number): bool
    {
        return (bool) preg_match(self::LEGACY_PATTERN, trim($number));
    }

    public static function plainSequence(string $number): ?int
    {
        $number = trim($number);
        if (! preg_match(self::PLAIN_PATTERN, $number, $match)) {
            return null;
        }

        return (int) $match[1];
    }

    public static function sequenceInYear(mixed $number, string $year): ?int
    {
        if (! preg_match(self::PATTERN, trim((string) $number), $match)) {
            return null;
        }

        if ($match[1] !== $year) {
            return null;
        }

        return (int) $match[2];
    }

    public static function yearFromRequestDate(?string $requestDate): ?string
    {
        $date = trim((string) $requestDate);
        if (preg_match('/^(\d{4})-\d{2}-\d{2}/', $date, $match)) {
            return $match[1];
        }

        return null;
    }
}
