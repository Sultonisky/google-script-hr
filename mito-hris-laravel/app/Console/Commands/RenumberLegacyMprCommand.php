<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\MprRequest;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nomor MPR baru memakai MPR-001, MPR-002, …
 * Record production yang masih MPR-YYYYMMDD-XXXX tidak ikut sequence itu.
 * Perintah ini memetakan nomor lama ke nomor pendek berikutnya, urut tanggal pengajuan.
 *
 * Usage:
 *   php artisan mito:renumber-legacy-mpr
 *   php artisan mito:renumber-legacy-mpr --execute
 */
class RenumberLegacyMprCommand extends Command
{
    protected $signature = 'mito:renumber-legacy-mpr
        {--execute : Tulis perubahan; tanpa flag ini hanya preview}';

    protected $description = 'Ubah nomor MPR format lama (MPR-YYYYMMDD-XXXX) menjadi MPR-001, MPR-002, …';

    public function handle(AuditLogRepositoryInterface $auditRepo): int
    {
        $plan = $this->plan();

        if ($plan['skipped'] !== []) {
            $this->warn('Nomor tidak dikenali — tidak diubah:');
            $this->table(
                ['Nomor', 'Posisi'],
                array_map(fn (MprRequest $row) => [$row->mpr_number, $row->position], $plan['skipped'])
            );
        }

        if ($plan['changes'] === []) {
            $this->info('Tidak ada nomor MPR format lama yang perlu diubah.');

            return self::SUCCESS;
        }

        $this->table(
            ['Nomor lama', 'Nomor baru', 'Tanggal', 'Posisi'],
            array_map(fn (array $change) => [
                $change['old'],
                $change['new'],
                $change['date'] !== '' ? $change['date'] : '-',
                $change['position'] !== '' ? $change['position'] : '-',
            ], $plan['changes'])
        );

        if (! $this->option('execute')) {
            $this->comment('Preview saja; tidak ada data ditulis. Tambahkan --execute untuk menjalankan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan, $auditRepo) {
            foreach ($plan['changes'] as $change) {
                MprRequest::query()->whereKey($change['id'])->update([
                    'mpr_number' => $change['new'],
                ]);

                AuditLog::query()
                    ->where('entity_type', 'MPR')
                    ->where('entity_id', $change['old'])
                    ->update(['entity_id' => $change['new']]);

                $auditRepo->log(
                    'MPR',
                    $change['new'],
                    'renumbered',
                    'mpr_number',
                    $change['old'],
                    $change['new'],
                    'System',
                    'Artisan'
                );
            }
        });

        $this->info(sprintf('%d nomor MPR format lama diubah.', count($plan['changes'])));
        $this->comment('Arsip Google Sheet tidak diubah. Jalankan mirror database ke sheet secara manual jika arsip perlu mengikuti nomor baru.');

        return self::SUCCESS;
    }

    /**
     * @return array{changes: list<array{id: int, old: string, new: string, date: string, position: string}>, skipped: list<MprRequest>}
     */
    private function plan(): array
    {
        $rows = MprRequest::query()->orderBy('id')->get();
        $max = 0;
        $legacy = [];
        $skipped = [];

        foreach ($rows as $row) {
            $number = trim((string) $row->mpr_number);
            if (preg_match('/^MPR-(\d+)$/i', $number, $match)) {
                $max = max($max, (int) $match[1]);
                continue;
            }

            if (preg_match('/^MPR-\d{8}-\d+$/i', $number)) {
                $legacy[] = $row;
                continue;
            }

            $skipped[] = $row;
        }

        usort($legacy, function (MprRequest $left, MprRequest $right): int {
            $leftDate = $left->request_date !== null && $left->request_date !== '' ? $left->request_date : '9999-99-99';
            $rightDate = $right->request_date !== null && $right->request_date !== '' ? $right->request_date : '9999-99-99';
            $byDate = strcmp($leftDate, $rightDate);
            if ($byDate !== 0) {
                return $byDate;
            }

            $byCreated = ($left->created_at?->getTimestamp() ?? 0) <=> ($right->created_at?->getTimestamp() ?? 0);
            if ($byCreated !== 0) {
                return $byCreated;
            }

            return $left->id <=> $right->id;
        });

        $changes = [];
        foreach ($legacy as $row) {
            $max++;
            $changes[] = [
                'id' => (int) $row->id,
                'old' => trim((string) $row->mpr_number),
                'new' => 'MPR-'.str_pad((string) $max, 3, '0', STR_PAD_LEFT),
                'date' => trim((string) $row->request_date),
                'position' => trim((string) $row->position),
            ];
        }

        return ['changes' => $changes, 'skipped' => $skipped];
    }
}
