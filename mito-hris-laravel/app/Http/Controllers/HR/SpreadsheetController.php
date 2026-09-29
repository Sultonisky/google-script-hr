<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Gated entry point to the Google Sheets archive so the spreadsheet ID is
 * never rendered for users without the open_spreadsheet permission.
 */
class SpreadsheetController extends Controller
{
    public function __construct(
        protected AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function open(): RedirectResponse
    {
        $spreadsheetId = trim((string) config('google.spreadsheet_id', ''));
        if ($spreadsheetId === '') {
            abort(404);
        }

        $actor = (string) (session('hr_user.email') ?? 'System');

        try {
            $this->auditLogs->log(
                entityType: 'System',
                entityId: 'SPREADSHEET-OPEN-'.now()->format('YmdHis'),
                action: 'Opened',
                field: 'Spreadsheet',
                oldValue: '-',
                newValue: 'Membuka Spreadsheet arsip',
                user: $actor,
                source: 'Dashboard'
            );
        } catch (\Throwable $e) {
            Log::warning('SpreadsheetController::open audit log failed: '.$e->getMessage(), [
                'user' => $actor,
            ]);
        }

        return redirect()->away('https://docs.google.com/spreadsheets/d/'.rawurlencode($spreadsheetId).'/edit');
    }
}
