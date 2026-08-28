<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(AuditLogRepositoryInterface $auditRepo)
    {
        $this->auditRepo = $auditRepo;
    }

    public function index(Request $request): View
    {
        $allLogs = $this->auditRepo->getLogs();

        $actionFilter = $request->query('action');
        $entityTypeFilter = $request->query('entity_type');
        $sourceFilter = $request->query('source');
        $userFilter = strtolower(trim($request->query('user', '')));
        $entityIdFilter = strtolower(trim($request->query('entity_id', '')));
        $searchFilter = strtolower(trim($request->query('search', '')));

        $logs = $allLogs;

        if (!empty($actionFilter)) {
            $logs = $logs->filter(fn($l) => strtolower($l['Action'] ?? '') === strtolower($actionFilter));
        }

        if (!empty($entityTypeFilter)) {
            $logs = $logs->filter(fn($l) => strtolower($l['Entity Type'] ?? 'Candidate') === strtolower($entityTypeFilter));
        }

        if (!empty($sourceFilter)) {
            $logs = $logs->filter(fn($l) => strtolower($l['Source'] ?? 'Legacy') === strtolower($sourceFilter));
        }

        if (!empty($userFilter)) {
            $logs = $logs->filter(fn($l) => str_contains(strtolower($l['User'] ?? ''), $userFilter));
        }

        if (!empty($entityIdFilter)) {
            $logs = $logs->filter(fn($l) => str_contains(strtolower($l['Entity ID'] ?? $l['Recruitment ID'] ?? ''), $entityIdFilter));
        }

        if (!empty($searchFilter)) {
            $logs = $logs->filter(function($l) use ($searchFilter) {
                return str_contains(strtolower($l['Entity ID'] ?? $l['Recruitment ID'] ?? ''), $searchFilter)
                    || str_contains(strtolower($l['User'] ?? ''), $searchFilter)
                    || str_contains(strtolower($l['Action'] ?? ''), $searchFilter)
                    || str_contains(strtolower($l['Entity Type'] ?? ''), $searchFilter);
            });
        }

        $total = $logs->count();
        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
        $currentPage = max(1, (int) $request->query('page', 1));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min($currentPage, $lastPage);
        $paginatedLogs = $logs->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $stats = [
            'created' => $allLogs->filter(fn($l) => in_array(strtolower($l['Action'] ?? ''), ['created', 'create', 'apply']))->count(),
            'update'  => $allLogs->filter(fn($l) => in_array(strtolower($l['Action'] ?? ''), ['updated', 'update', 'update_status', 'status_changed']))->count(),
            'hold_bl' => $allLogs->filter(fn($l) => in_array(strtoupper($l['Action'] ?? ''), ['HOLD', 'BLACKLIST']))->count(),
        ];

        return view('hr.audit-logs.index', compact(
            'paginatedLogs',
            'total',
            'currentPage',
            'lastPage',
            'perPage',
            'stats'
        ));
    }
}
