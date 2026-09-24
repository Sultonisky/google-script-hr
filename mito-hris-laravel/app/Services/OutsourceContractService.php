<?php

namespace App\Services;

use App\DTOs\EmployeeData;
use App\Enums\SkDocumentType;
use Illuminate\Support\Carbon;

class OutsourceContractService
{
    public function __construct(
        private SkNumberService $skNumbers
    ) {}

    /**
     * Allocate PKWT TAD contract number — same fixed-seq family as other SK docs.
     * Format: {seq}/PKTAD/{ENTITY}/{ROMAN}/{YEAR}
     *
     * @return array{seq:int, contract_number:string, roman_month:string, year:int, generated_at:string}
     */
    public function allocateContractNumber(EmployeeData $employee, ?Carbon $now = null, ?string $issuedBy = null): array
    {
        $now = $now ?? now()->timezone('Asia/Jakarta');
        $employeeId = trim((string) ($employee->employeeId ?? ''));
        if ($employeeId === '') {
            throw new \InvalidArgumentException('Employee ID kosong.');
        }

        $issued = $this->skNumbers->issue(
            employeeId: $employeeId,
            type: SkDocumentType::PKTAD,
            branchName: (string) ($employee->branchName ?? ''),
            issuedBy: $issuedBy ?: 'HR Administrator',
            reference: 'Outsource PKWT TAD',
            notes: 'Kontrak PKWT TAD',
            issuedAt: $now,
        );

        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$now->month - 1];

        return [
            'seq'             => $issued['sequence'],
            'contract_number' => $issued['nomor'],
            'roman_month'     => $roman,
            'year'            => $now->year,
            'generated_at'    => $now->format('Y-m-d H:i:s'),
        ];
    }

    public function isOutsource(EmployeeData $employee): bool
    {
        return strtolower(trim((string) ($employee->statusEmployee ?? ''))) === 'outsource';
    }
}
