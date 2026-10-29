<?php

namespace App\Services;

use App\Enums\SkDocumentType;
use App\Enums\WarningLetterLevel;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Masa berlaku sanksi SP. Surat tingkat yang lebih tinggi menggugurkan surat
 * di bawahnya untuk karyawan yang sama. Surat tingkat yang sama berjalan sendiri.
 */
class WarningLetterSanctionService
{
    public function __construct(
        private EmployeeDocumentRepositoryInterface $documents,
    ) {}

    /**
     * Penanda sanksi aktif dan seluruh karyawan yang pernah menerima SP.
     *
     * @return array{badges: array<string, list<array{level: string, valid_until_label: string}>>, employee_ids: array<string, true>, letter_counts: array<string, int>}
     */
    public function employeeIndex(): array
    {
        $letters = $this->warningLetters();
        $ids = [];
        $letterCounts = [];
        foreach ($letters as $letter) {
            $ids[$letter['employee_id']] = true;
            $letterCounts[$letter['employee_id']] = ($letterCounts[$letter['employee_id']] ?? 0) + 1;
        }

        return [
            'badges' => $this->activeBadgesFromLetters($letters),
            'employee_ids' => $ids,
            'letter_counts' => $letterCounts,
        ];
    }

    /**
     * Ringkasan sanksi aktif untuk header drawer satu karyawan.
     *
     * @return array{label: string, extra: int}|null
     */
    public function drawerSummary(string $employeeId): ?array
    {
        $employeeId = ltrim(trim($employeeId), "'");
        if ($employeeId === '') {
            return null;
        }

        $letters = array_values(array_filter(
            $this->warningLetters(),
            fn (array $letter) => $letter['employee_id'] === $employeeId,
        ));
        if ($letters === []) {
            return null;
        }

        $latest = $this->activeBadgesFromLetters($letters)[$employeeId][0] ?? null;
        if ($latest === null) {
            return null;
        }

        return [
            'label' => $latest['level'].' · s.d. '.$latest['valid_until_label'],
            'extra' => max(0, count($letters) - 1),
        ];
    }

    /**
     * Penanda sanksi yang masih berjalan, diindeks Employee ID.
     *
     * @return array<string, list<array{level: string, valid_until_label: string}>>
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
        foreach (array_values($letters) as $index => $letter) {
            $key = (string) ($letter['key'] ?? $letter['employee_id'].'#'.$index);
            $level = WarningLetterLevel::resolve(
                (string) ($letter['reference'] ?? ''),
                (string) ($letter['doc_type'] ?? ''),
                (string) ($letter['nomor'] ?? ''),
            );
            $validUntil = $this->validUntil((string) $letter['reference'], (string) $letter['notes'], $letter['issued_at']);
            $prepared[$key] = [
                'key' => $key,
                'employee_id' => $letter['employee_id'],
                'issued_at' => $letter['issued_at'],
                'rank' => $this->rank($level),
                'level' => $level?->shortLabel() ?? ($letter['doc_type'] !== '' ? $letter['doc_type'] : 'SP'),
                'valid_until' => $validUntil,
            ];
        }

        $states = [];
        foreach ($prepared as $key => $letter) {
            $superseded = false;
            foreach ($prepared as $other) {
                if ($other['employee_id'] !== $letter['employee_id'] || $other['key'] === $key) {
                    continue;
                }
                if ($other['rank'] > $letter['rank'] && $this->isNewer($other, $letter)) {
                    $superseded = true;
                    break;
                }
            }
            $states[$key] = [
                'active' => !$superseded && $this->isActive($letter['valid_until']),
                'superseded' => $superseded,
                'level' => $letter['level'],
                'valid_until_label' => $letter['valid_until']?->timezone('Asia/Jakarta')->format('d M Y') ?? '',
                'employee_id' => $letter['employee_id'],
                'issued_at' => $letter['issued_at']?->timestamp ?? 0,
            ];
        }

        return $states;
    }

    /**
     * @param  list<array{key?: string, employee_id: string, reference: string, notes: string, doc_type: string, issued_at: ?Carbon}>  $letters
     * @return array<string, list<array{level: string, valid_until_label: string}>>
     */
    public function activeBadgesFromLetters(array $letters): array
    {
        $badges = [];
        foreach ($this->statesFor($letters) as $state) {
            if (!$state['active'] || $state['valid_until_label'] === '') {
                continue;
            }

            $badges[$state['employee_id']][] = [
                'level' => $state['level'],
                'valid_until_label' => $state['valid_until_label'],
                'issued_at' => $state['issued_at'],
            ];
        }

        foreach ($badges as $employeeId => $items) {
            usort($items, fn (array $left, array $right) => $right['issued_at'] <=> $left['issued_at']);
            $badges[$employeeId] = array_map(fn (array $item) => [
                'level' => $item['level'],
                'valid_until_label' => $item['valid_until_label'],
            ], $items);
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
                'nomor' => trim((string) ($row['Nomor'] ?? '')),
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
