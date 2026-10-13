<?php

namespace App\Console\Commands;

use App\Enums\EmployeeStatus;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Console\Command;

/**
 * Seragamkan Employee "Status Employee" ke Permanent / Contract / Outsource.
 * PKWTT → Permanent, PKWT/Probation → Contract; status offboarding dibiarkan.
 * Nilai yang tidak dikenali (mis. Kemitraan) hanya dilaporkan untuk ditinjau manual.
 *
 * Usage:
 *   php artisan mito:normalize-employee-status
 *   php artisan mito:normalize-employee-status --execute
 */
class NormalizeEmployeeStatusCommand extends Command
{
    protected $signature = 'mito:normalize-employee-status
        {--execute : Tulis perubahan; tanpa flag ini hanya preview}';

    protected $description = 'Seragamkan Status Employee menjadi Permanent, Contract, atau Outsource';

    public function handle(EmployeeRepositoryInterface $employees, AuditLogRepositoryInterface $auditRepo): int
    {
        $changes = [];
        $unknown = [];

        foreach ($employees->getAll() as $employee) {
            $old = (string) ($employee->statusEmployee ?? '');
            $new = EmployeeStatus::canonical($old);

            if (! EmployeeStatus::fromValue($new) && ! EmployeeStatus::isTerminal($new)) {
                $unknown[] = [$employee->employeeId, $employee->fullName, $old === '' ? '(kosong)' : $old];
            } elseif ($new !== $old) {
                $changes[] = ['id' => (string) $employee->employeeId, 'old' => $old, 'new' => $new];
            }
        }

        $summary = collect($changes)
            ->groupBy(fn (array $c) => "{$c['old']}\0{$c['new']}")
            ->map(fn ($group) => [$group[0]['old'], $group[0]['new'], $group->count()])
            ->values()
            ->all();

        if ($summary === []) {
            $this->info('Tidak ada Status Employee yang perlu diseragamkan.');
        } else {
            $this->table(['Status lama', 'Status baru', 'Jumlah'], $summary);
        }

        if ($unknown !== []) {
            $this->warn('Status tidak dikenali — tidak diubah, tinjau manual:');
            $this->table(['Employee ID', 'Nama', 'Status'], $unknown);
        }

        if (! $this->option('execute')) {
            if ($changes !== []) {
                $this->comment('Preview saja; tidak ada data ditulis. Tambahkan --execute untuk menjalankan.');
            }

            return self::SUCCESS;
        }

        $now = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $failed = 0;
        foreach ($changes as $change) {
            if (! $employees->update($change['id'], ['Status Employee' => $change['new'], 'Updated At' => $now])) {
                $failed++;
                $this->error("Gagal memperbarui {$change['id']}.");

                continue;
            }
            $auditRepo->log('Employee', $change['id'], 'updated', 'Status Employee', $change['old'], $change['new'], 'System', 'Artisan');
        }

        $this->info(sprintf('%d Status Employee diseragamkan.', count($changes) - $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
