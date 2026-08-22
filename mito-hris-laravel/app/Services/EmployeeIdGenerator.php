<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmployeeIdGenerator
{
    public function generate(): string
    {
        $datePart = now()->timezone('Asia/Jakarta')->format('Ymd');
        $key = "EMP_COUNTER_{$datePart}";

        $lock = Cache::lock("lock_{$key}", 10);
        try {
            $lock->block(10);
            $counter = (int) Cache::get($key, 0) + 1;
            Cache::put($key, $counter, now()->endOfDay());
            return $datePart . str_pad($counter, 2, '0', STR_PAD_LEFT);
        } finally {
            $lock->release();
        }
    }

    public function generateBatch(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $datePart = now()->timezone('Asia/Jakarta')->format('Ymd');
        $key = "EMP_COUNTER_{$datePart}";

        $lock = Cache::lock("lock_{$key}", 10);
        try {
            $lock->block(10);
            $start = (int) Cache::get($key, 0) + 1;
            $end = $start + $count - 1;
            Cache::put($key, $end, now()->endOfDay());

            $ids = [];
            for ($i = $start; $i <= $end; $i++) {
                $ids[] = $datePart . str_pad($i, 2, '0', STR_PAD_LEFT);
            }
            return $ids;
        } finally {
            $lock->release();
        }
    }
}