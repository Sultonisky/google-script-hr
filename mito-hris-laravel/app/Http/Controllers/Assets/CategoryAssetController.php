<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Models\CategoryAsset;
use App\Models\CategoryAssetAssignment;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

abstract class CategoryAssetController extends Controller
{
    protected string $modelClass;
    protected string $category;
    protected string $routeName;
    protected string $viewName;
    protected string $codePrefix;
    protected array $details = [];
    protected array $listFieldNames = [];
    protected EmployeeRepositoryInterface $employeeRepository;
    protected AuditLogRepositoryInterface $auditRepository;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepository,
        AuditLogRepositoryInterface $auditRepository,
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->auditRepository = $auditRepository;
    }

    abstract protected function detailRules(): array;

    protected function commonFields(): array
    {
        return [
            ['name' => 'asset_code', 'label' => 'Kode Aset', 'type' => 'text'],
            ['name' => 'name', 'label' => 'Nama Aset', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
            ['name' => 'purchase_date', 'label' => 'Tanggal Pembelian', 'type' => 'date'],
            ['name' => 'purchase_price', 'label' => 'Harga Pembelian (Rp)', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'condition_status', 'label' => 'Kondisi', 'type' => 'select', 'options' => array_column(AssetCondition::cases(), 'value')],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => array_column(AssetStatus::cases(), 'value')],
            ['name' => 'location', 'label' => 'Lokasi', 'type' => 'text'],
            ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
        ];
    }

    protected function fields(): array
    {
        return array_merge($this->commonFields(), $this->details);
    }

    protected function commonRules(?int $ignoreId = null): array
    {
        $modelClass = $this->modelClass;
        $table = (new $modelClass())->getTable();
        $codeRule = Rule::unique($table, 'asset_code');
        if ($ignoreId !== null) {
            $codeRule->ignore($ignoreId);
        }

        return [
            'asset_code' => ['nullable', 'string', 'max:50', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'condition_status' => ['required', Rule::enum(AssetCondition::class)],
            'status' => ['required', Rule::enum(AssetStatus::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function index(Request $request): View
    {
        $modelClass = $this->modelClass;
        $query = $modelClass::query()
            ->with([
                'activeAssignment',
                'assignments' => fn ($assignments) => $assignments->orderByDesc('assigned_date'),
            ])
            ->search($request->query('search'));
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('condition_status')) {
            $query->where('condition_status', $request->query('condition_status'));
        }

        $assets = $query->latest('id')->paginate(15)->withQueryString();
        $missingCodeCount = $modelClass::query()->where(fn ($query) => $query
            ->whereNull('asset_code')
            ->orWhere('asset_code', ''))->count();

        return view($this->viewName . '.index', [
            'assets' => $assets,
            'title' => $this->category,
            'fields' => $this->fields(),
            'listFields' => $this->listFields(),
            'routeName' => $this->routeName,
            'permissionPrefix' => $this->permissionPrefix(),
            'missingCodeCount' => $missingCodeCount,
            'employeeLookupUrl' => route('assets.portal.employees.lookup'),
        ]);
    }

    public function create(): View
    {
        return $this->formView();
    }

    public function edit(int $id): View
    {
        $modelClass = $this->modelClass;
        return $this->formView($modelClass::query()->findOrFail($id));
    }

    protected function listFields(): array
    {
        $fields = $this->fields();
        if ($this->listFieldNames === []) {
            return array_values(array_filter(
                $fields,
                fn (array $field): bool => !in_array($field['name'], [
                    'asset_code',
                    'name',
                    'description',
                    'purchase_date',
                    'purchase_price',
                    'condition_status',
                    'status',
                    'location',
                    'notes',
                ], true)
            ));
        }

        return array_values(array_filter(
            $fields,
            fn (array $field): bool => in_array($field['name'], $this->listFieldNames, true)
        ));
    }

    public function show(int $id): View
    {
        $modelClass = $this->modelClass;
        $asset = $modelClass::query()
            ->with([
                'activeAssignment',
                'assignments' => fn ($assignments) => $assignments->orderByDesc('assigned_date'),
            ])
            ->findOrFail($id);

        return view('hr.assets.categories.shared.show', [
            'asset' => $asset,
            'title' => $this->category,
            'fields' => $this->fields(),
            'routeName' => $this->routeName,
            'permissionPrefix' => $this->permissionPrefix(),
            'assets' => collect([$asset]),
            'employeeLookupUrl' => route('assets.portal.employees.lookup'),
        ]);
    }

    private function formView(?CategoryAsset $asset = null): View
    {
        return view($this->viewName . '.form', [
            'asset' => $asset,
            'title' => $this->category,
            'fields' => $this->fields(),
            'routeName' => $this->routeName,
            'permissionPrefix' => $this->permissionPrefix(),
        ]);
    }

    protected function permissionPrefix(): string
    {
        return 'assets.' . strtolower($this->category);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge($this->commonRules(), $this->detailRules()));
        $data['created_by'] = $this->actorEmail();

        $modelClass = $this->modelClass;
        $asset = $modelClass::query()->create($data);
        if (blank($asset->asset_code)) {
            $asset->forceFill(['asset_code' => $this->nextCode()])->save();
        }

        $this->auditRepository->log('Asset', (string) $asset->id, 'created', null, null, $asset->name, $this->actorEmail(), $this->category . ' Assets');

        return redirect()->route($this->routeName . '.index')->with('success', $this->category . ' berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $modelClass = $this->modelClass;
        $asset = $modelClass::query()->findOrFail($id);
        $data = $request->validate(array_merge($this->commonRules($asset->id), $this->detailRules()));
        $before = $asset->getAttributes();
        $asset->update($data);

        foreach ($data as $field => $value) {
            if ((string) ($before[$field] ?? '') !== (string) ($value ?? '')) {
                $this->auditRepository->log('Asset', (string) $asset->id, 'updated', $field, $before[$field] ?? null, $value, $this->actorEmail(), $this->category . ' Assets');
            }
        }

        return redirect()->route($this->routeName . '.index')->with('success', $this->category . ' berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $modelClass = $this->modelClass;
        $asset = $modelClass::query()->findOrFail($id);
        if ($asset->activeAssignment()->exists()) {
            return back()->with('error', 'Aset masih ditugaskan. Kembalikan aset sebelum disposisi.');
        }
        $previousStatus = $asset->status?->value ?? (string) $asset->status;
        $asset->update(['status' => AssetStatus::DISPOSED]);
        $this->auditRepository->log('Asset', (string) $asset->id, 'disposed', 'status', $previousStatus, AssetStatus::DISPOSED->value, $this->actorEmail(), $this->category . ' Assets');

        return redirect()->route($this->routeName . '.index')->with('success', $this->category . ' berhasil didisposisi.');
    }

    public function assign(Request $request, int $id): RedirectResponse
    {
    try {
        $data = $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
            'assigned_date' => ['required', 'date'],
            'assignment_notes' => ['nullable', 'string', 'max:1000'],
        ]);
    } catch (ValidationException $exception) {
        return $this->assignmentErrorRedirect($request, $id, $exception->errors());
    }

    $modelClass = $this->modelClass;
    $asset = $modelClass::query()->findOrFail($id);
    try {
        $employee = $this->employeeRepository->findById($data['employee_id']);
    } catch (Throwable $exception) {
        Log::warning('Employee lookup failed during category asset assignment', [
            'employee_id' => $data['employee_id'],
            'category' => $this->category,
            'error' => $exception->getMessage(),
        ]);
        return $this->assignmentErrorRedirect($request, $id, [
            'employee_id' => ['Gagal memverifikasi karyawan. Coba kembali.'],
        ]);
    }

    if (!$employee) {
        return $this->assignmentErrorRedirect($request, $id, [
            'employee_id' => ['Employee ID tidak dikenal.'],
        ]);
    }

    try {
        DB::transaction(function () use ($asset, $data, $employee, $modelClass): void {
            $lockedAsset = $modelClass::query()->lockForUpdate()->findOrFail($asset->id);
            if ($lockedAsset->status !== AssetStatus::AVAILABLE) {
                throw ValidationException::withMessages(['asset' => 'Aset hanya dapat ditugaskan jika statusnya Available.']);
            }
            if ($lockedAsset->activeAssignment()->exists()) {
                throw ValidationException::withMessages(['asset' => 'Aset sudah memiliki penugasan aktif.']);
            }

            $lockedAsset->assignments()->create([
                'employee_id' => $data['employee_id'],
                'employee_name' => $employee->fullName ?? $data['employee_id'],
                'assigned_date' => $data['assigned_date'],
                'assigned_by' => $this->actorEmail(),
                'assignment_notes' => $data['assignment_notes'] ?? null,
                'status' => 'active',
            ]);
            $lockedAsset->update(['status' => AssetStatus::ASSIGNED]);
        });
    } catch (ValidationException $exception) {
        return $this->assignmentErrorRedirect($request, $id, $exception->errors());
    }

    $this->auditRepository->log('Asset', (string) $asset->id, 'assigned', 'status', AssetStatus::AVAILABLE->value, AssetStatus::ASSIGNED->value . ' → ' . $data['employee_id'], $this->actorEmail(), $this->category . ' Assets');

    return redirect()->route($this->routeName . '.index')->with('success', 'Aset berhasil ditugaskan.');
    }

    private function assignmentErrorRedirect(Request $request, int $id, array $errors): RedirectResponse
    {
        return redirect()->route($this->routeName . '.index')
            ->withInput($request->except('_token'))
            ->withErrors($errors)
            ->with('open_asset_assign_modal', $id);
    }

    public function returnAsset(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'return_date' => ['required', 'date'],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $modelClass = $this->modelClass;
        $asset = $modelClass::query()->findOrFail($id);
        $modelClass = $this->modelClass;
        $assignment = DB::transaction(function () use ($asset, $data, $modelClass): CategoryAssetAssignment {
            $lockedAsset = $modelClass::query()->lockForUpdate()->findOrFail($asset->id);
            $assignment = $lockedAsset->activeAssignment()->first();
            if (!$assignment) {
                throw ValidationException::withMessages(['asset' => 'Aset tidak memiliki penugasan aktif.']);
            }

            $assignment->update([
                'return_date' => $data['return_date'],
                'return_by' => $this->actorEmail(),
                'return_notes' => $data['return_notes'] ?? null,
                'status' => 'returned',
            ]);
            $lockedAsset->update(['status' => AssetStatus::AVAILABLE]);

            return $assignment;
        });

        $this->auditRepository->log('Asset', (string) $asset->id, 'returned', 'status', AssetStatus::ASSIGNED->value, AssetStatus::AVAILABLE->value . ' ← ' . $assignment->employee_id, $this->actorEmail(), $this->category . ' Assets');

        return redirect()->route($this->routeName . '.index')->with('success', 'Aset berhasil dikembalikan.');
    }

    public function generateCode(int $id): RedirectResponse
    {
        $modelClass = $this->modelClass;
        $asset = $modelClass::query()->findOrFail($id);
        if (filled($asset->asset_code)) {
            return back()->with('error', 'Aset sudah memiliki kode: ' . $asset->asset_code);
        }

        $code = $this->nextCode();
        $asset->update(['asset_code' => $code]);
        $this->auditRepository->log('Asset', (string) $asset->id, 'code_generated', 'asset_code', null, $code, $this->actorEmail(), $this->category . ' Assets');

        return back()->with('success', 'Kode aset ' . $code . ' berhasil dibuat.');
    }

    public function generateBulkCodes(): RedirectResponse
    {
        $modelClass = $this->modelClass;
        $assets = $modelClass::query()->where(fn ($query) => $query->whereNull('asset_code')->orWhere('asset_code', ''))->orderBy('id')->get();
        $count = 0;
        foreach ($assets as $asset) {
            $asset->update(['asset_code' => $this->nextCode()]);
            $count++;
        }

        return back()->with('success', $count . ' kode aset ' . strtolower($this->category) . ' berhasil dibuat.');
    }

    private function nextCode(): string
    {
        $modelClass = $this->modelClass;
        $prefix = $this->codePrefix . '-';
        $codes = $modelClass::query()->where('asset_code', 'like', $prefix . '%')->pluck('asset_code');
        $max = 0;

        foreach ($codes as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return sprintf('%s%05d', $prefix, $max + 1);
    }

    private function actorEmail(): string
    {
        return (string) session('asset_auth.email', 'HR Administrator');
    }
}
