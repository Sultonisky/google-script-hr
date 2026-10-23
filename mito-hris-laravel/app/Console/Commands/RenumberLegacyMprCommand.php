<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\MprRequest;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Support\MprNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nomor MPR baru memakai MPR-2026-001, MPR-2026-002, …
 * Record production yang masih MPR-YYYYMMDD-XXXX tidak ikut sequence itu.
 * Perintah ini memetakan MPR-001 dan MPR-YYYYMMDD-XXXX ke nomor tahun pengajuannya.
 *
 * Usage:
 *   php artisan mito:renumber-legacy-mpr
 *   php artisan mito:renumber-legacy-mpr --execute
 */
class RenumberLegacyMprCommand extends Command
{
    protected $signature = 'mito:renumber-legacy-mpr
        {--execute : Tulis perubahan; tanpa flag ini hanya preview}';

    protected $description = 'Ubah MPR-001 dan MPR-YYYYMMDD-XXXX menjadi MPR-2026-001, …';

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
            $this->info('Tidak ada nomor MPR yang perlu diubah.');

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

        $this->info(sprintf('%d nomor MPR diubah.', count($plan['changes'])));
        $this->comment('Arsip Google Sheet tidak diubah. Jalankan mirror database ke sheet secara manual jika arsip perlu mengikuti nomor baru.');

        return self::SUCCESS;
    }

    /**
     * @return array{changes: list<array{id: int, old: string, new: string, date: string, position: string}>, skipped: list<MprRequest>}
     */
    private function plan(): array
    {
        $rows = MprRequest::query()->orderBy('id')->get();
        $maxByYear = [];
        $taken = [];
        $plain = [];
        $legacy = [];
        $skipped = [];

        foreach ($rows as $row) {
            $number = trim((string) $row->mpr_number);
            if (MprNumber::isCurrent($number) && preg_match(MprNumber::PATTERN, $number, $match)) {
                $year = $match[1];
                $maxByYear[$year] = max($maxByYear[$year] ?? 0, (int) $match[2]);
                $taken[MprNumber::format($year, (int) $match[2])] = true;
                continue;
            }

            if (MprNumber::plainSequence($number) !== null) {
                $plain[] = $row;
                continue;
            }

            if (MprNumber::isLegacy($number)) {
                $legacy[] = $row;
                continue;
            }

            $skipped[] = $row;
        }

        usort($plain, function (MprRequest $left, MprRequest $right): int {
            return (MprNumber::plainSequence((string) $left->mpr_number) ?? 0)
                <=> (MprNumber::plainSequence((string) $right->mpr_number) ?? 0);
        });
        usort($legacy, fn (MprRequest $left, MprRequest $right): int => $this->compareByRequestDate($left, $right));

        $changes = [];
        foreach ($plain as $row) {
            $date = trim((string) $row->request_date);
            $year = $this->yearFor($row);
            $sequence = MprNumber::plainSequence((string) $row->mpr_number) ?? 1;
            $candidate = MprNumber::format($year, $sequence);
            if (isset($taken[$candidate])) {
                do {
                    $maxByYear[$year] = ($maxByYear[$year] ?? 0) + 1;
                    $candidate = MprNumber::format($year, $maxByYear[$year]);
                } while (isset($taken[$candidate]));
            }
            $taken[$candidate] = true;
            if (preg_match(MprNumber::PATTERN, $candidate, $assigned)) {
                $maxByYear[$year] = max($maxByYear[$year] ?? 0, (int) $assigned[2]);
            }
            $changes[] = $this->change($row, $candidate, $date);
        }

        foreach ($legacy as $row) {
            $date = trim((string) $row->request_date);
            $year = $this->yearFor($row);
            do {
                $maxByYear[$year] = ($maxByYear[$year] ?? 0) + 1;
                $candidate = MprNumber::format($year, $maxByYear[$year]);
            } while (isset($taken[$candidate]));
            $taken[$candidate] = true;
            $changes[] = $this->change($row, $candidate, $date);
        }

        return ['changes' => $changes, 'skipped' => $skipped];
    }

    private function yearFor(MprRequest $row): string
    {
        return MprNumber::yearFromRequestDate($row->request_date)
            ?? ($row->created_at?->timezone('Asia/Jakarta')->format('Y'))
            ?? now()->timezone('Asia/Jakarta')->format('Y');
    }

    private function compareByRequestDate(MprRequest $left, MprRequest $right): int
    {
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
    }

    /**
     * @return array{id: int, old: string, new: string, date: string, position: string}
     */
    private function change(MprRequest $row, string $newNumber, string $date): array
    {
        return [
            'id' => (int) $row->id,
            'old' => trim((string) $row->mpr_number),
            'new' => $newNumber,
            'date' => $date,
            'position' => trim((string) $row->position),
        ];
    }
}
