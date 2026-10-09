<?php

namespace App\Console\Commands;

use App\DTOs\OutsourceEmployeeData;
use App\Exceptions\AttendanceOutsourcePushException;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\AttendanceOutsourcePushService;
use Illuminate\Console\Command;

/**
 * Retry/backfill for the HRIS -> Attendance Person List push. Attendance only
 * creates missing IDs (inactive, default PIN) and never changes existing ones.
 *
 * Usage:
 *   php artisan mito:outsource-push-attendance
 *   php artisan mito:outsource-push-attendance --execute
 */
class PushOutsourceToAttendanceCommand extends Command
{
    protected $signature = 'mito:outsource-push-attendance
        {--execute : Buat ID yang belum ada di Attendance; tanpa flag ini hanya preview}';

    protected $description = 'Kirim Outsource ID dan nama dari HRIS ke Person List Attendance (hanya ID yang belum ada)';

    public function handle(OutsourceEmployeeRepositoryInterface $outsourceRepo, AttendanceOutsourcePushService $attendancePush): int
    {
        $dryRun = ! $this->option('execute');
        $totals = ['processed' => 0, 'would_create' => 0, 'created' => 0, 'skipped' => 0, 'conflict' => 0];

        $people = $outsourceRepo->getAll()
            ->filter(fn (OutsourceEmployeeData $person) => filled($person->outsourceId) && filled($person->fullName))
            ->map(fn (OutsourceEmployeeData $person) => [
                'outsource_id' => strtoupper(trim((string) $person->outsourceId)),
                'full_name' => trim((string) $person->fullName),
            ])
            ->values()
            ->all();

        try {
            foreach (array_chunk($people, 100) as $batch) {
                $result = $attendancePush->push($batch, $dryRun);
                foreach ($totals as $key => $value) {
                    $totals[$key] += $result['meta'][$key];
                }
                foreach ($result['data'] as $record) {
                    if ($record['status'] !== 'skipped') {
                        $this->line(strtoupper($record['status']) . ': ' . $record['outsource_id']);
                    }
                }
            }
        } catch (AttendanceOutsourcePushException $e) {
            $this->printTotals($totals);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->printTotals($totals);
        $this->info($dryRun
            ? 'Preview saja; tidak ada data ditulis di Attendance. Tambahkan --execute untuk membuat ID yang belum ada.'
            : 'Push ke Attendance selesai.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $totals
     */
    private function printTotals(array $totals): void
    {
        $this->table(
            ['Diproses', 'Akan dibuat', 'Dibuat', 'Sudah ada', 'Konflik'],
            [[$totals['processed'], $totals['would_create'], $totals['created'], $totals['skipped'], $totals['conflict']]],
        );
    }
}
