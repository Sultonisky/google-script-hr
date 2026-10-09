<?php

namespace App\Http\Controllers\HR;

use App\Exceptions\AttendanceOutsourcePushException;
use App\DTOs\OutsourceEmployeeData;
use App\Enums\SkDocumentType;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\OutsourceEmployeeRepositoryInterface;
use App\Services\AttendanceOutsourcePushService;
use App\Services\EmployeeDocumentArchiveService;
use App\Services\OutsourceContractService;
use App\Services\OutsourceXlsxImportService;
use App\Services\PdfGeneratorService;
use App\Services\RecruitmentService;
use App\Support\OutsourceEmployeeAttributeMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OutsourceController extends Controller
{
    private const TEXT_REGEX = '/^[\p{L}0-9 .,&()\-\/]+$/u';

    public function __construct(
        protected OutsourceEmployeeRepositoryInterface $outsourceRepo,
        protected OutsourceContractService $contractService,
        protected PdfGeneratorService $pdfService,
        protected AuditLogRepositoryInterface $auditRepo,
        protected EmployeeDocumentArchiveService $documentArchive
    ) {}

    public function index(Request $request): View
    {
        $perPage     = max(1, (int) $request->query('per_page', 10));
        $currentPage = max(1, (int) $request->query('page', 1));

        // Single source of truth: dedicated Outsource_Employees store
        $allOutsources = $this->outsourceRepo->getAll();

        // Stats — always from full dataset (not current page)
        $stats = ['total' => $allOutsources->count()];

        $searchFilter = $request->query('search');
        $vendorFilter = (string) $request->query('vendor', '');
        $vendorFilter = in_array($vendorFilter, config('hris.outsource.vendors', []), true) ? $vendorFilter : '';
        $filtered = $this->outsourceRepo->getAll(['search' => $searchFilter, 'vendor' => $vendorFilter]);

        $sortFilter = $request->query('sort', 'name_asc');
        $sortFilter = in_array($sortFilter, ['name_asc', 'name_desc'], true) ? $sortFilter : 'name_asc';

        $orderFilter = $request->query('order');
        $orderFilter = in_array($orderFilter, ['newest', 'oldest'], true) ? $orderFilter : '';

        $joinTimestamp = fn (OutsourceEmployeeData $e) => strtotime((string) ($e->mitoJoinDate ?? $e->contractStartDate ?? $e->createdAt ?? '1970-01-01')) ?: 0;
        if ($orderFilter === 'newest') {
            $filtered = $filtered->sortByDesc($joinTimestamp);
        } elseif ($orderFilter === 'oldest') {
            $filtered = $filtered->sortBy($joinTimestamp);
        } elseif ($sortFilter === 'name_desc') {
            $filtered = $filtered->sortByDesc(fn (OutsourceEmployeeData $e) => strtolower(trim($e->fullName ?? '')));
        } else {
            $filtered = $filtered->sortBy(fn (OutsourceEmployeeData $e) => strtolower(trim($e->fullName ?? '')));
        }

        $filtered = $filtered->values();
        $total = $filtered->count();

        // Manual pagination
        $offset     = ($currentPage - 1) * $perPage;
        $outsources = $filtered->slice($offset, $perPage)->values();

        // Full outsource list for Proses Kontrak modal search (all pages)
        $allOutsourcesForSearch = $allOutsources->values();

        return view('hr.outsource.index', compact(
            'outsources',
            'allOutsourcesForSearch',
            'stats',
            'total',
            'currentPage',
            'perPage',
            'searchFilter',
            'vendorFilter',
            'sortFilter',
            'orderFilter'
        ));
    }

    /**
     * Tambah karyawan outsource baru secara manual dari dashboard HR.
     *
     * POST /hr/outsource
     * Requires: can:manage_outsource
     */
    public function store(Request $request, AttendanceOutsourcePushService $attendancePush): JsonResponse
    {
        $validated = $this->validatePayload($request, false);

        if ($this->outsourceRepo->findByContact($validated['whatsappNumber'] ?? null, $validated['email'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'No WA atau email sudah terdaftar pada data outsource lain.',
            ], 422);
        }

        $data = new OutsourceEmployeeData(...$this->withoutUnmanageableCompensation($validated));
        $data->createdBy = $this->hrUserName();

        try {
            $created = $this->outsourceRepo->create($data);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data outsource. ' . $e->getMessage()], 422);
        }

        $this->auditRepo->log('Outsource', $created->outsourceId, 'created', 'Outsource ID', '-', $created->outsourceId, $this->hrUserName(), 'HR Dashboard');
        $attendancePush->pushCreated([$created]);

        return response()->json([
            'success' => true,
            'message' => 'Karyawan outsource berhasil ditambahkan.',
            'employee' => $this->present($created),
        ], 201);
    }

    public function pushToAttendance(Request $request, AttendanceOutsourcePushService $attendancePush): JsonResponse
    {
        $validated = $request->validate([
            'dry_run' => ['sometimes', 'boolean'],
        ]);
        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $people = $this->outsourceRepo->getAll()
            ->filter(fn (OutsourceEmployeeData $person) => filled($person->outsourceId) && filled($person->fullName))
            ->map(fn (OutsourceEmployeeData $person) => [
                'outsource_id' => strtoupper(trim((string) $person->outsourceId)),
                'full_name' => trim((string) $person->fullName),
            ])
            ->values()
            ->all();
        $totals = ['processed' => 0, 'created' => 0, 'skipped' => 0, 'conflict' => 0, 'would_create' => 0];
        $conflicts = [];
        $records = [];

        try {
            foreach (array_chunk($people, 100) as $batch) {
                $result = $attendancePush->push($batch, $dryRun);
                foreach ($totals as $key => $value) {
                    $totals[$key] += $result['meta'][$key];
                }
                foreach ($result['data'] as $index => $record) {
                    $record['full_name'] = $batch[$index]['full_name'];
                    $records[] = $record;
                    if ($record['status'] === 'conflict') {
                        $conflicts[] = $record['outsource_id'];
                    }
                }
            }
        } catch (AttendanceOutsourcePushException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'meta' => $totals,
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => $dryRun
                ? 'Preview sinkronisasi Outsource ke Attendance selesai.'
                : 'Sinkronisasi Outsource ke Attendance selesai.',
            'dry_run' => $dryRun,
            'data' => $records,
            'meta' => $totals,
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Import data outsource dari file Excel (sheet RAW DATA, kolom A–V).
     * Penanganan identik dengan `php artisan mito:outsource:import-xlsx`.
     *
     * POST /hr/outsource/import
     * Requires: can:manage_outsource
     */
    public function import(Request $request, OutsourceXlsxImportService $importer): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'extensions:xlsx', 'mimes:xlsx,zip'],
            'vendor' => ['required', 'string', Rule::in(config('hris.outsource.vendors', []))],
            'sheet' => ['nullable', 'string', 'max:100'],
            'dry_run' => ['nullable', 'boolean'],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
            'file.extensions' => 'File harus berformat .xlsx.',
            'file.mimes' => 'File harus berformat .xlsx.',
            'vendor.required' => 'Vendor wajib dipilih.',
            'vendor.in' => 'Vendor tidak valid.',
        ]);

        $dryRun = $request->boolean('dry_run');
        $sheet = trim((string) ($validated['sheet'] ?? '')) ?: 'RAW DATA';
        $user = $this->hrUserName();

        @set_time_limit(300);

        try {
            $result = $importer->import(
                $request->file('file')->getRealPath(),
                $sheet,
                $validated['vendor'],
                $dryRun,
                $user,
                Gate::allows('manage_outsource_compensation')
            );
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal membaca file. Pastikan file .xlsx valid dan tidak terproteksi password.'], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membaca file: ' . $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal membaca file. Pastikan file .xlsx valid dan tidak terproteksi password.'], 422);
        }

        if (!$dryRun) {
            $this->auditRepo->log(
                'Outsource',
                'IMPORT_XLSX',
                'imported',
                'Import XLSX',
                '-',
                "Vendor {$validated['vendor']}: dibuat {$result['created']}, diperbarui {$result['updated']}, error " . count($result['errors']),
                $user,
                'HR Dashboard'
            );
        }

        $ok = $result['errors'] === [];
        $message = $dryRun
            ? "Preview: {$result['read']} baris dibaca, {$result['created']} akan dibuat, {$result['updated']} akan diperbarui."
            : ($ok
                ? "Import selesai: {$result['created']} dibuat, {$result['updated']} diperbarui."
                : 'Import selesai dengan ' . count($result['errors']) . ' error.');

        return response()->json([
            'success' => $ok,
            'dry_run' => $dryRun,
            'message' => $message,
            'summary' => [
                'read' => $result['read'],
                'created' => $result['created'],
                'updated' => $result['updated'],
            ],
            'warnings' => $result['warnings'],
            'errors' => $result['errors'],
        ], $ok ? 200 : 422);
    }

    /**
     * Detail karyawan outsource untuk drawer.
     *
     * GET /hr/outsource/{id}/json
     * Requires: can:view_outsource
     */
    public function getJson(string $id): JsonResponse
    {
        $outsource = $this->outsourceRepo->findById($id);
        if (!$outsource) {
            return response()->json(['success' => false, 'error' => 'Karyawan outsource tidak ditemukan.'], 404);
        }

        try {
            $hiddenFields = Gate::allows('view_outsource_compensation') ? [] : $this->compensationHeaders();
            $auditLogs = $this->auditRepo->getLogs((string) $outsource->outsourceId)
                ->filter(fn ($log) => strtolower(trim($log['Entity Type'] ?? $log['entityType'] ?? '')) === 'outsource')
                ->reject(fn ($log) => in_array(trim((string) ($log['Field'] ?? $log['field'] ?? '')), $hiddenFields, true))
                ->take(20)
                ->values();
        } catch (\Throwable $e) {
            report($e);
            $auditLogs = collect();
        }

        return response()->json([
            'success' => true,
            'employee' => $this->present($outsource),
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Edit karyawan outsource.
     *
     * PUT /hr/outsource/{id}
     * Requires: can:manage_outsource
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $existing = $this->outsourceRepo->findById($id);
        if (!$existing) {
            return response()->json(['success' => false, 'message' => 'Karyawan outsource tidak ditemukan.'], 404);
        }

        $validated = $this->withoutUnmanageableCompensation($this->validatePayload($request, true));

        $duplicate = $this->outsourceRepo->findByContact($validated['whatsappNumber'] ?? null, $validated['email'] ?? null);
        if ($duplicate && $duplicate->outsourceId !== $existing->outsourceId) {
            return response()->json([
                'success' => false,
                'message' => 'No WA atau email sudah terdaftar pada data outsource lain.',
            ], 422);
        }

        $changes = [];
        foreach ($validated as $property => $value) {
            if ($existing->{$property} != $value) {
                $changes[$property] = ['old' => $existing->{$property}, 'new' => $value];
            }
        }

        if ($changes === []) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada perubahan data.',
                'employee' => $this->present($existing),
            ]);
        }

        if (!$this->outsourceRepo->update($id, array_map(fn (array $c) => $c['new'], $changes))) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan perubahan data outsource.'], 422);
        }

        foreach ($changes as $property => $change) {
            $this->auditRepo->log(
                'Outsource',
                $existing->outsourceId,
                'updated',
                OutsourceEmployeeAttributeMap::FIELDS[$property][1],
                $change['old'],
                $change['new'],
                $this->hrUserName(),
                'HR Dashboard'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan outsource berhasil diperbarui.',
            'employee' => ($fresh = $this->outsourceRepo->findById($id)) ? $this->present($fresh) : null,
        ]);
    }

    /**
     * Allocate nomor kontrak + siapkan 2 PDF (kontrak + surat pernyataan).
     * Returns JSON with download URLs — frontend download keduanya.
     *
     * POST /hr/outsource/kontrak-pkwt-tad
     */
    public function generateKontrakPkwtTad(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id'   => 'required|string|max:64',
            'perusahaan'    => 'required|string|max:255',
            'beralamat_di'  => 'required|string|max:500',
            'mulai_tanggal' => 'required|date',
            'pendidikan'    => 'required|string|max:100',
            'contract_duration' => 'nullable|string|max:50',
        ]);

        $outsource = $this->outsourceRepo->findById($validated['employee_id']);
        if (!$outsource) {
            return response()->json(['success' => false, 'message' => 'Data karyawan outsource tidak ditemukan.'], 404);
        }

        $employee = $outsource->toEmployeeData();
        $alloc = $this->contractService->allocateContractNumber($employee, null, $this->hrUserName());
        $now   = now()->timezone('Asia/Jakarta');

        $extraData = [
            'contract_number'   => $alloc['contract_number'],
            'perusahaan'        => $validated['perusahaan'],
            'beralamat_di'      => $validated['beralamat_di'],
            'mulai_tanggal'     => $validated['mulai_tanggal'],
            'pendidikan'        => $validated['pendidikan'],
            'join_date'         => $validated['mulai_tanggal'],
            'doc_date'          => $now->format('Y-m-d'),
            'contract_duration' => $validated['contract_duration'] ?? '12 Bulan',
            'position'          => $outsource->jobTitle,
        ];

        $token = (string) Str::uuid();
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $outsource->fullName ?? 'Outsource');
        Cache::put('osc_pkwt_tad_' . $token, [
            'employee_id' => $outsource->outsourceId,
            'extra_data'  => $extraData,
            'safe_name'   => $safeName,
        ], now()->addMinutes(15));

        // Archive now: the download token expires after 15 minutes.
        $this->documentArchive->capture(
            $outsource->outsourceId,
            SkDocumentType::PKWT,
            $alloc['contract_number'],
            fn () => $this->pdfService->generateKontrakPkwtTadPdf($employee, $extraData)->output(),
            "PKWT_TAD_{$safeName}_{$outsource->outsourceId}.pdf",
            $this->hrUserName()
        );

        $this->auditRepo->log(
            'Outsource',
            $outsource->outsourceId,
            'generated',
            'contract_pkwt_tad',
            null,
            $alloc['contract_number'],
            $this->hrUserName(),
            'Export'
        );

        $baseQuery = ['token' => $token];

        return response()->json([
            'success'         => true,
            'contract_number' => $alloc['contract_number'],
            'message'         => 'Kontrak PKWT TAD siap diunduh.',
            'pdf_urls'        => [
                'kontrak'    => route('hr.outsource.kontrak-pkwt-tad.download', $baseQuery + ['type' => 'kontrak']),
                'pernyataan' => route('hr.outsource.kontrak-pkwt-tad.download', $baseQuery + ['type' => 'pernyataan']),
            ],
        ]);
    }

    /**
     * Download satu PDF (kontrak | pernyataan) via token dari generate.
     *
     * GET /hr/outsource/kontrak-pkwt-tad/download?token=...&type=kontrak|pernyataan
     */
    public function downloadKontrakPkwtTad(Request $request): Response
    {
        $token = trim((string) $request->query('token', ''));
        $type  = strtolower(trim((string) $request->query('type', 'kontrak')));

        if ($token === '' || !in_array($type, ['kontrak', 'pernyataan'], true)) {
            abort(404, 'Parameter download tidak valid.');
        }

        $payload = Cache::get('osc_pkwt_tad_' . $token);
        if (!$payload || empty($payload['employee_id'])) {
            abort(410, 'Link download kedaluwarsa. Generate ulang dari form.');
        }

        $outsource = $this->outsourceRepo->findById($payload['employee_id']);
        if (!$outsource) {
            abort(404, 'Data karyawan tidak ditemukan.');
        }

        $employee  = $outsource->toEmployeeData();
        $extraData = $payload['extra_data'] ?? [];
        $safeName  = $payload['safe_name'] ?? 'Outsource';
        $empId     = $outsource->outsourceId;

        if ($type === 'pernyataan') {
            $pdf = $this->pdfService->generateSuratPernyataanOutsourcePdf($employee, $extraData);
            $filename = "Surat_Pernyataan_{$safeName}_{$empId}.pdf";
        } else {
            $pdf = $this->pdfService->generateKontrakPkwtTadPdf($employee, $extraData);
            $filename = "PKWT_TAD_{$safeName}_{$empId}.pdf";
        }

        return $pdf->download($filename);
    }

    /**
     * Validate HR add/edit payload (keys = OutsourceEmployeeData properties).
     * On update only the submitted keys are returned.
     *
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    private function present(OutsourceEmployeeData $outsource): array
    {
        $data = $outsource->toArray();
        if (Gate::denies('view_outsource_compensation')) {
            foreach (OutsourceEmployeeAttributeMap::COMPENSATION_FIELDS as $property) {
                unset($data[$property]);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function withoutUnmanageableCompensation(array $values): array
    {
        return Gate::allows('manage_outsource_compensation')
            ? $values
            : array_diff_key($values, array_flip(OutsourceEmployeeAttributeMap::COMPENSATION_FIELDS));
    }

    /**
     * @return list<string>
     */
    private function compensationHeaders(): array
    {
        return array_map(
            fn (string $property) => OutsourceEmployeeAttributeMap::FIELDS[$property][1],
            OutsourceEmployeeAttributeMap::COMPENSATION_FIELDS
        );
    }

    private function validatePayload(Request $request, bool $partial): array
    {
        $request->merge($this->sanitizeInput($request->all()));

        $required = $partial ? ['sometimes', 'required'] : ['required'];
        $rules = [
            'fullName'          => [...$required, 'string', 'max:255', 'regex:/^[\p{L}\' .]+$/u'],
            'vendor'            => [...$required, 'string', Rule::in(config('hris.outsource.vendors', []))],
            'citizenIdAddress'  => ['nullable', 'string', 'max:500'],
            'birthDate'         => ['nullable', 'date'],
            'birthPlace'        => ['nullable', 'string', 'max:120'],
            'lastEducation'     => ['nullable', 'string', 'max:64'],
            'whatsappNumber'    => ['nullable', 'string', 'regex:/^\+62\d{8,13}$/'],
            'email'             => ['nullable', 'email:filter', 'max:255'],
            'jobTitle'          => ['nullable', 'string', 'max:255', 'regex:' . self::TEXT_REGEX],
            'workLocation'      => ['nullable', 'string', 'max:255', 'regex:' . self::TEXT_REGEX],
            'workCity'          => ['nullable', 'string', 'max:120', 'regex:' . self::TEXT_REGEX],
            'bankAccount'       => ['nullable', 'digits_between:8,20'],
            'mitoJoinDate'      => ['nullable', 'date'],
            'contractStartDate' => ['nullable', 'date'],
            'contractEndDate'   => ['nullable', 'date'],
            'costCenter'        => ['nullable', 'string', 'max:120', 'regex:' . self::TEXT_REGEX],
            'entity'            => ['nullable', 'string', 'max:255'],
            'payrollScheme'     => ['nullable', 'string', 'max:32'],
            'umkAmount'         => ['nullable', 'numeric', 'min:0'],
            'basicSalary'       => ['nullable', 'numeric', 'min:0'],
            'incentiveAmount'   => ['nullable', 'numeric', 'min:0'],
            'remarks'           => ['nullable', 'string', 'max:1000'],
        ];

        $request->validate($rules, [
            'fullName.required' => 'Nama lengkap wajib diisi.',
            'fullName.regex' => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, dan apostrof.',
            'vendor.required' => 'Vendor wajib dipilih.',
            'vendor.in' => 'Vendor harus Damarindo atau StaffInc.',
            'whatsappNumber.regex' => 'No WA tidak valid. Gunakan format 08xxxxxxxxxx.',
            'email.email' => 'Format email tidak valid.',
            'bankAccount.digits_between' => 'No rekening BCA harus 8–20 digit angka.',
            'jobTitle.regex' => 'Nama jabatan hanya boleh huruf, angka, spasi, dan tanda . , & ( ) - /',
            'workLocation.regex' => 'Lokasi kerja hanya boleh huruf, angka, spasi, dan tanda . , & ( ) - /',
            'workCity.regex' => 'Kota lokasi kerja hanya boleh huruf, angka, spasi, dan tanda . , & ( ) - /',
            'costCenter.regex' => 'Cabang (cost center) hanya boleh huruf, angka, spasi, dan tanda . , & ( ) - /',
        ]);

        $values = [];
        foreach (array_keys($rules) as $property) {
            if ($partial && !$request->exists($property)) {
                continue;
            }
            $values[$property] = OutsourceEmployeeAttributeMap::normalize($property, $request->input($property));
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function sanitizeInput(array $input): array
    {
        $clean = [];
        foreach ($input as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            $value = in_array($key, ['citizenIdAddress', 'remarks'], true)
                ? trim($value)
                : trim(preg_replace('/\s+/u', ' ', $value) ?? '');
            $clean[$key] = match ($key) {
                'whatsappNumber' => $value === '' ? '' : (string) RecruitmentService::normalizePhone(preg_replace('/\D+/', '', $value)),
                'bankAccount' => preg_replace('/\D+/', '', $value),
                'email' => strtolower($value),
                'umkAmount', 'basicSalary', 'incentiveAmount' => $value === '' ? '' : (string) OutsourceEmployeeAttributeMap::parseAmount($value),
                default => $value,
            };
        }

        return $clean;
    }
}
