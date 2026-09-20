<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class EmployeeIdGenerator
{
    /**
     * Generate YYYYMMDD + sequence (2+ digits), keyed by join date.
     * Falls back to today in Asia/Jakarta when join date is missing or invalid.
     *
     * @param  iterable<int, mixed>  $existingIds
     */
    public function generate(?string $joinDate = null, iterable $existingIds = []): string
    {
        $datePart = $this->datePart($joinDate);
        $key = "EMP_COUNTER_{$datePart}";

        $lock = Cache::lock("lock_{$key}", 10);
        try {
            $lock->block(10);
            $cached = (int) Cache::get($key, 0);
            $fromExisting = $this->maxSequenceForDate($datePart, $existingIds);
            $counter = max($cached, $fromExisting) + 1;
            Cache::put($key, $counter, now()->endOfDay());

            return $datePart . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
        } finally {
            $lock->release();
        }
    }

    public function generateBatch(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $datePart = $this->datePart(null);
        $key = "EMP_COUNTER_{$datePart}";

        $lock = Cache::lock("lock_{$key}", 10);
        try {
            $lock->block(10);
            $start = (int) Cache::get($key, 0) + 1;
            $end = $start + $count - 1;
            Cache::put($key, $end, now()->endOfDay());

            $ids = [];
            for ($i = $start; $i <= $end; $i++) {
                $ids[] = $datePart . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            }

            return $ids;
        } finally {
            $lock->release();
        }
    }

    private function datePart(?string $joinDate): string
    {
        $tz = 'Asia/Jakarta';
        if (is_string($joinDate) && trim($joinDate) !== '') {
            try {
                return Carbon::parse(trim($joinDate), $tz)->format('Ymd');
            } catch (\Throwable) {
                // Fall through to today.
            }
        }

        return now()->timezone($tz)->format('Ymd');
    }

    /**
     * @param  iterable<int, mixed>  $existingIds
     */
    private function maxSequenceForDate(string $datePart, iterable $existingIds): int
    {
        $max = 0;
        $prefixLen = strlen($datePart);

        foreach ($existingIds as $id) {
            $id = strtoupper(trim((string) $id));
            if ($id === '' || !str_starts_with($id, $datePart)) {
                continue;
            }

            $suffix = substr($id, $prefixLen);
            if ($suffix !== '' && ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max;
    }
}
