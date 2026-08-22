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
        $cityFilter   = $request->query('city', '');
        $perPage      = $request->query('per_page', 10);

        $allCandidates = $this->candidateRepo->getAllFromSheets(['candidates']);
        $pendingCandidates = $allCandidates->filter(fn($c) => strtolower($c->status ?? 'pending') === 'pending');

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
            } else {
                $candidates = $allCandidates->filter(fn($c) => strtolower($c->status ?? '') === $statusLower);
            }
        }

        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $candidates = $candidates->filter(fn($c) =>
                str_contains(strtolower($c->fullName ?? ''), $search) ||
                str_contains(strtolower($c->recruitmentId ?? ''), $search) ||
                str_contains(strtolower($c->email ?? ''), $search) ||
                str_contains(strtolower($c->nik ?? ''), $search) ||
                str_contains(strtolower($c->positionApplied ?? ''), $search)
            );
        }

        if ($cityFilter) {
            $city = strtolower($cityFilter);
            $candidates = $candidates->filter(fn($c) => str_contains(strtolower($c->city ?? ''), $city));
        }

        $total = $candidates->count();
        $candidates = $candidates->values();

        // Paginate manually
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedCandidates = $candidates->slice($offset, $perPage)->values();

        return view('hr.recruitment.index', compact('paginatedCandidates', 'candidates', 'counts', 'statusFilter', 'searchFilter', 'cityFilter', 'perPage', 'currentPage', 'total'));
    }

    /**
     * Display Accepted candidates page (1:1 with partials/AcceptedPage.html).
     */
    public function accepted(Request $request): View
    {
        $perPage = $request->query('per_page', 10);
        $candidates = $this->candidateRepo->getAllFromSheets(['candidates_accepted']);
        $searchFilter = $request->query('search');
        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $candidates = $candidates->filter(fn($c) =>
                str_contains(strtolower($c->fullName ?? ''), $search) ||
                str_contains(strtolower($c->recruitmentId ?? ''), $search)
            )->values();
        }
        $stats = [
            'offering'   => $this->candidateRepo->getAllFromSheets(['candidates_accepted'])->filter(fn($c) => strtolower($c->status ?? '') === 'offering')->count(),
            'onboarding' => $candidates->count(),
        ];

        $total = $candidates->count();
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedCandidates = $candidates->slice($offset, $perPage)->values();

        return view('hr.recruitment.pages.accepted', compact('paginatedCandidates', 'candidates', 'stats', 'perPage', 'currentPage', 'total'));
    }

    public function holdPage(Request $request): View
    {
        $perPage = $request->query('per_page', 10);
        $candidates = $this->candidateRepo->getAllFromSheets(['candidates_hold']);
        $searchFilter = $request->query('search');
        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $candidates = $candidates->filter(fn($c) =>
                str_contains(strtolower($c->fullName ?? ''), $search) ||
                str_contains(strtolower($c->recruitmentId ?? ''), $search)
            )->values();
        }

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
            $candidates = $candidates->filter(fn($c) =>
                str_contains(strtolower($c->fullName ?? ''), $search) ||
                str_contains(strtolower($c->recruitmentId ?? ''), $search)
            )->values();
        }

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
     */
    public function accept(AcceptCandidateRequest $request, string $id): RedirectResponse
    {
        try {
            $this->recruitmentService->updateCandidateStatus(
                recruitmentId: $id,
                newStatus: 'Accepted',
                notes: null,
                user: 'HR Administrator'
            );

            return back()->with('success', "Kandidat {$id} berhasil diterima dan dipindahkan ke status Accepted.");
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
            $offeringData = [
                'Offering Company Entity' => $request->input('branch_name', ''),
                'Offering Position' => $request->input('position', ''),
                'Offering Department' => $request->input('department', ''),
                'Offering Division' => $request->input('division', ''),
                'Offering Job Level' => $request->input('job_level', ''),
                'Offering Lokasi Kerja' => $request->input('lokasi_kerja', ''),
                'Offering Join Date' => $request->input('join_date', ''),
                'Offering Employment Status' => $request->input('employment_status', ''),
                'Offering Contract Duration' => $request->input('contract_duration', ''),
                'Offering Salary Basic' => $request->input('salary_basic', ''),
                'Offering Allow Pulsa' => $request->input('allow_pulsa', ''),
                'Offering Allow Transport' => $request->input('allow_transport', ''),
                'Offering Working Hours' => $request->input('working_hours', ''),
                'Offering Created' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'Offering Created By' => 'HR Administrator',
            ];

            $this->candidateRepo->update($id, $offeringData);

            return response()->json(['success' => true, 'message' => 'Data offering berhasil disimpan.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getJson(string $id): JsonResponse
    {
        $candidate = $this->candidateRepo->findById($id);
        if (!$candidate) {
            return response()->json(['success' => false, 'error' => 'Kandidat tidak ditemukan.'], 404);
        }

        $auditLogs = $this->auditRepo->getLogs($id);

        return response()->json([
            'success' => true,
            'candidate' => $candidate,
            'auditLogs' => $auditLogs,
        ]);
    }
}
