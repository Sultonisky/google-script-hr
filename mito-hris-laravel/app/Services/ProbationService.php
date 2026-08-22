<?php

namespace App\Services;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use RuntimeException;

class ProbationService
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        AuditLogRepositoryInterface $auditRepo
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->auditRepo = $auditRepo;
    }

    /**
     * Evaluate probation employee (1:1 with backend/Probation.gs).
     * Decision: 'Passed' (Lulus Tetap/PKWTT), 'Extended' (Perpanjang), 'Failed' (Tidak Lolos/Terminated)
     */
    public function evaluateProbation(string $employeeId, array $evalData, ?string $user = null): bool
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan probation {$employeeId} tidak ditemukan.");
        }

        $user = $user ?: 'HR Administrator';
        $decision = $evalData['decision'] ?? 'Passed';
        $score = $evalData['score'] ?? 0;
        $notes = $evalData['notes'] ?? '';

        $newStatus = match($decision) {
            'Passed'   => 'PKWTT',
            'Extended' => 'Probation Extended',
            'Failed'   => 'Terminated',
            default    => 'Probation',
        };

        $attributes = [
            'Status Employee' => $newStatus,
            'HR Notes'        => "Evaluasi Probation ({$decision}, Skor: {$score}): {$notes}",
            'Updated At'      => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];

        $success = $this->employeeRepo->update($employeeId, $attributes);

        if ($success) {
            $this->auditRepo->log(
                recruitmentId: $employeeId,
                action: 'PROBATION_EVAL_' . strtoupper($decision),
                field: 'Status Employee',
                oldValue: $employee->statusEmployee,
                newValue: "{$newStatus} (Score: {$score})",
                user: $user
            );
        }

        return $success;
    }
}
