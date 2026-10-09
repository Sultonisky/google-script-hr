<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Move an outsource person (and every row keyed by their Outsource ID) to a new ID,
 * so the old ID can be taken by the Attendance record that already uses it.
 *
 * Usage:
 *   php artisan mito:outsource-reassign-id DM20260129 DM20261000
 *   php artisan mito:outsource-reassign-id DM20260129 DM20261000 --execute --reason="Bentrok ID dengan Attendance"
 */
class ReassignOutsourceIdCommand extends Command
{
    /** Table => column holding the Outsource ID. */
    private const REFERENCES = [
        'outsource_employees' => 'outsource_id',
        'outsource_payslips' => 'outsource_id',
        'outsource_incentives' => 'outsource_id',
        'employee_documents' => 'employee_id',
        'employee_document_files' => 'employee_id',
        'audit_logs' => 'entity_id',
    ];

    protected $signature = 'mito:outsource-reassign-id
        {from : Outsource ID lama}
        {to : Outsource ID baru}
        {--execute : Tulis perubahan; tanpa flag ini hanya preview}
        {--reason= : Alasan perubahan untuk audit log}';

    protected $description = 'Pindahkan Outsource ID beserta slip gaji, insentif, dokumen, dan audit log ke ID baru';

    public function handle(AuditLogRepositoryInterface $auditRepo): int
    {
        $from = strtoupper(trim((string) $this->argument('from')));
        $to = strtoupper(trim((string) $this->argument('to')));
        $execute = (bool) $this->option('execute');

        if (config('hris.data_driver') !== 'pgsql') {
            $this->error('Command ini hanya berjalan saat HRIS_DATA_DRIVER=pgsql.');

            return self::FAILURE;
        }

        if ($from === $to || ! preg_match('/^[A-Z0-9][A-Z0-9._-]{0,31}$/', $to)) {
            $this->error('ID baru tidak valid atau sama dengan ID lama.');

            return self::FAILURE;
        }

        if (! $this->isOutsideSequenceOrReserved($to)) {
            $this->error("ID {$to} akan menggeser nomor urut berikutnya. Tambahkan dulu ke hris.outsource.reserved_ids.");

            return self::FAILURE;
        }

        $person = DB::table('outsource_employees')->where('outsource_id', $from)->first(['outsource_id', 'full_name']);
        if ($person === null) {
            $this->error("Outsource ID {$from} tidak ditemukan.");

            return self::FAILURE;
        }

        foreach (self::REFERENCES as $table => $column) {
            if (DB::table($table)->where($column, $to)->exists()) {
                $this->error("ID {$to} sudah dipakai di tabel {$table}.");

                return self::FAILURE;
            }
        }

        $this->line("{$from} ({$person->full_name}) -> {$to}");
        $this->table(['Tabel', 'Baris'], $this->countRows($from));

        if (! $execute) {
            $this->comment('Preview saja; tidak ada data ditulis. Tambahkan --execute untuk menjalankan.');

            return self::SUCCESS;
        }

        $reason = trim((string) $this->option('reason'));

        DB::transaction(function () use ($from, $to, $reason, $auditRepo): void {
            DB::table('outsource_employees')->where('outsource_id', $from)->lockForUpdate()->first();

            foreach (self::REFERENCES as $table => $column) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }

            $auditRepo->log(
                'Outsource',
                $to,
                'id_reassigned',
                Str::limit($reason !== '' ? "Outsource ID ({$reason})" : 'Outsource ID', 250),
                $from,
                $to,
                'System',
                'Artisan',
            );
        });

        $this->info("Outsource ID {$from} berhasil dipindah ke {$to}.");

        return self::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    private function countRows(string $id): array
    {
        $rows = [];
        foreach (self::REFERENCES as $table => $column) {
            $rows[] = [$table, DB::table($table)->where($column, $id)->count()];
        }

        return $rows;
    }

    private function isOutsideSequenceOrReserved(string $id): bool
    {
        $reserved = array_map(
            static fn ($value): string => strtoupper(trim((string) $value)),
            (array) config('hris.outsource.reserved_ids', []),
        );
        if (in_array($id, $reserved, true)) {
            return true;
        }

        $prefix = strtoupper(trim((string) config('hris.outsource.id_prefix', 'DM'))) ?: 'DM';

        return preg_match('/^' . preg_quote($prefix, '/') . '\d{4}\d+$/', $id) !== 1;
    }
}
