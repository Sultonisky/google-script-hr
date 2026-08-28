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
                recruitmentId: $event->candidate->recruitmentId ?? 'NEW',
                action: 'APPLY',
                field: 'Status',
                oldValue: '',
                newValue: 'Pending',
                user: 'Public Portal'
            );
        } elseif ($event instanceof CandidateStatusChanged) {
            $this->auditRepo->log(
                recruitmentId: $event->recruitmentId,
                action: 'UPDATE_STATUS',
                field: 'Status',
                oldValue: $event->oldStatus,
                newValue: $event->newStatus,
                user: $event->user ?? 'HR Administrator'
            );
        } elseif ($event instanceof EmployeeHired) {
            $this->auditRepo->log(
                recruitmentId: $event->recruitmentId ?? ($event->employee->employeeId ?? ''),
                action: 'HIRED_TO_EMPLOYEE',
                field: 'Status Employee',
                oldValue: 'Candidate',
                newValue: $event->employee->statusEmployee ?? 'PKWT',
                user: $event->user ?? 'HR Administrator'
            );
        }
    }
}
