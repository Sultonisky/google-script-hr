<?php

namespace App\Http\Controllers\HR;

use App\DTOs\MprData;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreMprRequest;
use App\Http\Requests\HR\UpdateMprRequest;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\MarkdownRenderer;
use App\Services\MprPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MprController extends Controller
{
    protected MprRepositoryInterface $mprRepo;
    protected MprPdfService $pdfService;
    protected MarkdownRenderer $markdownRenderer;
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(
        MprRepositoryInterface $mprRepo,
        MprPdfService $pdfService,
        MarkdownRenderer $markdownRenderer,
        AuditLogRepositoryInterface $auditRepo
    ) {
        $this->mprRepo = $mprRepo;
        $this->pdfService = $pdfService;
        $this->markdownRenderer = $markdownRenderer;
        $this->auditRepo = $auditRepo;
    }

    /**
     * Display list of MPR records (for HR) or Manpower's own requests + submission form.
     */
    public function index(Request $request): View
    {
        $user = session('hr_user', []);
        $role = $user['role'] ?? 'Viewer';
        $isManpower = strtolower(trim($role)) === 'manpower';
        $isManager = $isManpower;

        $search = $request->query('search', '');
        $dept = $request->query('department', '');
        $status = $request->query('status', '');
        $submitBy = $request->query('submit_by', '');
        $perPage = (int) $request->query('per_page', 10);

        $filters = array_filter([
            'search'     => $search,
            'department' => $dept,
            'status'     => $status,
            'submit_by'  => $submitBy,
        ]);

        if ($isManager) {
            $mprs = $this->mprRepo->getAllForManager($user['email'] ?? '', $filters);
        } else {
            $mprs = $this->mprRepo->getAll($filters);
        }

        $mprs = $mprs->sortByDesc(fn($mpr) => $mpr->requestDate ?? $mpr->createdAt ?? '')->values();

        // Aggregate statistics for dashboard metrics
        $allMprs = $isManpower ? $mprs : $this->mprRepo->getAll();
        $submitByOptions = $allMprs->map(function ($mpr) {
            return trim($mpr->requestorName ?: $mpr->requestorEmail ?: $mpr->createdBy ?: '');
        })
            ->filter(fn($name) => filled($name))
            ->unique()
            ->sort()
            ->values();
        $thisMonthStr = now()->timezone('Asia/Jakarta')->format('Y-m');

        $stats = [
            'total'           => $allMprs->count(),
            'this_month'      => $allMprs->filter(fn($m) => str_starts_with($m->requestDate ?? $m->createdAt ?? '', $thisMonthStr))->count(),
            'total_quantity'  => $allMprs->sum(fn($m) => (int) ($m->quantity ?? 1)),
            'submitted'       => $allMprs->filter(fn($m) => strtolower($m->status ?? '') === 'submitted')->count(),
        ];

        // Pagination
        $total = $mprs->count();
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedMprs = $mprs->slice($offset, $perPage)->values();
        $lastPage = max(1, (int) ceil($total / $perPage));

        // MPR organization catalog is shared by the form and validation rules.
        $departmentDivisionMap = config('hris.mpr_department_divisions', []);
        $departments = array_keys($departmentDivisionMap);

        $jobLevels = ['Associate', 'Staff', 'Senior Staff', 'Supervisor', 'Team Lead', 'Manager', 'General Manager', 'Director'];

        $workLocations = ['Head Office (HO)', 'Depo Jakarta', 'Depo Bandung', 'Depo Surabaya', 'Pabrik'];

        $employmentTypes = ['Permanent (PKWTT)', 'Contract (PKWT)', 'Outsource', 'Intern', 'Freelance / Project'];

        // All available entity names (kode entitas) untuk HR/Super Admin
        $entityOptions = [
            'MSI' => 'PT Mahakarya Sukses Indonesia (MSI)',
            'SPI' => 'PT Stein Perkasa Internasional (SPI)',
            'PII' => 'PT Perkasa Injeksi Indonesia (PII)',
            'MEP' => 'PT Mitra Elektro Perkasa (MEP)',
        ];

        // Entity yang diizinkan untuk user yang sedang login
        // - Manpower: hanya entity yang menjadi assignment-nya (dari session)
        // - HR/Super Admin: semua entity tersedia
        $userEntities = $user['entities'] ?? [];
        $allowedEntities = $isManpower
            ? array_filter($entityOptions, fn($label, $code) => in_array($code, $userEntities, true), ARRAY_FILTER_USE_BOTH)
            : $entityOptions;

        $reasons = [
            'Penambahan Karyawan Baru (Business Expansion)',
            'Penggantian Karyawan Resign / Mutasi / Demosi',
            'Beban Kerja Musiman / Peak Season',
            'Proyek Khusus',
            'Kebutuhan Restrukturisasi Organisasi',
        ];

        return view('hr.mpr.index', compact(
            'isManager',
            'user',
            'role',
            'paginatedMprs',
            'total',
            'currentPage',
            'lastPage',
            'perPage',
            'stats',
            'departments',
            'departmentDivisionMap',
            'jobLevels',
            'workLocations',
            'employmentTypes',
            'entityOptions',
            'allowedEntities',
            'reasons',
            'search',
            'dept',
            'status',
            'submitBy',
            'submitByOptions'
        ));
    }

    /**
     * Store new MPR request and auto-generate PDF.
     *
     * Security:
     * - Entity divalidasi server-side: selected entity HARUS ada di daftar entity requestor.
     * - Branch selalu diambil dari session authenticated requestor, TIDAK dari request body.
     * - Identitas requestor (nama, email) selalu dari session untuk role Manpower.
     */
    public function store(StoreMprRequest $request): JsonResponse|RedirectResponse
    {
        $user    = session('hr_user', []);
        $role    = $user['role'] ?? 'Viewer';
        $isManager = strtolower(trim($role)) === 'manpower';

        $validated = $request->validated();

        // ==============================================================
        // ENTITY MAP: kode entitas → nama perusahaan lengkap
        // ==============================================================
        $entityNameMap = [
            'MSI' => 'PT MAHAKARYA SUKSES INDONESIA',
            'SPI' => 'PT STEIN PERKASA INTERNASIONAL',
            'PII' => 'PT PERKASA INJEKSI INDONESIA',
            'MEP' => 'PT MITRA ELEKTRO PERKASA',
        ];

        // ==============================================================
        // RESOLVE REQUESTOR IDENTITY & ENTITY/BRANCH
        // ==============================================================
        if ($isManager) {
            // Identitas requestor SELALU dari session — tidak boleh dari request body
            $requestorName  = $user['fullName'] ?? $user['name'] ?? 'Manpower';
            $requestorEmail = $user['email'] ?? '';
            $createdBy      = $user['email'] ?? 'Manpower';

            // Entity assignment dari session (sudah di-normalize saat login)
            $userEntities = $user['entities'] ?? [];

            // Selected entity dari request body (input user)
            $selectedEntity = trim($validated['entity'] ?? '');

            // ==============================================================
            // BACKEND AUTHORIZATION: selected entity HARUS ada di assignment
            // Tolak jika Manager mencoba entity yang bukan miliknya
            // ==============================================================
            if (empty($selectedEntity) || !in_array($selectedEntity, $userEntities, true)) {
                $allowed = implode(', ', $userEntities);
                $errMsg  = empty($userEntities)
                    ? 'Akun Anda belum memiliki entitas yang di-assign. Hubungi administrator.'
                    : "Entitas '{$selectedEntity}' tidak dalam daftar entitas Anda. Entitas yang diizinkan: {$allowed}.";

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errMsg], 403);
                }
                return redirect()->back()->withInput()->with('error', $errMsg);
            }

            // Branch SELALU dari authenticated requestor — tidak bisa dimanipulasi
            $branch = trim($user['branch'] ?? '');

            // Resolve nama perusahaan dari kode entitas
            $entityFullName = $entityNameMap[$selectedEntity] ?? $selectedEntity;
        } else {
            // Admin / Super Admin: bisa mengisi atas nama Manpower lain
            $requestorName  = !empty($validated['manager_name'])  ? $validated['manager_name']  : ($user['fullName'] ?? 'Admin');
            $requestorEmail = !empty($validated['manager_email']) ? $validated['manager_email'] : ($user['email'] ?? '');
            $createdBy      = $user['email'] ?? 'HR Team';

            // Untuk HR: entity bisa dari field 'entity' atau fallback ke 'company' (form lama)
            $rawEntity = !empty($validated['entity']) ? $validated['entity'] : ($validated['company'] ?? '');
            $selectedEntity = trim($rawEntity);

            // Resolve ke nama penuh jika kode entitas digunakan
            $entityFullName = $entityNameMap[$selectedEntity] ?? $selectedEntity;
            if (empty($entityFullName)) {
                $entityFullName = 'PT MAHAKARYA SUKSES INDONESIA';
                $selectedEntity = 'MSI';
            }

            // Branch: HR tidak punya branch sendiri — kosong atau dari requestor bila mengisi nama manager
            $branch = trim($user['branch'] ?? '');
        }

        $now = now()->timezone('Asia/Jakarta');
        $mprData = new MprData(
            requestDate: $now->format('Y-m-d'),
            requestorName: $requestorName,
            requestorEmail: $requestorEmail,
            entity: $selectedEntity,
            branch: $branch,
            department: $validated['department'],
            division: $validated['division'],
            position: $validated['position'],
            jobLevel: $validated['job_level'],
            workLocation: $validated['work_location'],
            employmentType: $validated['employment_type'],
            quantity: (int) ($validated['quantity'] ?? 1),
            expectedJoinDate: $validated['expected_join_date'],
            reason: $validated['reason'],
            replacementFor: $validated['replacement_for'] ?? null,
            jobDescription: $validated['job_description'] ?? null,
            requirements: $validated['requirements'] ?? null,
            notes: $validated['notes'] ?? null,
            status: 'Submitted',
            createdBy: $createdBy,
        );

        try {
            $savedMpr = $this->mprRepo->create($mprData);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan MPR: ' . $e->getMessage());
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan data MPR ke sistem: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data MPR: ' . $e->getMessage());
        }

        $this->auditRepo->log(
            'MPR',
            $savedMpr->mprNumber,
            'created',
            null,
            null,
            $savedMpr->toArray(),
            $user['email'] ?? 'Manpower',
            $isManager ? 'Public' : 'Dashboard'
        );

        // Prepare PDF URL
        $pdfUrl = route('hr.mpr.pdf', ['id' => $savedMpr->mprNumber]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'    => true,
                'message'    => "Pengajuan Manpower Request (MPR) berhasil dibuat dengan nomor {$savedMpr->mprNumber}.",
                'mpr_id'     => $savedMpr->mprNumber,
                'mpr_number' => $savedMpr->mprNumber,
                'pdf_url'    => $pdfUrl,
                'mpr'        => $savedMpr->toArray(),
            ], 201);
        }

        return redirect()->route('hr.mpr.index')
            ->with('success', "Pengajuan MPR ({$savedMpr->mprNumber}) berhasil disimpan.")
            ->with('mpr_pdf_url', $pdfUrl);
    }

    /**
     * Get MPR detail as JSON.
     */
    public function getJson(string $id): JsonResponse
    {
        $mpr = $this->mprRepo->findByMprNumber($id);

        if (!$mpr) {
            return response()->json([
                'success' => false,
                'error'   => "Data MPR dengan nomor '{$id}' tidak ditemukan.",
            ], 404);
        }

        // Check ownership authorization for Manager role (IDOR Protection)
        $user = session('hr_user', []);
        $role = $user['role'] ?? 'Viewer';

        if ($role === 'Manpower') {
            $userEmail  = strtolower(trim($user['email'] ?? ''));
            $mprEmail   = strtolower(trim($mpr->requestorEmail ?? ''));
            $mprCreator = strtolower(trim($mpr->createdBy ?? ''));

            if ($mprEmail !== $userEmail && $mprCreator !== $userEmail) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Anda tidak memiliki akses ke data MPR ini.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'mpr'     => array_merge($mpr->toArray(), [
                'requirements_html'    => $this->markdownRenderer->render($mpr->requirements),
                'job_description_html' => $this->markdownRenderer->render($mpr->jobDescription),
                'notes_html'           => $this->markdownRenderer->render($mpr->notes),
            ]),
        ]);
    }

    /** Update editable MPR fields while preserving identity and workflow metadata. */
    public function update(UpdateMprRequest $request, string $id): JsonResponse
    {
        Gate::authorize('update_mpr');
        $existing = $this->mprRepo->findByMprNumber($id);

        if (!$existing) {
            return response()->json(['success' => false, 'message' => 'Data MPR tidak ditemukan.'], 404);
        }

        $validated = $request->validated();
        $updated = new MprData(
            mprNumber: $existing->mprNumber,
            requestDate: $existing->requestDate,
            requestorName: $existing->requestorName,
            requestorEmail: $existing->requestorEmail,
            entity: $existing->entity,
            branch: $existing->branch,
            department: $validated['department'],
            division: $validated['division'],
            position: $validated['position'],
            jobLevel: $validated['job_level'],
            workLocation: $validated['work_location'],
            employmentType: $validated['employment_type'],
            quantity: (int) $validated['quantity'],
            expectedJoinDate: $validated['expected_join_date'],
            reason: $validated['reason'],
            replacementFor: $validated['replacement_for'] ?? null,
            jobDescription: $validated['job_description'] ?? null,
            requirements: $validated['requirements'] ?? null,
            notes: $validated['notes'] ?? null,
            status: $existing->status,
            createdBy: $existing->createdBy,
            createdAt: $existing->createdAt,
            updatedAt: now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        );

        try {
            $this->mprRepo->update($existing->mprNumber ?? $id, $updated);
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui MPR: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui MPR.'], 500);
        }

        $actor = session('hr_user.email', session('hr_user.fullName', 'HR Administrator'));
        foreach ($validated as $field => $newValue) {
            $property = match ($field) {
                'job_level' => 'jobLevel',
                'work_location' => 'workLocation',
                'employment_type' => 'employmentType',
                'expected_join_date' => 'expectedJoinDate',
                'replacement_for' => 'replacementFor',
                'job_description' => 'jobDescription',
                default => $field,
            };
            $oldValue = $existing->{$property} ?? null;
            if ((string) $oldValue !== (string) $newValue) {
                $this->auditRepo->log('MPR', $existing->mprNumber ?? $id, 'updated', $field, $oldValue, $newValue, $actor, 'Dashboard');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'MPR berhasil diperbarui.',
            'mpr' => $updated->toArray(),
            'pdf_url' => route('hr.mpr.pdf', ['id' => $updated->mprNumber]),
        ]);
    }

    /** Preview the current persisted MPR using the same PDF service as export. */
    public function preview(string $id): Response
    {
        $mpr = $this->mprRepo->findByMprNumber($id);
        if (!$mpr) {
            abort(404, "Dokumen MPR '{$id}' tidak ditemukan.");
        }

        $user = session('hr_user', []);
        if (($user['role'] ?? '') === 'Manpower') {
            $email = strtolower(trim($user['email'] ?? ''));
            if (
                $email !== strtolower(trim($mpr->requestorEmail ?? ''))
                && $email !== strtolower(trim($mpr->createdBy ?? ''))
            ) {
                abort(403, 'Anda tidak memiliki hak akses untuk melihat preview MPR ini.');
            }
        }

        return $this->pdfService->generate($mpr)->stream("MPR-{$mpr->mprNumber}-preview.pdf");
    }

    /**
     * Show detail of MPR record.
     */
    public function show(string $id): View|JsonResponse
    {
        $mpr = $this->mprRepo->findByMprNumber($id);

        if (!$mpr) {
            abort(404, "Data MPR dengan nomor '{$id}' tidak ditemukan.");
        }

        // Check ownership authorization for Manager role (IDOR Protection)
        $user = session('hr_user', []);
        $role = $user['role'] ?? 'Viewer';

        if ($role === 'Manpower') {
            $userEmail  = strtolower(trim($user['email'] ?? ''));
            $mprEmail   = strtolower(trim($mpr->requestorEmail ?? ''));
            $mprCreator = strtolower(trim($mpr->createdBy ?? ''));

            if ($mprEmail !== $userEmail && $mprCreator !== $userEmail) {
                abort(403, 'Anda tidak memiliki hak akses untuk melihat data MPR ini.');
            }
        }

        return $this->getJson($id);
    }

    /**
     * Export / Download MPR PDF document (using single MprPdfService).
     */
    public function exportPdf(string $id): Response
    {
        $mpr = $this->mprRepo->findByMprNumber($id);

        if (!$mpr) {
            abort(404, "Dokumen MPR '{$id}' tidak ditemukan.");
        }

        // Check ownership authorization for Manager role (IDOR Protection)
        $user = session('hr_user', []);
        $role = $user['role'] ?? 'Viewer';

        if ($role === 'Manpower') {
            $userEmail  = strtolower(trim($user['email'] ?? ''));
            $mprEmail   = strtolower(trim($mpr->requestorEmail ?? ''));
            $mprCreator = strtolower(trim($mpr->createdBy ?? ''));

            if ($mprEmail !== $userEmail && $mprCreator !== $userEmail) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengunduh dokumen MPR ini.');
            }
        } else {
            // Ensure user has export_mpr permission
            if (!Gate::allows('export_mpr')) {
                abort(403, 'Anda tidak memiliki izin untuk mengekspor dokumen MPR.');
            }
        }

        $pdf = $this->pdfService->generate($mpr);
        $fileName = "MPR-{$mpr->mprNumber}.pdf";

        $this->auditRepo->log('MPR', $mpr->mprNumber, 'generated', 'document', null, 'MPR PDF', $user['email'] ?? 'Manpower', 'Dashboard');

        return $pdf->stream($fileName);
    }
}
