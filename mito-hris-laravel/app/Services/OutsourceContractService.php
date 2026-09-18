<?php

namespace App\Services;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class OutsourceContractService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepo
    ) {}

    /**
     * Allocate next PKWT TAD contract number for an outsource employee.
     * Format: 001/DM-PKWT/TAD/XI/2026
     *
     * Sequence is per employeeId (1st contract → 001, 2nd → 002, …).
     * Roman month + year come from generation time ($now).
     */
    public function allocateContractNumber(EmployeeData $employee, ?Carbon $now = null): array
    {
        $now = $now ?? now()->timezone('Asia/Jakarta');
        $employeeId = trim((string) ($employee->employeeId ?? ''));
        if ($employeeId === '') {
            throw new \InvalidArgumentException('Employee ID kosong.');
        }

        $cacheKey = 'OUTSOURCE_PKWT_TAD_SEQ_' . $employeeId;
        $lock = Cache::lock('lock_' . $cacheKey, 15);

        try {
            $lock->block(10);

            $fromSheet = (int) ($employee->outsourceContractSeq ?? 0);
            $fromCache = (int) Cache::get($cacheKey, 0);
            $next = max($fromSheet, $fromCache) + 1;

            Cache::forever($cacheKey, $next);

            // Persist to sheet when column exists; ignore failure if header belum ada.
            try {
                $this->employeeRepo->update($employeeId, [
                    'Outsource Contract Seq' => (string) $next,
                ]);
            } catch (\Throwable) {
                // Sequence tetap aman di cache.
            }

            $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$now->month - 1];
            $contractNumber = sprintf('%03d/DM-PKWT/TAD/%s/%d', $next, $roman, $now->year);

            return [
                'seq'             => $next,
                'contract_number' => $contractNumber,
                'roman_month'     => $roman,
                'year'            => $now->year,
                'generated_at'    => $now->format('Y-m-d H:i:s'),
            ];
        } finally {
            optional($lock)->release();
        }
    }

    public function isOutsource(EmployeeData $employee): bool
    {
        return strtolower(trim((string) ($employee->statusEmployee ?? ''))) === 'outsource';
    }
}
