<?php

namespace App\Services;

use App\Enums\SkDocumentType;
use App\Enums\WarningLetterLevel;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Masa berlaku sanksi SP. Surat yang lebih baru menggugurkan durasi surat sebelumnya.
 * Hanya surat terbaru yang bisa berstatus aktif.
 */
class WarningLetterSanctionService
{
    public function __construct(
        private EmployeeDocumentRepositoryInterface $documents,
    ) {}

    /**
     * Penanda sanksi aktif dan seluruh karyawan yang pernah menerima SP.
     *
     * @return array{badges: array<string, array{level: string, valid_until_label: string}>, employee_ids: array<string, true>}
     */
    public function employeeIndex(): array
    {
        $letters = $this->warningLetters();
        $ids = [];
        foreach ($letters as $letter) {
            $ids[$letter['employee_id']] = true;
        }

        return [
            'badges' => $this->activeBadgesFromLetters($letters),
            'employee_ids' => $ids,
        ];
    }

    /**
     * Penanda karyawan yang sanksinya masih berjalan, diindeks Employee ID.
     *
     * @return array<string, array{level: string, valid_until_label: string}>
     */
    public function activeBadges(): array
    {
        return $this->activeBadgesFromLetters($this->warningLetters());
    }

    public function validUntil(string $reference, string $notes, ?Carbon $issuedAt): ?Carbon
    {
        if (preg_match('/berlaku s\.d\. (\d{4}-\d{2}-\d{2})/', $notes, $match) === 1) {
            return Carbon::parse($match[1], 'Asia/Jakarta')->startOfDay();
        }

        $level = WarningLetterLevel::tryFrom(trim($reference));
        if ($level === null || $issuedAt === null) {
            return null;
        }

        return $issuedAt->copy()->timezone('Asia/Jakarta')->startOfDay()
            ->addMonthsNoOverflow($level->validityMonths())
            ->subDay();
    }

    public function isActive(?Carbon $validUntil, ?Carbon $today = null): bool
    {
        if ($validUntil === null) {
            return false;
        }

        $today ??= now()->timezone('Asia/Jakarta')->startOfDay();

        return $validUntil->greaterThanOrEqualTo($today);
    }

    /**
     * @param  list<array{key?: string, employee_id: string, reference: string, notes: string, doc_type: string, issued_at: ?Carbon}>  $letters
     * @return array<string, array{active: bool, superseded: bool, level: string, valid_until_label: string, employee_id: string}>
     */
    public function statesFor(array $letters): array
    {
        $prepared = [];
        $latestKey = [];
        foreach (array_values($letters) as $index => $letter) {
            $key = (string) ($letter['key'] ?? $letter['employee_id'].'#'.$index);
            $level = WarningLetterLevel::tryFrom(trim((string) $letter['reference']));
            $validUntil = $this->validUntil((string) $letter['reference'], (string) $letter['notes'], $letter['issued_at']);
            $prepared[$key] = [
                'key' => $key,
                'employee_id' => $letter['employee_id'],
                'issued_at' => $letter['issued_at'],
                'rank' => $this->rank($level),
                'level' => $level?->shortLabel() ?? ($letter['doc_type'] !== '' ? $letter['doc_type'] : 'SP'),
                'valid_until' => $validUntil,
            ];
            $currentKey = $latestKey[$letter['employee_id']] ?? null;
            if ($currentKey === null || $this->isNewer($prepared[$key], $prepared[$currentKey])) {
                $latestKey[$letter['employee_id']] = $key;
            }
        }

        $states = [];
        foreach ($prepared as $key => $letter) {
            $superseded = $latestKey[$letter['employee_id']] !== $key;
            $states[$key] = [
                'active' => !$superseded && $this->isActive($letter['valid_until']),
                'superseded' => $superseded,
                'level' => $letter['level'],
                'valid_until_label' => $letter['valid_until']?->timezone('Asia/Jakarta')->format('d M Y') ?? '',
                'employee_id' => $letter['employee_id'],
            ];
        }

        return $states;
    }

    /**
     * @param  list<array{key?: string, employee_id: string, reference: string, notes: string, doc_type: string, issued_at: ?Carbon}>  $letters
     * @return array<string, array{level: string, valid_until_label: string}>
     */
    public function activeBadgesFromLetters(array $letters): array
    {
        $badges = [];
        foreach ($this->statesFor($letters) as $state) {
            if (!$state['active'] || $state['valid_until_label'] === '') {
                continue;
            }

            $badges[$state['employee_id']] = [
                'level' => $state['level'],
                'valid_until_label' => $state['valid_until_label'],
            ];
        }

        return $badges;
    }

    /**
     * @return list<array{employee_id: string, reference: string, notes: string, doc_type: string, issued_at: ?Carbon}>
     */
    private function warningLetters(): array
    {
        $letters = [];
        foreach ($this->documents->getAll() as $row) {
            if (strtoupper(trim((string) ($row['Doc Code'] ?? ''))) !== SkDocumentType::SURAT_PERINGATAN->value) {
                continue;
            }

            $employeeId = ltrim(trim((string) ($row['Employee ID'] ?? '')), "'");
            if ($employeeId === '') {
                continue;
            }

            $letters[] = [
                'employee_id' => $employeeId,
                'reference' => trim((string) ($row['Reference'] ?? '')),
                'notes' => trim((string) ($row['Notes'] ?? '')),
                'doc_type' => trim((string) ($row['Doc Type'] ?? '')),
                'issued_at' => $this->parseDate((string) ($row['Issued At'] ?? '')),
            ];
        }

        return $letters;
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value, 'Asia/Jakarta');
        } catch (\Throwable) {
            return null;
        }
    }

    private function rank(?WarningLetterLevel $level): int
    {
        return match ($level) {
            WarningLetterLevel::SP1 => 1,
            WarningLetterLevel::SP2, WarningLetterLevel::SP1_FINAL => 2,
            WarningLetterLevel::SP3 => 3,
            default => 0,
        };
    }

    /**
     * @param  array{issued_at: ?Carbon, rank: int}  $candidate
     * @param  array{issued_at: ?Carbon, rank: int}  $current
     */
    private function isNewer(array $candidate, array $current): bool
    {
        $candidateAt = $candidate['issued_at']?->timestamp ?? 0;
        $currentAt = $current['issued_at']?->timestamp ?? 0;

        if ($candidateAt !== $currentAt) {
            return $candidateAt > $currentAt;
        }

        if ($candidate['rank'] !== $current['rank']) {
            return $candidate['rank'] > $current['rank'];
        }

        return strcmp((string) $candidate['key'], (string) $current['key']) > 0;
    }
}
