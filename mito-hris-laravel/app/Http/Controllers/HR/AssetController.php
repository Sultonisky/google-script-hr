<?php

namespace App\Http\Controllers\HR;

use App\Enums\AssetCategory;
use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\AssignAssetRequest;
use App\Http\Requests\HR\StoreAssetRequest;
use App\Http\Requests\HR\UpdateAssetRequest;
use App\Models\Asset;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function __construct(
        protected AssetService $assetService,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    // ── LIST ───────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $perPage    = max(1, (int) $request->query('per_page', 15));
        $currentPage = max(1, (int) $request->query('page', 1));

        $query = Asset::query()
            ->search($request->query('search'))
            ->ofCategory($request->query('category'))
            ->ofStatus($request->query('status'))
            ->ofCondition($request->query('condition'))
            ->ofLocation($request->query('location'))
            ->ofAssignment($request->query('assignment'));

        $total = (clone $query)->count();
        $offset = ($currentPage - 1) * $perPage;
        $assets = (clone $query)->orderBy('id', 'desc')->skip($offset)->take($perPage)->get();

        $stats = $this->assetService->getStats();
        $locations = Asset::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        $assetIndexPath = route('assets.portal.index');
        $assetBasePath  = '/assets';

        return view('hr.assets.index', compact(
            'assets', 'stats', 'total', 'currentPage', 'perPage', 'locations',
            'assetIndexPath', 'assetBasePath',
        ));
    }

    // ── CREATE ─────────────────────────────────────────────────────

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->actorEmail();
        if (empty($data['status']))  $data['status'] = AssetStatus::AVAILABLE->value;
        if (empty($data['condition_status'])) $data['condition_status'] = AssetCondition::GOOD->value;

        $asset = Asset::create($data);

        $this->auditRepo->log('Asset', (string) $asset->id, 'created', null, null,
            $asset->name, $data['created_by'], 'Asset Management');

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil ditambahkan.',
            'asset'   => $asset,
        ], 201);
    }

    // ── SHOW ───────────────────────────────────────────────────────

    public function show(Asset $asset): JsonResponse
    {
        $asset->load(['activeAssignment', 'assignments' => function ($q) {
            $q->orderByDesc('assigned_date')->limit(20);
        }]);

        return response()->json(['success' => true, 'asset' => $asset]);
    }

    // ── UPDATE ─────────────────────────────────────────────────────

    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        $old = $asset->only(array_keys($request->validated()));
        $asset->fill($request->validated());
        $asset->save();

        $actor = $this->actorEmail();
        foreach ($request->validated() as $field => $newVal) {
            if (isset($old[$field])) {
                $oldVal = $old[$field];
                if ($oldVal instanceof \BackedEnum) $oldVal = $oldVal->value;
                if ((string) ($oldVal ?? '') !== (string) ($newVal ?? '')) {
                    $this->auditRepo->log('Asset', (string) $asset->id, 'updated', $field,
                        $oldVal ?? null, $newVal, $actor, 'Asset Management');
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil diperbarui.',
            'asset'   => $asset->fresh(),
        ]);
    }

    // ── DELETE / DISPOSE ───────────────────────────────────────────

    public function destroy(Asset $asset): JsonResponse
    {
        $actor = $this->actorEmail();
        $asset->update(['status' => AssetStatus::DISPOSED]);

        $this->auditRepo->log('Asset', (string) $asset->id, 'disposed', 'status',
            $asset->getOriginal('status'), AssetStatus::DISPOSED->value, $actor, 'Asset Management');

        return response()->json(['success' => true, 'message' => 'Aset berhasil didisposisi.']);
    }

    // ── CODE GENERATION ────────────────────────────────────────────

    public function generateCode(Asset $asset): JsonResponse
    {
        if (!empty(trim($asset->asset_code ?? ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Aset sudah memiliki kode: ' . $asset->asset_code,
            ], 422);
        }

        $code = $this->assetService->generateCode($asset->category);
        $asset->update(['asset_code' => $code]);

        $actor = $this->actorEmail();
        $this->auditRepo->log('Asset', (string) $asset->id, 'code_generated', 'asset_code',
            null, $code, $actor, 'Asset Management');

        return response()->json([
            'success' => true,
            'message' => "Kode aset {$code} berhasil digenerate.",
            'code'    => $code,
        ]);
    }

    public function generateBulkCodes(): JsonResponse
    {
        $summary = $this->assetService->generateCodesForMissingAssets();

        $actor = $this->actorEmail();
        $this->auditRepo->log('Asset', null, 'bulk_codes_generated', null, null,
            json_encode($summary), $actor, 'Asset Management');

        $message = $summary['generated'] > 0
            ? "Berhasil generate {$summary['generated']} kode aset."
            : 'Tidak ada aset yang perlu digenerate.';

        return response()->json(['success' => true, 'message' => $message, 'summary' => $summary]);
    }

    // ── ASSIGNMENT ─────────────────────────────────────────────────

    public function assign(AssignAssetRequest $request, Asset $asset): JsonResponse
    {
        try {
            $assignment = $this->assetService->assignAsset(
                $asset,
                $request->validated('employee_id'),
                $request->validated('assigned_date'),
                $request->validated('notes'),
                $this->actorEmail(),
            );

            return response()->json([
                'success'    => true,
                'message'    => 'Aset berhasil ditugaskan.',
                'assignment' => $assignment,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function returnAsset(Asset $asset, Request $request): JsonResponse
    {
        $data = $request->validate([
            'return_date'  => ['required', 'date'],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $assignment = $this->assetService->returnAsset(
                $asset,
                $data['return_date'],
                $data['return_notes'] ?? null,
                $this->actorEmail(),
            );

            return response()->json([
                'success'    => true,
                'message'    => 'Aset berhasil dikembalikan.',
                'assignment' => $assignment,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── JSON API ───────────────────────────────────────────────────

    public function getJson(Asset $asset): JsonResponse
    {
        $asset->load(['activeAssignment', 'assignments' => function ($q) {
            $q->orderByDesc('assigned_date')->limit(20);
        }]);

        return response()->json(['success' => true, 'asset' => $asset]);
    }

    private function actorEmail(): string
    {
        $portal = request()->attributes->get('portal');
        $sessionKey = match ($portal) {
            'assets' => 'asset_auth',
            'certificates' => 'certificate_auth',
            'mpr' => config('mpr.session_key', 'mpr_requestor_auth'),
            default => 'hr_user',
        };

        return (string) session($sessionKey . '.email', 'HR Administrator');
    }

    public function missingCodeSummary(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'summary' => $this->assetService->getMissingCodeSummary(),
        ]);
    }

    /**
     * Preview the next available generated code for a prefix, WITHOUT persisting.
     * Used by the Add form's "Generate" button to show the value before save.
     */
    public function previewNextCode(string $prefix): JsonResponse
    {
        $category = AssetCategory::tryFromPrefix($prefix);
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Prefix kategori tidak valid.'], 422);
        }

        return response()->json([
            'success' => true,
            'code'    => $this->assetService->generateCode($category),
        ]);
    }
}
