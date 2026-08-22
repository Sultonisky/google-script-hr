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
        $searchFilter = strtolower(trim($request->query('search', '')));

        $logs = $allLogs;

        if (!empty($actionFilter)) {
            $logs = $logs->filter(fn($l) => strtolower($l['Action'] ?? '') === strtolower($actionFilter));
        }

        if (!empty($searchFilter)) {
            $logs = $logs->filter(function($l) use ($searchFilter) {
                return str_contains(strtolower($l['Recruitment ID'] ?? ''), $searchFilter)
                    || str_contains(strtolower($l['User'] ?? ''), $searchFilter)
                    || str_contains(strtolower($l['Action'] ?? ''), $searchFilter);
            });
        }

        $stats = [
            'created' => $allLogs->filter(fn($l) => in_array(strtoupper($l['Action'] ?? ''), ['CREATE', 'APPLY']))->count(),
            'update'  => $allLogs->filter(fn($l) => str_contains(strtoupper($l['Action'] ?? ''), 'UPDATE'))->count(),
            'hold_bl' => $allLogs->filter(fn($l) => in_array(strtoupper($l['Action'] ?? ''), ['HOLD', 'BLACKLIST']))->count(),
        ];

        return view('hr.audit-logs.index', compact('logs', 'stats'));
    }
}
