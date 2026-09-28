<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\MprRequestor;
use App\Models\User;
use App\Services\Google\GoogleDriveService;
use App\Services\Google\GoogleSheetsService;
use App\Support\HrisDataDriver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DiagnoseCommand extends Command
{
    protected $signature = 'mito:diagnose';

    protected $description = 'Diagnostik SoT (DB), mirror Sheets/Drive opsional, dan komponen sistem';

    public function handle(GoogleSheetsService $sheets, GoogleDriveService $drive): int
    {
        $this->info('===========================================================');
        $this->info('  MITO HRIS — Deep Diagnostics');
        $this->info('===========================================================');

        $driver = HrisDataDriver::current();
        $sheetsOptional = $driver === HrisDataDriver::PGSQL;

        $checks = [
            'HRIS data driver' => fn () => 'OK ('.$driver.')',
            'Application' => fn () => app()->version() ? 'OK' : 'FAIL',
            'Environment' => fn () => config('app.env') ? 'OK' : 'FAIL',
            'Database Connection' => function () {
                try {
                    DB::connection()->getPdo();
                    $extra = '';
                    if (Schema::hasTable('employees')) {
                        $extra = ' emp='.Employee::query()->count()
                            .' users='.User::query()->count()
                            .' req='.MprRequestor::query()->count();
                    }

                    return 'OK ('.config('database.default').$extra.')';
                } catch (\Exception $e) {
                    return 'FAIL: '.$e->getMessage();
                }
            },
            'Google Sheets (mirror)' => function () use ($sheets, $sheetsOptional) {
                try {
                    $data = $sheets->getRange('Employee', 'A1:Z1', false);
                    if (isset($data[0])) {
                        return 'OK'.($sheetsOptional ? ' (archive accessible)' : '');
                    }

                    return $sheetsOptional
                        ? 'WARN: empty response (mirror optional)'
                        : 'FAIL: empty response';
                } catch (\Exception $e) {
                    return ($sheetsOptional ? 'WARN' : 'FAIL').': '.$e->getMessage();
                }
            },
            'Google Drive Connection' => function () use ($drive) {
                try {
                    $service = $drive->getDriveService();
                    $about = $service->about->get(['fields' => 'user']);

                    return 'OK (user: '.($about->getUser()->getEmailAddress() ?? 'unknown').')';
                } catch (\Exception $e) {
                    return 'WARN: '.$e->getMessage();
                }
            },
            'Storage Permissions' => fn () => Storage::disk('local')->exists('') ? 'OK' : 'FAIL',
            'PDF Engine' => function () {
                if (! class_exists(Pdf::class)) {
                    return 'FAIL (DOMPDF not installed)';
                }

                $fontDir = (string) config('dompdf.options.font_dir');
                $tempDir = (string) config('dompdf.options.temp_dir');
                $problems = [];

                foreach (['font directory' => $fontDir, 'temp directory' => $tempDir] as $label => $path) {
                    if ($path === '' || ! is_dir($path)) {
                        $problems[] = "{$label} missing: {$path}";
                    } elseif (! is_writable($path)) {
                        $problems[] = "{$label} not writable: {$path}";
                    }
                }

                if (! extension_loaded('dom')) {
                    $problems[] = 'PHP ext-dom missing';
                }

                if ($problems) {
                    return 'FAIL: '.implode('; ', $problems);
                }

                try {
                    $output = Pdf::loadHTML('<html><body>MITO HRIS PDF diagnostic</body></html>')->output();

                    return str_starts_with($output, '%PDF-')
                        ? 'OK (render test passed)'
                        : 'FAIL (render output is not a PDF)';
                } catch (\Throwable $e) {
                    return 'FAIL (render): '.$e->getMessage();
                }
            },
            'RBAC' => function () {
                $roles = config('hris.auth.valid_roles_internal', []);
                $requestorRoles = config('hris.auth.valid_roles_requestor', []);

                return ! empty($roles)
                    ? 'OK ('.count($roles).' internal roles, '.count($requestorRoles).' requestor roles)'
                    : 'FAIL (no roles defined)';
            },
            'Vite build assets' => function () {
                $manifest = public_path('build/manifest.json');
                if (! is_file($manifest)) {
                    return 'WARN: public/build/manifest.json missing (run npm run build)';
                }

                $entries = json_decode((string) file_get_contents($manifest), true);
                if (! is_array($entries) || $entries === []) {
                    return 'WARN: Vite manifest is empty or invalid';
                }

                $missing = [];
                foreach ($entries as $entry) {
                    $file = is_array($entry) ? ($entry['file'] ?? null) : null;
                    if (! is_string($file) || $file === '') {
                        continue;
                    }

                    $absolute = public_path('build/'.ltrim($file, '/'));
                    if (! is_file($absolute)) {
                        $missing[] = $file;
                    }

                    if (count($missing) >= 3) {
                        break;
                    }
                }

                if ($missing !== []) {
                    return 'WARN: missing built files: '.implode(', ', $missing);
                }

                return 'OK ('.count($entries).' manifest entries)';
            },
            'MPR Requestor store' => function () use ($sheets, $driver) {
                if ($driver === HrisDataDriver::PGSQL) {
                    try {
                        return 'OK (DB rows='.MprRequestor::query()->count().')';
                    } catch (\Throwable $e) {
                        return 'FAIL: '.$e->getMessage();
                    }
                }

                try {
                    $sheetName = config('google.sheets.mpr_requestor', 'mpr_requestor');
                    $data = $sheets->getRange($sheetName, 'A1:M1', false);

                    return isset($data[0]) ? 'OK (sheet accessible)' : 'WARN: sheet empty/missing headers';
                } catch (\Exception $e) {
                    return 'WARN: '.$e->getMessage().' (backup: mito:setup-sheets --fix --force)';
                }
            },
        ];

        $anyFailed = false;
        foreach ($checks as $name => $check) {
            $result = $check();
            $ok = str_starts_with($result, 'OK');
            $warn = str_starts_with($result, 'WARN');
            $symbol = $ok ? '✓' : ($warn ? '!' : '✗');
            $this->line("  [{$symbol}] {$name}: {$result}");
            if (! $ok && ! $warn) {
                $anyFailed = true;
            }
        }

        $this->info('===========================================================');

        return $anyFailed ? Command::FAILURE : Command::SUCCESS;
    }
}
