<?php

namespace App\Console\Commands;

use App\Services\Google\GoogleSheetsService;
use App\Services\Google\GoogleDriveService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DiagnoseCommand extends Command
{
    protected $signature = 'mito:diagnose';
    protected $description = 'Run deep diagnostics on all system components';

    public function handle(GoogleSheetsService $sheets, GoogleDriveService $drive): int
    {
        $this->info('===========================================================');
        $this->info('  MITO HRIS — Deep Diagnostics');
        $this->info('===========================================================');

        $checks = [
            'Application' => function () {
                return app()->version() ? 'OK' : 'FAIL';
            },
            'Environment' => function () {
                return config('app.env') ? 'OK' : 'FAIL';
            },
            'Database Connection' => function () {
                try {
                    DB::connection()->getPdo();
                    return 'OK';
                } catch (\Exception $e) {
                    return 'FAIL: ' . $e->getMessage();
                }
            },
            'Google Sheets Connection' => function () use ($sheets) {
                try {
                    $data = $sheets->getRange('Employee', 'A1:Z1', false);
                    return isset($data[0]) ? 'OK' : 'FAIL: empty response';
                } catch (\Exception $e) {
                    return 'FAIL: ' . $e->getMessage();
                }
            },
            'Google Drive Connection' => function () use ($drive) {
                try {
                    $service = $drive->getDriveService();
                    $about = $service->about->get(['fields' => 'user']);
                    return 'OK (user: ' . ($about->getUser()->getEmailAddress() ?? 'unknown') . ')';
                } catch (\Exception $e) {
                    return 'FAIL: ' . $e->getMessage();
                }
            },
            'Storage Permissions' => function () {
                return Storage::disk('local')->exists('') ? 'OK' : 'FAIL';
            },
            'PDF Engine' => function () {
                if (!class_exists(Pdf::class)) {
                    return 'FAIL (DOMPDF not installed)';
                }

                $fontDir = (string) config('dompdf.options.font_dir');
                $tempDir = (string) config('dompdf.options.temp_dir');
                $problems = [];

                foreach (['font directory' => $fontDir, 'temp directory' => $tempDir] as $label => $path) {
                    if ($path === '' || !is_dir($path)) {
                        $problems[] = "{$label} missing: {$path}";
                    } elseif (!is_writable($path)) {
                        $problems[] = "{$label} not writable: {$path}";
                    }
                }

                if (!extension_loaded('dom')) {
                    $problems[] = 'PHP ext-dom missing';
                }

                if ($problems) {
                    return 'FAIL: ' . implode('; ', $problems);
                }

                try {
                    $output = Pdf::loadHTML('<html><body>MITO HRIS PDF diagnostic</body></html>')->output();
                    return str_starts_with($output, '%PDF-')
                        ? 'OK (render test passed)'
                        : 'FAIL (render output is not a PDF)';
                } catch (\Throwable $e) {
                    return 'FAIL (render): ' . $e->getMessage();
                }
            },
            'RBAC' => function () {
                $roles = config('hris.auth.valid_roles_internal', []);
                $requestorRoles = config('hris.auth.valid_roles_requestor', []);
                return !empty($roles)
                    ? 'OK (' . count($roles) . ' internal roles, ' . count($requestorRoles) . ' requestor roles)'
                    : 'FAIL (no roles defined)';
            },
            'MPR Requestor Sheet' => function () use ($sheets) {
                try {
                    $sheetName = config('google.sheets.mpr_requestor', 'mpr_requestor');
                    $data = $sheets->getRange($sheetName, 'A1:M1', false);
                    return isset($data[0]) ? 'OK (sheet accessible)' : 'WARN: sheet empty/missing headers';
                } catch (\Exception $e) {
                    return 'WARN: ' . $e->getMessage() . ' (run mito:setup-sheets --fix)';
                }
            },
        ];

        $anyFailed = false;
        foreach ($checks as $name => $check) {
            $result = $check();
            $ok     = str_starts_with($result, 'OK');
            $symbol = $ok ? '✓' : '✗';
            $this->line("  [{$symbol}] {$name}: {$result}");
            if (!$ok) $anyFailed = true;
        }

        $this->info('===========================================================');
        return $anyFailed ? Command::FAILURE : Command::SUCCESS;
    }
}
