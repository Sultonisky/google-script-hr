<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\AcceptCandidateRequest;
use App\Http\Requests\HR\BlacklistCandidateRequest;
use App\Http\Requests\HR\HoldCandidateRequest;
use App\Http\Requests\HR\UpdateCandidateStatusRequest;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Services\RecruitmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentController extends Controller
{
    protected CandidateRepositoryInterface $candidateRepo;
    protected AuditLogRepositoryInterface $auditRepo;
    protected RecruitmentService $recruitmentService;

    public function __construct(
        CandidateRepositoryInterface $candidateRepo,
        AuditLogRepositoryInterface $auditRepo,
        RecruitmentService $recruitmentService
    ) {
        $this->candidateRepo = $candidateRepo;
        $this->auditRepo = $auditRepo;
        $this->recruitmentService = $recruitmentService;
    }

    /**
     * Display ATS recruitment table with filter badges.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', '');
        $searchFilter = $request->query('search', '');
        $positionFilter = $request->query('position', '');
        $cityFilter   = $request->query('city', '');
        $genderFilter = $request->query('gender', '');
        $sortFilter   = $request->query('sort', 'newest');
        $perPage      = $request->query('per_page', 10);

        $allCandidates = $this->candidateRepo->getAllFromSheets(['candidates']);
        $positions = $allCandidates->pluck('positionApplied')
            ->filter(fn($position) => filled($position))
            ->map(fn($position) => trim($position))
            ->unique()
            ->sort()
            ->values();
        $pendingCandidates = $allCandidates->filter(function ($c) {
            return in_array(strtolower(trim($c->status ?? '')), ['new', 'pending'], true);
        });

        $counts = [
            'all'         => $allCandidates->count(),
            'new'         => $pendingCandidates->count(),
            'screening'   => $allCandidates->filter(fn($c) => strtolower($c->status ?? '') === 'screening')->count(),
            'interview'   => $allCandidates->filter(fn($c) => str_contains(strtolower($c->status ?? ''), 'interview'))->count(),
            'offering'    => $allCandidates->filter(fn($c) => strtolower($c->status ?? '') === 'offering')->count(),
            'accepted'    => $this->candidateRepo->getAllFromSheets(['candidates_accepted'])->count(),
            'hold'        => $this->candidateRepo->getAllFromSheets(['candidates_hold'])->count(),
            'blacklist'   => $this->candidateRepo->getAllFromSheets(['candidates_blacklist'])->count(),
            'rejected'    => $allCandidates->filter(fn($c) => strtolower($c->status ?? '') === 'rejected')->count(),
        ];

        $candidates = $pendingCandidates;

        if ($statusFilter) {
            $statusLower = strtolower($statusFilter);
            $sheetMap = [
                'hold'      => ['candidates_hold'],
                'blacklist' => ['candidates_blacklist'],
                'accepted'  => ['candidates_accepted'],
                'probation' => ['candidates_probation'],
            ];

            if (isset($sheetMap[$statusLower])) {
                $candidates = $this->candidateRepo->getAllFromSheets($sheetMap[$statusLower]);

                $candidates = $candidates->sortByDesc(fn($c) => $c->createdDate ?? '')->values();
            } else {
                $candidates = $allCandidates->filter(fn($c) => strtolower($c->status ?? '') === $statusLower);
            }
        }

        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $candidates = $candidates->filter(
                fn($c) =>
                str_contains(strtolower($c->fullName ?? ''), $search) ||
                    str_contains(strtolower($c->recruitmentId ?? ''), $search) ||
                    str_contains(strtolower($c->email ?? ''), $search) ||
                    str_contains(strtolower($c->nik ?? ''), $search) ||
                    str_contains(strtolower($c->positionApplied ?? ''), $search)
            );
        }

        if ($positionFilter) {
            $position = strtolower(trim($positionFilter));
            $candidates = $candidates->filter(fn($c) => strtolower(trim($c->positionApplied ?? '')) === $position);
        }

        if ($genderFilter) {
            $gender = strtolower(trim($genderFilter));
            $candidates = $candidates->filter(fn($c) => strtolower(trim($c->gender ?? '')) === $gender);
        }

        if ($cityFilter) {
            $city = strtolower($cityFilter);
            $candidates = $candidates->filter(fn($c) => str_contains(strtolower($c->city ?? ''), $city));
        }

        $candidates = match ($sortFilter) {
            'oldest'   => $candidates->sortBy(fn($c) => $c->createdDate ?? ''),
            'name_asc' => $candidates->sortBy(fn($c) => strtolower($c->fullName ?? '')),
            'name_desc' => $candidates->sortByDesc(fn($c) => strtolower($c->fullName ?? '')),
            default    => $candidates->sortByDesc(fn($c) => $c->createdDate ?? ''),
        };

        $total = $candidates->count();
        $candidates = $candidates->values();

        // Paginate manually
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedCandidates = $candidates->slice($offset, $perPage)->values();

        return view('hr.recruitment.index', compact('paginatedCandidates', 'candidates', 'counts', 'positions', 'statusFilter', 'searchFilter', 'positionFilter', 'cityFilter', 'genderFilter', 'sortFilter', 'perPage', 'currentPage', 'total'));
    }

    /**
     * Display Accepted candidates page (1:1 with partials/AcceptedPage.html).
     */
    public function accepted(Request $request): View
    {
        $perPage = $request->query('per_page', 10);
        // Full accepted list (unfiltered) — dipakai oleh search modal "Buat Offering Letter"
        // agar modal melihat seluruh kandidat Accepted, bukan hanya hasil filter tabel.
        $allCandidates = $this->candidateRepo->getAllFromSheets(['candidates_accepted']);
        $candidates = $allCandidates;
        $searchFilter = $request->query('search');
        $sortFilter = $request->query('sort', 'newest');
        $offeringFilter = $request->query('offering', '');
        $responseFilter = $request->query('response', '');
        $contractFilter = $request->query('contract', '');
        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $candidates = $candidates->filter(
                fn($c) =>
                str_contains(strtolower($c->fullName ?? ''), $search) ||
                    str_contains(strtolower($c->recruitmentId ?? ''), $search)
            )->values();
        }

        if ($offeringFilter === 'exists') {
            $candidates = $candidates->filter(fn($c) => filled($c->offeringCreated) && $c->offeringCreated !== '-');
        } elseif ($offeringFilter === 'missing') {
            $candidates = $candidates->filter(fn($c) => blank($c->offeringCreated) || $c->offeringCreated === '-');
        }

        if (in_array($responseFilter, ['Menunggu', 'Diterima', 'Ditolak'], true)) {
            $candidates = $candidates->filter(fn($c) => ($c->offeringResponse ?? '') === $responseFilter);
        } elseif ($responseFilter === 'none') {
            $candidates = $candidates->filter(fn($c) => blank($c->offeringResponse) || $c->offeringResponse === '-');
        }

        if ($contractFilter === 'processed') {
            $candidates = $candidates->filter(fn($c) => filled($c->onboardingStatus) && $c->onboardingStatus !== '-');
        } elseif ($contractFilter === 'pending') {
            $candidates = $candidates->filter(fn($c) => blank($c->onboardingStatus) || $c->onboardingStatus === '-');
        }
        $stats = [
            // Total Offering Letter = kandidat Accepted yang sudah punya Offering Created (1:1 GAS)
            'offering'   => $allCandidates->filter(fn($c) => !empty($c->offeringCreated) && $c->offeringCreated !== '-')->count(),
            // Kontrak PKWT Selesai = kandidat yang sudah diproses onboarding (status Contract)
            'onboarding' => $allCandidates->filter(fn($c) => !empty($c->onboardingStatus) && $c->onboardingStatus !== '-' && $c->onboardingStatus !== '')->count(),
        ];

        $candidates = $sortFilter === 'oldest'
            ? $candidates->sortBy(fn($c) => $c->createdDate ?? '')->values()
            : $candidates->sortByDesc(fn($c) => $c->createdDate ?? '')->values();
        $total = $candidates->count();
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedCandidates = $candidates->slice($offset, $perPage)->values();

        return view('hr.recruitment.pages.accepted', compact('paginatedCandidates', 'candidates', 'allCandidates', 'stats', 'perPage', 'currentPage', 'total', 'sortFilter', 'offeringFilter', 'responseFilter', 'contractFilter'));
    }

    public function holdPage(Request $request): View
    {
        $perPage = $request->query('per_page', 10);
        $candidates = $this->candidateRepo->getAllFromSheets(['candidates_hold']);
        $searchFilter = $request->query('search');
        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $candidates = $candidates->filter(
                fn($c) =>
                str_contains(strtolower($c->fullName ?? ''), $search) ||
                    str_contains(strtolower($c->recruitmentId ?? ''), $search)
            )->values();
        }

        $candidates = $candidates->sortByDesc(fn($c) => $c->createdDate ?? '')->values();
        $total = $candidates->count();
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedCandidates = $candidates->slice($offset, $perPage)->values();

        return view('hr.recruitment.pages.hold', compact('paginatedCandidates', 'candidates', 'perPage', 'currentPage', 'total'));
    }

    public function blacklistPage(Request $request): View
    {
        $perPage = $request->query('per_page', 10);
        $candidates = $this->candidateRepo->getAllFromSheets(['candidates_blacklist']);
        $searchFilter = $request->query('search');
        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $candidates = $candidates->filter(
                fn($c) =>
                str_contains(strtolower($c->fullName ?? ''), $search) ||
                    str_contains(strtolower($c->recruitmentId ?? ''), $search)
            )->values();
        }

        $candidates = $candidates->sortByDesc(fn($c) => $c->createdDate ?? '')->values();
        $total = $candidates->count();
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedCandidates = $candidates->slice($offset, $perPage)->values();

        return view('hr.recruitment.pages.blacklist', compact('paginatedCandidates', 'candidates', 'perPage', 'currentPage', 'total'));
    }

    /**
     * Show candidate comprehensive detail page.
     */
    public function show(string $id): View
    {
        $candidate = $this->candidateRepo->findById($id);
        if (!$candidate) {
            abort(404, "Kandidat dengan ID {$id} tidak ditemukan.");
        }

        $auditLogs = $this->auditRepo->getLogs($id);

        return view('hr.recruitment.show', compact('candidate', 'auditLogs'));
    }

    /**
     * Update candidate status.
     */
    public function updateStatus(UpdateCandidateStatusRequest $request, string $id): RedirectResponse
    {
        try {
            $this->recruitmentService->updateCandidateStatus(
                recruitmentId: $id,
                newStatus: $request->input('status'),
                notes: $request->input('hr_notes'),
                user: 'HR Administrator'
            );

            return back()->with('success', "Status kandidat {$id} berhasil diperbarui menjadi {$request->input('status')}.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Hold candidate.
     */
    public function hold(HoldCandidateRequest $request, string $id): RedirectResponse
    {
        try {
            $this->recruitmentService->holdCandidate(
                recruitmentId: $id,
                reason: $request->input('reason'),
                followUpDate: null,
                notes: null,
                user: 'HR Administrator'
            );

            return back()->with('success', "Kandidat {$id} berhasil dipindahkan ke status HOLD.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Blacklist candidate.
     */
    public function blacklist(BlacklistCandidateRequest $request, string $id): RedirectResponse
    {
        try {
            $this->recruitmentService->blacklistCandidate(
                recruitmentId: $id,
                reason: $request->input('reason'),
                notes: null,
                user: 'HR Administrator'
            );

            return back()->with('success', "Kandidat {$id} telah dimasukkan ke daftar BLACKLIST.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Accept candidate & hire to Employee master sheet.
     * 1:1 dengan GAS acceptCandidateToEmployee():
     *   1. Update status + Employee ID di in-memory row
     *   2. Append ke kandidat_accepted
     *   3. Delete dari data_kandidat
     *   4. Buat record di Employee sheet (jika belum ada)
     */
    public function accept(AcceptCandidateRequest $request, string $id): RedirectResponse
    {
        try {
            $user = $this->hrUserName();
            $this->recruitmentService->acceptCandidateToEmployee(
                recruitmentId: $id,
                extraEmployeeData: [],
                user: $user
            );

            return back()->with('success', "Kandidat {$id} berhasil diterima dan dipindahkan ke Kandidat Accepted.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Auto-save HR Notes via AJAX.
     */
    public function saveNotes(Request $request, string $id): JsonResponse
    {
        try {
            $notes = $request->input('notes', '');
            $this->candidateRepo->update($id, ['HR Notes' => $notes]);

            return response()->json(['success' => true, 'message' => 'Catatan HR berhasil disimpan.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function moveStatus(Request $request, string $id): JsonResponse
    {
        try {
            $fromStatus = $request->input('from_status');
            $toStatus = $request->input('to_status');
            $reason = $request->input('reason');
            $hrNotes = $request->input('hr_notes');

            if (!$toStatus) {
                return response()->json(['success' => false, 'message' => 'Status tujuan wajib diisi.'], 422);
            }

            $toLower = strtolower($toStatus);
            if ($toLower === 'hold') {
                $this->recruitmentService->holdCandidate(
                    recruitmentId: $id,
                    reason: $reason ?? '',
                    followUpDate: null,
                    notes: $hrNotes,
                    user: 'HR Administrator'
                );
            } elseif ($toLower === 'blacklist') {
                $this->recruitmentService->blacklistCandidate(
                    recruitmentId: $id,
                    reason: $reason ?? '',
                    notes: $hrNotes,
                    user: 'HR Administrator'
                );
            } elseif ($toLower === 'accepted') {
                $this->recruitmentService->acceptCandidateToEmployee(
                    recruitmentId: $id,
                    extraEmployeeData: [],
                    user: 'HR Administrator'
                );
            } else {
                $this->recruitmentService->updateCandidateStatus(
                    recruitmentId: $id,
                    newStatus: $toStatus,
                    notes: $hrNotes,
                    user: 'HR Administrator'
                );
            }

            return response()->json(['success' => true, 'message' => "Status berhasil diubah ke {$toStatus}."]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveOffering(Request $request, string $id): JsonResponse
    {
        try {
            // Cari kandidat untuk cek apakah sudah punya offering sebelumnya (1:1 GAS saveOfferingStatus)
            $candidate = $this->candidateRepo->findById($id);
            if (!$candidate) {
                return response()->json(['success' => false, 'message' => "Kandidat {$id} tidak ditemukan."], 404);
            }

            $now  = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $user = $this->hrUserName();

            $hasExistingOffering = !empty($candidate->offeringCreated) && $candidate->offeringCreated !== '-';

            $offeringData = [
                'Offering Company Entity'    => $request->input('branch_name', ''),
                'Offering Position'          => $request->input('position', ''),
                'Offering Division'          => $request->input('division', ''),
                'Offering Job Level'         => $request->input('job_level', ''),
                'Offering Lokasi Kerja'      => $request->input('lokasi_kerja', ''),
                'Offering Join Date'         => $request->input('join_date', ''),
                'Offering Employment Status' => $request->input('employment_status', 'Perjanjian Kerja Waktu Tertentu'),
                'Offering Contract Duration' => $request->input('contract_duration', ''),
                'Offering Salary Basic'      => $request->input('salary_basic', ''),
                'Offering Allow Pulsa'       => $request->input('allow_pulsa', ''),
                'Offering Allow Transport'   => $request->input('allow_transport', ''),
                'Offering Working Hours'     => $request->input('working_hours', ''),
                // Also set total salary for backward compat
                'Offering Salary'            => $request->input('salary_basic', ''),
                'Offering Notes'             => $request->input('notes', ''),
            ];

            if (!$hasExistingOffering) {
                // Belum punya offering → isi Created + set default response "Menunggu" (1:1 GAS)
                $offeringData['Offering Created']    = $now;
                $offeringData['Offering Created By'] = $user;
                $offeringData['Offering Response']   = 'Menunggu';
                $action = 'created';
            } else {
                // Sudah ada offering → update Updated + UpdatedBy (1:1 GAS)
                $offeringData['Offering Updated']    = $now;
                $offeringData['Offering Updated By'] = $user;
                $action = 'updated';
            }

            $success = $this->candidateRepo->update($id, $offeringData);
            if (!$success) {
                return response()->json(['success' => false, 'message' => 'Gagal menyimpan data ke Google Sheets. Pastikan kandidat ditemukan di sheet kandidat_accepted.'], 500);
            }

            // Log audit
            $this->auditRepo->log(
                'Candidate',
                $id,
                $hasExistingOffering ? 'updated' : 'created',
                'Offering',
                null,
                $now . ' by ' . $user,
                $user,
                'Dashboard'
            );

            return response()->json([
                'success' => true,
                'message' => 'Data offering berhasil disimpan.',
                'action'  => $action,
                'pdf_url' => route('hr.export.offering-letter', ['id' => $id]) . '?' . http_build_query([
                    'branch_name'       => $request->input('branch_name', ''),
                    'position'          => $request->input('position', ''),
                    'division'          => $request->input('division', ''),
                    'job_level'         => $request->input('job_level', ''),
                    'lokasi_kerja'      => $request->input('lokasi_kerja', ''),
                    'join_date'         => $request->input('join_date', ''),
                    'employment_status' => $request->input('employment_status', ''),
                    'contract_duration' => $request->input('contract_duration', ''),
                    'salary_basic'      => $request->input('salary_basic', ''),
                    'allow_pulsa'       => $request->input('allow_pulsa', ''),
                    'allow_transport'   => $request->input('allow_transport', ''),
                    'working_hours'     => $request->input('working_hours', ''),
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Simpan respons kandidat terhadap offering letter (1:1 GAS saveOfferingResponse).
     * response: "Menunggu" | "Diterima" | "Ditolak"
     */
    public function saveOfferingResponse(Request $request, string $id): JsonResponse
    {
        try {
            $response = $request->input('response', 'Menunggu');
            $notes = $request->input('notes', '');
            $user = $this->hrUserName();

            $allowed = ['Menunggu', 'Diterima', 'Ditolak'];
            if (!in_array($response, $allowed, true)) {
                return response()->json(['success' => false, 'message' => 'Respons tidak valid.'], 422);
            }

            $candidate = $this->candidateRepo->findById($id);
            if (!$candidate) {
                return response()->json(['success' => false, 'message' => "Recruitment ID tidak ditemukan: {$id}"], 404);
            }

            // Guard: hanya kandidat yang sudah punya offering letter yang boleh di-update responsnya
            if (empty(trim($candidate->offeringCreated ?? '')) || $candidate->offeringCreated === '-') {
                return response()->json(['success' => false, 'message' => 'Kandidat belum memiliki offering letter. Buat offering letter terlebih dahulu.'], 422);
            }

            $success = $this->candidateRepo->update($id, [
                'Offering Response' => $response,
                'Offering Response Notes' => $notes,
                'Offering Response Date' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'Offering Response By' => $user,
            ]);

            if (!$success) {
                return response()->json(['success' => false, 'message' => 'Gagal menyimpan respons offering ke Google Sheets. Pastikan kandidat ditemukan di sheet kandidat_accepted.'], 500);
            }

            $this->auditRepo->log(
                'Candidate',
                $id,
                'updated',
                'Offering Response',
                null,
                $response . ($notes ? " — {$notes}" : '') . " by {$user}",
                $user,
                'Dashboard'
            );

            return response()->json([
                'success' => true,
                'message' => 'Respons offering berhasil disimpan.',
                'recruitmentId' => $id,
                'response' => $response,
                'updatedBy' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Proses Kontrak PKWT & Onboarding — buat Employee (Contract) + tandai onboarding,
     * lalu kembalikan detail kontrak agar frontend bisa auto-generate PDF (1:1 GAS).
     */
    public function saveContract(Request $request, string $id): JsonResponse
    {
        try {
            $user = $this->hrUserName();
            $result = $this->recruitmentService->processContractOnboarding($id, $request->all(), $user);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getJson(Request $request, string $id): JsonResponse
    {
        $candidate = $this->candidateRepo->findById($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'error' => 'Kandidat tidak ditemukan.'], 404);
        }

        $auditLogs = $request->boolean('preview')
            ? collect()
            : $this->auditRepo->getLogs($id)->reject(function ($log) {
                $action = strtolower(trim((string) ($log['Action'] ?? $log['action'] ?? '')));
                $field = strtolower(trim((string) ($log['Field'] ?? $log['field'] ?? '')));

                return $action === 'consent_accepted' || $field === 'agreement_evidence';
            })->values();

        return response()->json([
            'success' => true,
            'candidate' => $candidate,
            'auditLogs' => $auditLogs,
        ]);
    }
}
