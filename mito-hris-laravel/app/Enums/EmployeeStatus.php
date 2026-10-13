<?php

namespace App\Enums;

/**
 * Jenis kepegawaian resmi pada kolom Employee "Status Employee".
 *
 * Kolom yang sama juga menyimpan status akhir offboarding (TERMINAL_STATUSES).
 * Nilai lama (PKWT, PKWTT, Probation, dst.) tetap dikenali lewat fromValue()
 * agar data yang belum dinormalisasi tetap diperlakukan dengan benar.
 */
enum EmployeeStatus: string
{
    case PERMANENT = 'Permanent';
    case CONTRACT  = 'Contract';
    case OUTSOURCE = 'Outsource';

    public const TERMINAL_STATUSES = ['Resigned', 'Terminated', 'Retired', 'Deceased', 'Inactive', 'Contract Finished'];

    private const ALIASES = [
        'permanent'         => self::PERMANENT,
        'pkwtt'             => self::PERMANENT,
        'permanent (pkwtt)' => self::PERMANENT,
        'tetap'             => self::PERMANENT,
        'karyawan tetap'    => self::PERMANENT,
        'contract'          => self::CONTRACT,
        'pkwt'              => self::CONTRACT,
        'contract (pkwt)'   => self::CONTRACT,
        'kontrak'           => self::CONTRACT,
        'probation'         => self::CONTRACT,
        'outsource'         => self::OUTSOURCE,
    ];

    public static function fromValue(?string $value): ?self
    {
        return self::ALIASES[strtolower(trim((string) $value))] ?? null;
    }

    /**
     * @param  self|list<self>  $statuses
     */
    public static function matches(?string $value, self|array $statuses): bool
    {
        return in_array(self::fromValue($value), is_array($statuses) ? $statuses : [$statuses], true);
    }

    public static function matchesFilter(?string $value, string $filter): bool
    {
        $status = self::fromValue($filter);

        return $status
            ? self::matches($value, $status)
            : strtolower(trim((string) $value)) === strtolower(trim($filter));
    }

    /** @return list<string> Lowercase values equivalent to a filter value. */
    public static function equivalentValues(string $value): array
    {
        $status = self::fromValue($value);
        if (! $status) {
            return [strtolower(trim($value))];
        }

        $aliases = array_keys(array_filter(
            self::ALIASES,
            fn (self $alias): bool => $alias === $status
        ));

        return array_values(array_unique([...$aliases, strtolower($status->value)]));
    }

    /**
     * Canonical stored value: alias → jenis resmi, status terminal → ejaan resmi,
     * selain itu dikembalikan apa adanya (trimmed) untuk ditinjau manual.
     */
    public static function canonical(?string $value): string
    {
        $value = trim((string) $value);
        if ($type = self::fromValue($value)) {
            return $type->value;
        }
        foreach (self::TERMINAL_STATUSES as $terminal) {
            if (strcasecmp($terminal, $value) === 0) {
                return $terminal;
            }
        }

        return $value;
    }

    public static function isTerminal(?string $value): bool
    {
        return in_array(self::canonical($value), self::TERMINAL_STATUSES, true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
