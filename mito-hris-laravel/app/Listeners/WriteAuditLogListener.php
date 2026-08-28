<?php

namespace App\Listeners;

use App\Events\CandidateApplied;
use App\Events\CandidateStatusChanged;
use App\Events\EmployeeHired;
use App\Repositories\Contracts\AuditLogRepositoryInterface;

class WriteAuditLogListener
{
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(AuditLogRepositoryInterface $auditRepo)
    {
        $this->auditRepo = $auditRepo;
    }

    public function handle(object $event): void
    {
        if ($event instanceof CandidateApplied) {
            $this->auditRepo->log(
                entityType: 'Candidate',
                entityId: $event->candidate->recruitmentId ?? 'NEW',
                action: 'created',
                field: 'Status',
                oldValue: '',
                newValue: 'Pending',
                user: 'Public Applicant',
                source: 'Public'
            );
        } elseif ($event instanceof CandidateStatusChanged) {
            $this->auditRepo->log(
                entityType: 'Candidate',
                entityId: $event->recruitmentId,
                action: 'status_changed',
                field: 'Status',
                oldValue: $event->oldStatus,
                newValue: $event->newStatus,
                user: $event->user ?? 'HR Administrator',
                source: 'Dashboard'
            );
        } elseif ($event instanceof EmployeeHired) {
            $this->auditRepo->log(
                entityType: 'Employee',
                entityId: $event->employee->employeeId ?? '',
                action: 'created',
                field: 'Status Employee',
                oldValue: 'Candidate',
                newValue: $event->employee->statusEmployee ?? 'PKWT',
                user: $event->user ?? 'HR Administrator',
                source: 'Dashboard'
            );
        }
    }
}
