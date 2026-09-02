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

    protected function currentRequestorUser(): array
    {
        $sessionKey = config('mpr.session_key', 'mpr_requestor_auth');
        $dedicatedUser = session($sessionKey, []);

        if (!empty($dedicatedUser) && (($dedicatedUser['auth_domain'] ?? '') === 'mpr_requestor' || strtolower(trim((string) ($dedicatedUser['role'] ?? ''))) === 'manpower')) {
            return $dedicatedUser;
        }

        return session('hr_user', []);
    }

    protected function isManpowerUser(array $user = []): bool
    {
        return strtolower(trim((string) ($user['role'] ?? ''))) === 'manpower';
    }

    /**
     * Shared MPR page context for requestor pages while preserving existing business logic.
     */
    protected function buildRequestorPageData(Request $request, bool $includeRecords = true): array
    {
        $user = $this->currentRequestorUser();
        $role = $user['role'] ?? 'Viewer';
        $isManpower = $this->isManpowerUser($user);
        $isManager = $isManpower;

        $search = $request->query('search', '');
        $dept = $request->query('department', '');
        $status = $request->query('status', '');
        $submitBy = $request->query('submit_by', '');
        $sort = strtolower((string) $request->query('sort', 'newest'));
        $perPage = (int) $request->query('per_page', 10);

        $filters = array_filter([
            'search'     => $search,
            'department' => $dept,
            'status'     => $status,
            'submit_by'  => $submitBy,
        ]);

        if ($includeRecords) {
            if ($isManager) {
                $mprs = $this->mprRepo->getAllForManager($user['email'] ?? '', $filters);
            } else {
                $mprs = $this->mprRepo->getAll($filters);
            }

            if ($sort === 'oldest') {
                $mprs = $mprs->sortBy(fn($mpr) => $mpr->requestDate ?? $mpr->createdAt ?? '')->values();
            } else {
                $mprs = $mprs->sortByDesc(fn($mpr) => $mpr->requestDate ?? $mpr->createdAt ?? '')->values();
            }

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

            $total = $mprs->count();
            $currentPage = max(1, (int) $request->query('page', 1));
            $offset = ($currentPage - 1) * $perPage;
            $paginatedMprs = $mprs->slice($offset, $perPage)->values();
            $lastPage = max(1, (int) ceil($total / $perPage));
        } else {
            $paginatedMprs = collect();
            $total = 0;
            $currentPage = 1;
            $lastPage = 1;
            $perPage = 10;
            $stats = [
                'total' => 0,
                'this_month' => 0,
                'total_quantity' => 0,
                'submitted' => 0,
            ];
            $submitByOptions = collect();
        }

        $departmentDivisionMap = config('hris.mpr_department_divisions', []);
        $departments = array_keys($departmentDivisionMap);
        $jobLevels = config('hris.mpr_form_options.job_levels', ['Associate', 'Staff', 'Senior Staff', 'Supervisor', 'Team Lead', 'Manager', 'General Manager', 'Director']);
        $workLocations = config('hris.mpr_form_options.work_locations', ['Head Office (HO)', 'Depo Jakarta', 'Depo Bandung', 'Depo Surabaya', 'Pabrik']);
        $employmentTypes = config('hris.mpr_form_options.employment_types', ['Permanent (PKWTT)', 'Contract (PKWT)', 'Outsource', 'Intern', 'Freelance / Project']);

        $entityOptions = config('hris.mpr_form_options.target_entities', [
            'MSI' => 'PT Mahakarya Sukses Indonesia (MSI)',
            'SPI' => 'PT Stein Perkasa Internasional (SPI)',
            'PII' => 'PT Perkasa Injeksi Indonesia (PII)',
            'MEP' => 'PT Mitra Elektro Perkasa (MEP)',
        ]);

        // Target entity adalah input MPR form — requestor bebas memilih entity dari config,
        // tidak lagi dibatasi assignment entity akun mpr_requestor (schema 12 kolom).
        $reasons = array_values(config('hris.mpr_form_options.reasons', [
            'Penambahan Karyawan Baru',
            'Restrukturisasi',
            'Penggantian Karyawan Resign / Mutasi / Demosi',
            'Beban Kerja Musiman / Peak Season',
            'Proyek Khusus',
        ]));

        return compact(
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
            'reasons',
            'search',
            'dept',
            'status',
            'submitBy',
            'submitByOptions'
        );
    }

    /**
     * Display list of MPR records (for HR) or redirect requestor to dedicated history page.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $this->currentRequestorUser();
        $role = $user['role'] ?? 'Viewer';
        $isManpower = $this->isManpowerUser($user);

        if ($isManpower) {
            return redirect()->route('mpr.auth.request');
        }

        $search = $request->query('search', '');
        $dept = $request->query('department', '');
        $status = $request->query('status', '');
        $submitBy = $request->query('submit_by', '');
        $sort = strtolower((string) $request->query('sort', 'newest'));
        $perPage = (int) $request->query('per_page', 10);

        $filters = array_filter([
            'search'     => $search,
            'department' => $dept,
            'status'     => $status,
            'submit_by'  => $submitBy,
        ]);

        $mprs = $this->mprRepo->getAll($filters);
        if ($sort === 'oldest') {
            $mprs = $mprs->sortBy(fn($mpr) => $mpr->requestDate ?? $mpr->createdAt ?? '')->values();
        } else {
            $mprs = $mprs->sortByDesc(fn($mpr) => $mpr->requestDate ?? $mpr->createdAt ?? '')->values();
        }

        $allMprs = $this->mprRepo->getAll();
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

        $total = $mprs->count();
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedMprs = $mprs->slice($offset, $perPage)->values();
        $lastPage = max(1, (int) ceil($total / $perPage));

        $departmentDivisionMap = config('hris.mpr_department_divisions', []);
        $departments = array_keys($departmentDivisionMap);
        $jobLevels = config('hris.mpr_form_options.job_levels', ['Associate', 'Staff', 'Senior Staff', 'Supervisor', 'Team Lead', 'Manager', 'General Manager', 'Director']);
        $workLocations = config('hris.mpr_form_options.work_locations', ['Head Office (HO)', 'Depo Jakarta', 'Depo Bandung', 'Depo Surabaya', 'Pabrik']);
        $employmentTypes = config('hris.mpr_form_options.employment_types', ['Permanent (PKWTT)', 'Contract (PKWT)', 'Outsource', 'Intern', 'Freelance / Project']);
        $entityOptions = config('hris.mpr_form_options.target_entities', [
            'MSI' => 'PT Mahakarya Sukses Indonesia (MSI)',
            'SPI' => 'PT Stein Perkasa Internasional (SPI)',
            'PII' => 'PT Perkasa Injeksi Indonesia (PII)',
            'MEP' => 'PT Mitra Elektro Perkasa (MEP)',
        ]);
        $reasons = array_values(config('hris.mpr_form_options.reasons', [
            'Penambahan Karyawan Baru',
            'Restrukturisasi',
            'Penggantian Karyawan Resign / Mutasi / Demosi',
            'Beban Kerja Musiman / Peak Season',
            'Proyek Khusus',
        ]));

        return view('hr.mpr.index', array_merge(compact(
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
            'reasons',
            'search',
            'dept',
            'status',
            'submitBy',
            'submitByOptions'
        ), ['isManager' => false]));
    }

    public function create(Request $request): View
    {
        $user = $this->currentRequestorUser();
        if (!$this->isManpowerUser($user)) {
            return $this->index($request);
        }

        return view('hr.mpr.create', $this->buildRequestorPageData($request, false));
    }

    public function history(Request $request): View
    {
        $user = $this->currentRequestorUser();
        if (!$this->isManpowerUser($user)) {
            return $this->index($request);
        }

        return view('hr.mpr.history', $this->buildRequestorPageData($request));
    }

    /**
     * Store new MPR request and auto-generate PDF.
     *
     * Security:
     * - Entity divalidasi server-side: selected entity HARUS ada di daftar entity requestor.
     * - Branch selalu diambil dari session authenticated requestor, TIDAK dari request body.
     * - Identitas requestor (nama, email) selalu dari session untuk role Manpower.
     */
    /**
     * Store new MPR request and auto-generate PDF.
     *
     * Security:
     * - Entity adalah input MPR form; divalidasi server-side terhadap config
     *   hris.mpr_form_options.target_entities (bukan assignment akun requestor).
     * - Identitas requestor (nama, email, jabatan) selalu dari session untuk role Manpower
     *   (sumber canonical: mpr_requestors.Full Name & mpr_requestors.Job Position).
     */
    public function store(StoreMprRequest $request): JsonResponse|RedirectResponse
    {
        $user    = $this->currentRequestorUser();
        $role    = $user['role'] ?? 'Viewer';
        $isManager = $this->isManpowerUser($user);

        $validated = $request->validated();

        // ==============================================================
        // RESOLVE REQUESTOR IDENTITY & TARGET ENTITY
        // ==============================================================
        $selectedEntity = trim($validated['entity'] ?? '');

        if ($isManager) {
            // Identitas & jabatan requestor SELALU dari session mpr_requestor,
            // tidak boleh dimanipulasi melalui request body.
            $requestorName     = $user['fullName'] ?? $user['name'] ?? 'Manpower';
            $requestorEmail    = $user['email'] ?? '';
            $requestorPosition = trim((string) ($user['jobPosition'] ?? '') );
            $createdBy         = $user['email'] ?? 'Manpower';
        } else {
            // Admin / Super Admin: bisa mengisi atas nama Manpower lain (on-behalf).
            $requestorName     = !empty($validated['manager_name'])  ? $validated['manager_name']  : ($user['fullName'] ?? 'Admin');
            $requestorEmail    = !empty($validated['manager_email']) ? $validated['manager_email'] : ($user['email'] ?? '');
            $requestorPosition = trim((string) ($validated['requestor_position'] ?? $user['jobPosition'] ?? '') );
            $createdBy         = $user['email'] ?? 'HR Team';
        }

        // ==============================================================
        // FIELD BARU: konversi value selection → label human-readable
        // (single source of truth label: config hris.mpr_form_options)
        // ==============================================================
        $formOptions = config('hris.mpr_form_options', []);
        $toLabels = function (array $values, string $group) use ($formOptions): string {
            $map = $formOptions[$group] ?? [];
            $labels = array_map(fn($v) => $map[$v] ?? $v, $values);
            return implode(', ', $labels);
        };
        $workingDaysLabels   = $toLabels((array) ($validated['working_days'] ?? []), 'working_days');
        $workingHoursLabels  = $toLabels((array) ($validated['working_hours'] ?? []), 'working_hours');
        $benefitsLabels      = $toLabels((array) ($validated['benefits'] ?? []), 'benefits');
        $educationLabel      = $formOptions['education_background'][$validated['education_background'] ?? ''] ?? null;
        $experienceLabel     = $formOptions['work_experience'][$validated['work_experience'] ?? ''] ?? null;

        $now = now()->timezone('Asia/Jakarta');
        $approvalDivision = trim((string) ($validated['approval_division'] ?? ''));
        if ($approvalDivision === '') {
            $approvalDivision = trim((string) ($validated['division'] ?? ''));
        }
        if ($approvalDivision === '' || !in_array($approvalDivision, config('hris.mpr.approval_divisions', []), true)) {
            $fallbacks = config('hris.mpr.approval_divisions', []);
            $approvalDivision = $fallbacks[0] ?? trim((string) ($validated['division'] ?? ''));
        }

        $mprData = new MprData(
            requestDate: $now->format('Y-m-d'),
            requestorName: $requestorName,
            requestorEmail: $requestorEmail,
            entity: $selectedEntity,
            department: $validated['department'],
            division: $validated['division'],
            approvalDivision: $approvalDivision,
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
            status: 'Submitted',
            createdBy: $createdBy,
            // -- Field baru (Refactor Create MPR) --
            requestorPosition: $requestorPosition ?: null,
            workingDays: $workingDaysLabels !== '' ? $workingDaysLabels : null,
            workingHours: $workingHoursLabels !== '' ? $workingHoursLabels : null,
            shiftDetail: $validated['shift_detail'] ?? null,
            benefits: $benefitsLabels !== '' ? $benefitsLabels : null,
            educationBackground: $educationLabel,
            workExperience: $experienceLabel,
            skillsCompetencies: $validated['skills_competencies'] ?? null,
            languages: $validated['languages'] ?? null,
            industryReference: $validated['industry_reference'] ?? null,
            specialNotes: $validated['special_notes'] ?? null,
            keyResultsTargets: $validated['key_results_targets'] ?? null,
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
        $user = $this->currentRequestorUser();
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
                // 'notes_html'           => $this->markdownRenderer->render($mpr->notes),
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
        $formOptions = config('hris.mpr_form_options', []);
        $toLabels = function (array $values, string $group) use ($formOptions): string {
            $map = $formOptions[$group] ?? [];
            $labels = array_map(fn($v) => $map[$v] ?? $v, $values);
            return implode(', ', $labels);
        };

        $workingDaysLabels = $toLabels((array) ($validated['working_days'] ?? []), 'working_days');
        $workingHoursLabels = $toLabels((array) ($validated['working_hours'] ?? []), 'working_hours');
        $benefitsLabels = $toLabels((array) ($validated['benefits'] ?? []), 'benefits');
        $educationLabel = $formOptions['education_background'][$validated['education_background'] ?? ''] ?? null;
        $experienceLabel = $formOptions['work_experience'][$validated['work_experience'] ?? ''] ?? null;

        $updated = new MprData(
            mprNumber: $existing->mprNumber,
            requestDate: $existing->requestDate,
            requestorName: $existing->requestorName,
            requestorEmail: $existing->requestorEmail,
            entity: $existing->entity,
            department: $validated['department'],
            division: $validated['division'],
            approvalDivision: $validated['approval_division'] ?? $existing->approvalDivision,
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
            status: $existing->status,
            createdBy: $existing->createdBy,
            createdAt: $existing->createdAt,
            updatedAt: now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            requestorPosition: $validated['requestor_position'] ?? $existing->requestorPosition,
            workingDays: $workingDaysLabels !== '' ? $workingDaysLabels : ($existing->workingDays ?? null),
            workingHours: $workingHoursLabels !== '' ? $workingHoursLabels : ($existing->workingHours ?? null),
            shiftDetail: $validated['shift_detail'] ?? $existing->shiftDetail,
            benefits: $benefitsLabels !== '' ? $benefitsLabels : ($existing->benefits ?? null),
            educationBackground: $educationLabel ?? $existing->educationBackground,
            workExperience: $experienceLabel ?? $existing->workExperience,
            skillsCompetencies: $validated['skills_competencies'] ?? $existing->skillsCompetencies,
            languages: $validated['languages'] ?? $existing->languages,
            industryReference: $validated['industry_reference'] ?? $existing->industryReference,
            specialNotes: $validated['special_notes'] ?? $existing->specialNotes,
            keyResultsTargets: $validated['key_results_targets'] ?? $existing->keyResultsTargets,
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

        $user = $this->currentRequestorUser();
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
        $user = $this->currentRequestorUser();
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
        $user = $this->currentRequestorUser();
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
