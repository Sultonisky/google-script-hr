<?php

namespace App\Services;

use App\Enums\AssetCategory;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AssetService
{
    private const MAX_GENERATE_ATTEMPTS = 50;

    public function __construct(
        protected AuditLogRepositoryInterface $auditRepo,
        protected EmployeeRepositoryInterface $employeeRepo,
    ) {}

    // ── Code Generation ─────────────────────────────────────────────

    public function generateCode(AssetCategory $category): string
    {
        $prefix = $category->prefix();
        $existingCodes = Asset::where('asset_code', 'LIKE', "{$prefix}-%")
            ->pluck('asset_code')->filter()->all();

        $maxSequence = $this->findMaxSequence($prefix, $existingCodes);

        for ($attempt = 0; $attempt < self::MAX_GENERATE_ATTEMPTS; $attempt++) {
            $nextSequence = $maxSequence + 1 + $attempt;
            $code = sprintf('%s-%05d', $prefix, $nextSequence);
            if (!Asset::where('asset_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException("Failed to generate unique asset code for {$prefix}.");
    }

    private function findMaxSequence(string $prefix, array $codes): int
    {
        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d{5})$/', $code, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > $max) $max = $seq;
            }
        }
        return $max;
    }

    public function generateCodesForMissingAssets(): array
    {
        $summary = ['total' => 0, 'generated' => 0, 'skipped' => 0, 'errors' => 0, 'categories' => []];

        $assets = Asset::whereNull('asset_code')->orWhere('asset_code', '')->get();
        $summary['total'] = $assets->count();
        if ($assets->isEmpty()) return $summary;

        $grouped = $assets->groupBy(fn ($a) => $a->category->value);

        foreach ($grouped as $categoryName => $categoryAssets) {
            $category = AssetCategory::tryFrom($categoryName);
            if (!$category) { $summary['errors'] += $categoryAssets->count(); continue; }

            $prefix = $category->prefix();
            $existingCodes = Asset::where('asset_code', 'LIKE', "{$prefix}-%")
                ->whereNotNull('asset_code')->where('asset_code', '!=', '')
                ->pluck('asset_code')->all();
            $currentMax = $this->findMaxSequence($prefix, $existingCodes);
            $count = 0;

            foreach ($categoryAssets as $asset) {
                if (!empty(trim($asset->asset_code ?? ''))) { $summary['skipped']++; continue; }

                $currentMax++;
                $newCode = sprintf('%s-%05d', $prefix, $currentMax);

                if (Asset::where('asset_code', $newCode)->exists()) {
                    try { $newCode = $this->generateCode($category); }
                    catch (RuntimeException $e) {
                        $summary['errors']++;
                        Log::error('Asset code generation failed', ['asset_id' => $asset->id, 'error' => $e->getMessage()]);
                        continue;
                    }
                }

                $asset->update(['asset_code' => $newCode]);
                $count++;
            }

            $summary['categories'][$category->label()] = $count;
            $summary['generated'] += $count;
        }

        return $summary;
    }

    // ── Assignment ──────────────────────────────────────────────────

    public function assignAsset(
        Asset $asset,
        string $employeeId,
        string $assignedDate,
        ?string $notes = null,
        ?string $assignedBy = null,
    ): AssetAssignment {
        if (!$asset->status->isAssignable()) {
            throw new RuntimeException("Cannot assign: status is '{$asset->status->value}'.");
        }
        if ($asset->activeAssignment()->exists()) {
            throw new RuntimeException("Cannot assign: asset already has an active assignment.");
        }

        $employee = null;
        try {
            $employee = $this->employeeRepo->findById($employeeId);
        } catch (\Throwable $e) {
            Log::warning('Employee lookup failed during asset assignment', [
                'employee_id' => $employeeId, 'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Gagal memverifikasi karyawan.');
        }

        if (!$employee) {
            throw new RuntimeException("Employee ID tidak dikenal: {$employeeId}");
        }

        $employeeName = $employee->fullName ?? $employeeId;

        $assignment = DB::transaction(function () use (
            $asset, $employeeId, $employeeName, $assignedDate, $notes, $assignedBy
        ) {
            $a = AssetAssignment::create([
                'asset_id'         => $asset->id,
                'employee_id'      => $employeeId,
                'employee_name'    => $employeeName,
                'assigned_date'    => $assignedDate,
                'assigned_by'      => $assignedBy,
                'assignment_notes' => $notes,
                'status'           => 'active',
            ]);
            $asset->update(['status' => AssetStatus::ASSIGNED]);
            return $a;
        });

        $this->auditRepo->log('Asset', (string) $asset->id, 'assigned', 'status',
            AssetStatus::AVAILABLE->value, AssetStatus::ASSIGNED->value . " → {$employeeId}",
            $assignedBy, 'Asset Management');

        return $assignment;
    }

    public function returnAsset(
        Asset $asset,
        string $returnDate,
        ?string $returnNotes = null,
        ?string $returnBy = null,
    ): AssetAssignment {
        $assignment = $asset->activeAssignment;
        if (!$assignment) throw new RuntimeException('Cannot return: no active assignment.');

        DB::transaction(function () use ($asset, $assignment, $returnDate, $returnNotes, $returnBy) {
            $assignment->update([
                'return_date'  => $returnDate,
                'return_by'    => $returnBy,
                'return_notes' => $returnNotes,
                'status'       => 'returned',
            ]);
            $asset->update(['status' => AssetStatus::AVAILABLE]);
        });

        $this->auditRepo->log('Asset', (string) $asset->id, 'returned', 'status',
            AssetStatus::ASSIGNED->value, AssetStatus::AVAILABLE->value . " ← {$assignment->employee_id}",
            $returnBy, 'Asset Management');

        return $assignment->fresh();
    }

    // ── Statistics ──────────────────────────────────────────────────

    public function getStats(): array
    {
        $base = fn () => Asset::query();
        return [
            'total'       => $base()->count(),
            'available'   => $base()->where('status', AssetStatus::AVAILABLE)->count(),
            'assigned'    => $base()->where('status', AssetStatus::ASSIGNED)->count(),
            'maintenance' => $base()->where('status', AssetStatus::MAINTENANCE)->count(),
            'damaged'     => $base()->where('status', AssetStatus::DAMAGED)->count(),
            'lost'        => $base()->where('status', AssetStatus::LOST)->count(),
            'disposed'    => $base()->where('status', AssetStatus::DISPOSED)->count(),
            'categories'  => [
                'Building'   => $base()->where('category', AssetCategory::BUILDING)->count(),
                'Vehicle'    => $base()->where('category', AssetCategory::VEHICLE)->count(),
                'Office'     => $base()->where('category', AssetCategory::OFFICE)->count(),
                'Elektronik' => $base()->where('category', AssetCategory::ELEKTRONIK)->count(),
            ],
            'without_code' => $base()->whereNull('asset_code')->orWhere('asset_code', '')->count(),
        ];
    }

    public function getMissingCodeSummary(): array
    {
        return Asset::whereNull('asset_code')->orWhere('asset_code', '')
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }
}
