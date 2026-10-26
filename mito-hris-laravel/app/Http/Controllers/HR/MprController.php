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
        // ── Portal-aware identity resolution ──────────────────────────────────────
        // DomainMiddleware sets request attribute 'portal' = 'hris' | 'mpr'.
        // This is the authoritative signal — we never guess the portal from session
        // content, because both sessions can coexist in a shared session store.
        //
        // MPR portal (/mpr/*): return mpr_requestor_auth ONLY (auth_domain check).
        // HRIS portal (/hr/*): return hr_user ONLY (auth_domain check).
        //
        // Without this, MprController@index called from the HRIS domain while a
        // valid mpr_requestor_auth session also existed would return the MPR
        // Requestor identity and immediately redirect to mpr.auth.request — which
        // is exactly the bug this fix addresses.
        $portal = request()->attributes->get('portal', 'hris');

        if ($portal === 'mpr') {
            $sessionKey    = config('mpr.session_key', 'mpr_requestor_auth');
            $dedicatedUser = session($sessionKey, []);

            if (!empty($dedicatedUser) && ($dedicatedUser['auth_domain'] ?? '') === 'mpr_requestor') {
                return $dedicatedUser;
            }

            // No valid MPR session — return empty (EnsureMprAuthenticated would have
            // blocked this request already; this path should not normally be reached).
            return [];
        }

        // HRIS portal — return hr_user only. Never read mpr_requestor_auth here.
        return session('hr_user', []);
    }

    protected function isManpowerUser(array $user = []): bool
    {
        return strtolower(trim((string) ($user['role'] ?? ''))) === 'manpower';
    }

    protected function requestorOwnsMpr(array $user, MprData $mpr): bool
    {
        $email = strtolower(trim((string) ($user['email'] ?? '')));
        if ($email === '') {
            return false;
        }

        return $email === strtolower(trim((string) ($mpr->requestorEmail ?? '')))
            || $email === strtolower(trim((string) ($mpr->createdBy ?? '')));
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaultsFromMpr(MprData $mpr): array
    {
        $singleKey = function (?string $stored, string $group): string {
            return $this->optionKeysFromLabels($stored, $group)[0] ?? '';
        };

        return [
            'entity' => $mpr->entity ?? '',
            'position' => $mpr->position ?? '',
            'department' => $mpr->department ?? '',
            'division' => $mpr->division ?? '',
            'approval_division' => $mpr->approvalDivision ?? '',
            'job_level' => $mpr->jobLevel ?? '',
            'work_location' => $mpr->workLocation ?? '',
            'employment_type' => $mpr->employmentType ?? '',
            'quantity' => $mpr->quantity ?? 1,
            'expected_join_date' => $mpr->expectedJoinDate ?? '',
            'reason' => $mpr->reason ?? '',
            'replacement_for' => $mpr->replacementFor ?? '',
            'shift_detail' => $mpr->shiftDetail ?? '',
            'working_days' => $this->optionKeysFromLabels($mpr->workingDays, 'working_days'),
            'working_hours' => $this->optionKeysFromLabels($mpr->workingHours, 'working_hours'),
            'benefits' => $this->optionKeysFromLabels($mpr->benefits, 'benefits'),
            'education_background' => $singleKey($mpr->educationBackground, 'education_background'),
            'work_experience' => $singleKey($mpr->workExperience, 'work_experience'),
            'skills_competencies' => $mpr->skillsCompetencies ?? '',
            'languages' => $mpr->languages ?? '',
            'industry_reference' => $mpr->industryReference ?? '',
            'requirements' => $mpr->requirements ?? '',
            'job_description' => $mpr->jobDescription ?? '',
            'key_results_targets' => $mpr->keyResultsTargets ?? '',
            'special_notes' => $mpr->specialNotes ?? '',
        ];
    }

    /**
     * @return list<string>
     */
    protected function optionKeysFromLabels(?string $stored, string $group): array
    {
        $map = config("hris.mpr_form_options.{$group}", []);
        $labels = array_values(array_filter(
            array_map('trim', explode(',', (string) $stored)),
            static fn (string $value) => $value !== ''
        ));
        $keys = [];
        foreach ($labels as $label) {
            $found = array_search($label, $map, true);
            if ($found !== false) {
                $keys[] = (string) $found;
                continue;
            }
            if (array_key_exists($label, $map)) {
                $keys[] = $label;
            }
        }

        return $keys;
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
            $stats = [
                'total'           => $allMprs->count(),
                'finish'          => $allMprs->filter(fn($m) => strtolower($m->status ?? '') === 'finish')->count(),
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
                'finish' => 0,
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
            'SDI' => 'PT Suka Dessert Indonesia (SDI)',
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
        $stats = [
            'total'           => $allMprs->count(),
            'finish'          => $allMprs->filter(fn($m) => strtolower($m->status ?? '') === 'finish')->count(),
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
            'SDI' => 'PT Suka Dessert Indonesia (SDI)',
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

    public function edit(Request $request, string $id): View
    {
        $user = $this->currentRequestorUser();
        if (!$this->isManpowerUser($user)) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah pengajuan MPR.');
        }

        $mpr = $this->mprRepo->findByMprNumber($id);
        if (!$mpr) {
            abort(404, 'Data MPR tidak ditemukan.');
        }
        if (!$this->requestorOwnsMpr($user, $mpr)) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah pengajuan MPR ini.');
        }
        if (!$this->mprIsSubmitted($mpr)) {
            abort(403, 'Hanya pengajuan berstatus Submitted yang bisa diedit.');
        }

        return view('hr.mpr.create', array_merge(
            $this->buildRequestorPageData($request, false),
            [
                'editingMpr' => $mpr,
                'formDefaults' => $this->formDefaultsFromMpr($mpr),
            ]
        ));
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

        $validated = $this->resolveWorkLocation($request->validated());

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

        // Prepare PDF URL — gunakan route yang sesuai dengan domain request
        $isMprDomain = $request->routeIs('mpr.auth.*');
        $pdfUrl = $isMprDomain
            ? route('mpr.auth.pdf', ['id' => $savedMpr->mprNumber])
            : route('hr.mpr.pdf', ['id' => $savedMpr->mprNumber]);

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

        $redirectRoute = $isMprDomain ? 'mpr.auth.request.history' : 'hr.mpr.index';
        return redirect()->route($redirectRoute)
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
        $existing = $this->mprRepo->findByMprNumber($id);

        if (!$existing) {
            return response()->json(['success' => false, 'message' => 'Data MPR tidak ditemukan.'], 404);
        }
        if (!$this->mprIsSubmitted($existing)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan berstatus Submitted yang bisa diedit.',
            ], 422);
        }

        $user = $this->currentRequestorUser();
        $isRequestorUpdate = $request->routeIs('mpr.auth.request.update');
        if ($isRequestorUpdate) {
            if (!$this->isManpowerUser($user) || !$this->requestorOwnsMpr($user, $existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah pengajuan MPR ini.',
                ], 403);
            }
        } else {
            Gate::authorize('update_mpr');
        }

        $validated = $this->resolveWorkLocation($request->validated());
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
        $submittedDayKeys = array_map(
            static fn ($value) => strtolower(trim((string) $value)),
            (array) ($validated['working_days'] ?? [])
        );
        $daysSubmitted = array_key_exists('working_days', $validated);
        $hoursSubmitted = array_key_exists('working_hours', $validated);
        $benefitsSubmitted = array_key_exists('benefits', $validated);
        $isShifting = in_array('shifting', $submittedDayKeys, true);
        $workingDaysValue = $daysSubmitted
            ? ($workingDaysLabels !== '' ? $workingDaysLabels : null)
            : ($existing->workingDays ?? null);
        $workingHoursValue = $isShifting
            ? null
            : ($hoursSubmitted
                ? ($workingHoursLabels !== '' ? $workingHoursLabels : null)
                : ($existing->workingHours ?? null));
        $shiftDetailValue = $daysSubmitted && ! $isShifting
            ? null
            : (array_key_exists('shift_detail', $validated)
                ? $validated['shift_detail']
                : $existing->shiftDetail);
        $benefitsValue = $benefitsSubmitted
            ? ($benefitsLabels !== '' ? $benefitsLabels : null)
            : ($existing->benefits ?? null);

        $updated = new MprData(
            mprNumber: $existing->mprNumber,
            requestDate: $existing->requestDate,
            requestorName: $existing->requestorName,
            requestorEmail: $existing->requestorEmail,
            entity: $validated['entity'] ?? $existing->entity,
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
            requestorPosition: $isRequestorUpdate
                ? $existing->requestorPosition
                : ($validated['requestor_position'] ?? $existing->requestorPosition),
            workingDays: $workingDaysValue,
            workingHours: $workingHoursValue,
            shiftDetail: $shiftDetailValue,
            benefits: $benefitsValue,
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

        $actor = $isRequestorUpdate
            ? ($user['email'] ?? 'Manpower')
            : session('hr_user.email', session('hr_user.fullName', 'HR Administrator'));
        $auditSource = $isRequestorUpdate ? 'Public' : 'Dashboard';
        foreach ($validated as $field => $newValue) {
            $property = match ($field) {
                'job_level' => 'jobLevel',
                'work_location' => 'workLocation',
                'employment_type' => 'employmentType',
                'expected_join_date' => 'expectedJoinDate',
                'replacement_for' => 'replacementFor',
                'job_description' => 'jobDescription',
                'approval_division' => 'approvalDivision',
                'requestor_position' => 'requestorPosition',
                'working_days' => 'workingDays',
                'working_hours' => 'workingHours',
                'shift_detail' => 'shiftDetail',
                'education_background' => 'educationBackground',
                'work_experience' => 'workExperience',
                'skills_competencies' => 'skillsCompetencies',
                'industry_reference' => 'industryReference',
                'special_notes' => 'specialNotes',
                'key_results_targets' => 'keyResultsTargets',
                default => $field,
            };
            $comparableNew = is_array($newValue) ? $toLabels($newValue, $field) : $newValue;
            $oldValue = $existing->{$property} ?? null;
            if ((string) $oldValue !== (string) $comparableNew) {
                $this->auditRepo->log('MPR', $existing->mprNumber ?? $id, 'updated', $field, $oldValue, $comparableNew, $actor, $auditSource);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'MPR berhasil diperbarui.',
            'mpr' => $updated->toArray(),
            'pdf_url' => $isRequestorUpdate
                ? route('mpr.auth.pdf', ['id' => $updated->mprNumber])
                : route('hr.mpr.pdf', ['id' => $updated->mprNumber]),
        ]);
    }

    /**
     * Move a Submitted MPR to Finish. Requestors may finish only their own record.
     */
    public function finish(Request $request, string $id): RedirectResponse
    {
        $existing = $this->mprRepo->findByMprNumber($id);
        if (!$existing) {
            abort(404, "Data MPR dengan nomor '{$id}' tidak ditemukan.");
        }

        $user = $this->currentRequestorUser();
        $isRequestorFinish = $request->routeIs('mpr.auth.request.finish') || $this->isManpowerUser($user);
        if ($isRequestorFinish) {
            if (!$this->isManpowerUser($user) || !$this->requestorOwnsMpr($user, $existing)) {
                abort(403, 'Anda tidak memiliki akses untuk mengubah status pengajuan MPR ini.');
            }
        } else {
            Gate::authorize('update_mpr');
        }

        if (strcasecmp(trim((string) $existing->status), 'Submitted') !== 0) {
            return back()->with('error', 'Hanya pengajuan berstatus Submitted yang bisa diubah menjadi Finish.');
        }

        $updated = clone $existing;
        $updated->status = 'Finish';
        $updated->updatedAt = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        try {
            $this->mprRepo->update($existing->mprNumber ?? $id, $updated);
        } catch (\Throwable $e) {
            Log::error('Gagal mengubah status MPR menjadi Finish: ' . $e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat mengubah status MPR.');
        }

        $actor = $isRequestorFinish
            ? ($user['email'] ?? 'Manpower')
            : session('hr_user.email', session('hr_user.fullName', 'HR Administrator'));
        $this->auditRepo->log(
            'MPR',
            $updated->mprNumber ?? $id,
            'updated',
            'status',
            $existing->status,
            'Finish',
            $actor,
            $isRequestorFinish ? 'Public' : 'Dashboard'
        );

        return back()->with('success', 'Status MPR ' . ($updated->mprNumber ?? $id) . ' diubah menjadi Finish.');
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

        return $this->pdfService->generate($mpr)->stream($this->pdfFileName($mpr->mprNumber, true));
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
        $fileName = $this->pdfFileName($mpr->mprNumber);

        $this->auditRepo->log('MPR', $mpr->mprNumber, 'generated', 'document', null, 'MPR PDF', $user['email'] ?? 'Manpower', 'Dashboard');

        return $pdf->stream($fileName);
    }

    private function mprIsSubmitted(MprData $mpr): bool
    {
        return strcasecmp(trim((string) $mpr->status), 'Submitted') === 0;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function resolveWorkLocation(array $validated): array
    {
        if (!array_key_exists('work_location', $validated)) {
            unset($validated['work_location_custom']);

            return $validated;
        }

        $other = (string) config('hris.mpr_form_options.work_location_other', 'Lainnya');
        $location = trim((string) $validated['work_location']);
        if (strcasecmp($location, $other) === 0) {
            $validated['work_location'] = trim((string) ($validated['work_location_custom'] ?? ''));
        }
        unset($validated['work_location_custom']);

        return $validated;
    }

    private function pdfFileName(?string $mprNumber, bool $preview = false): string
    {
        $number = trim((string) $mprNumber);
        $stem = str_starts_with($number, 'MPR-') ? $number : 'MPR-'.$number;

        return $preview ? "{$stem}-preview.pdf" : "{$stem}.pdf";
    }
}
