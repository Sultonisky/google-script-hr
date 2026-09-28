<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Outsource ID = {prefix}{YYYY}{NNNN} (e.g. DM20260001): next number after the
 * highest stored ID for prefix+year, so numbering always follows the actual data.
 */
class OutsourceIdGenerator
{
    private const LOCK_KEY = 'lock_OUTSOURCE_ID_SEQUENCE';

    /**
     * @param  iterable<int, mixed>  $existingIds
     */
    public function generate(iterable $existingIds = [], ?Carbon $now = null): string
    {
        $base = $this->base($now);

        return $base . str_pad((string) ($this->maxSequence($base, $existingIds) + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Reads the stored IDs, picks the next one and persists it under a single lock, so
     * concurrent creates (public apply form + HR dashboard) never share or skip a number.
     *
     * @template T
     * @param  callable(): iterable<int, mixed>  $existingIds
     * @param  callable(string): T  $persist
     * @return T
     */
    public function allocate(callable $existingIds, callable $persist, ?Carbon $now = null): mixed
    {
        return Cache::lock(self::LOCK_KEY, 30)->block(15, fn () => $persist($this->generate($existingIds(), $now)));
    }

    private function base(?Carbon $now): string
    {
        $prefix = strtoupper(trim((string) config('hris.outsource.id_prefix', 'DM'))) ?: 'DM';
        $year = ($now ?? now())->timezone('Asia/Jakarta')->format('Y');

        return $prefix . $year;
    }

    /**
     * @param  iterable<int, mixed>  $existingIds
     */
    private function maxSequence(string $base, iterable $existingIds): int
    {
        $max = 0;
        foreach ($existingIds as $id) {
            $id = strtoupper(trim((string) $id));
            if (!str_starts_with($id, $base)) {
                continue;
            }
            $suffix = substr($id, strlen($base));
            if ($suffix !== '' && ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max;
    }
}
