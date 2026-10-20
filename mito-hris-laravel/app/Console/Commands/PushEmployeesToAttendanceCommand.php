<?php

namespace App\Console\Commands;

use App\DTOs\EmployeeData;
use App\Exceptions\AttendanceEmployeePushException;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\AttendanceEmployeePushService;
use Illuminate\Console\Command;

class PushEmployeesToAttendanceCommand extends Command
{
    protected $signature = 'mito:employees-push-attendance
        {--execute : Tulis perubahan profil ke Attendance; tanpa flag ini hanya preview}';

    protected $description = 'Preview atau sinkronkan profil karyawan HRIS ke Attendance';

    public function handle(
        EmployeeRepositoryInterface $employeeRepo,
        AttendanceEmployeePushService $attendancePush
    ): int {
        $dryRun = ! $this->option('execute');
        $employees = $employeeRepo->getAll()
            ->filter(fn (EmployeeData $employee) => filled($employee->employeeId) && filled($employee->fullName))
            ->values();

        try {
            $result = $attendancePush->pushEmployees($employees, $dryRun);
        } catch (AttendanceEmployeePushException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($result['data'] as $record) {
            if (! in_array($record['status'], ['skipped'], true)) {
                $this->line(strtoupper($record['status']).': '.$record['employee_id']);
            }
        }

        $meta = $result['meta'];
        $this->table(
            ['Diproses', 'Dibuat', 'Diperbarui', 'Dilewati', 'Konflik', 'Akan dibuat', 'Akan diperbarui'],
            [[
                $meta['processed'],
                $meta['created'],
                $meta['updated'],
                $meta['skipped'],
                $meta['conflict'],
                $meta['would_create'],
                $meta['would_update'],
            ]],
        );
        $this->info($dryRun
            ? 'Preview saja; Attendance tidak diubah. Tambahkan --execute untuk menulis perubahan.'
            : 'Sinkronisasi profil karyawan ke Attendance selesai.');

        return self::SUCCESS;
    }
}
