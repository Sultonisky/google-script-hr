<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ApplyJobRequest;
use App\Http\Requests\Public\CandidateSelfUpdateRequest;
use App\Services\RecruitmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerController extends Controller
{
    private const RECRUITMENT_SUBMISSION_COMPLETED = 'public_recruitment_submission_completed';

    protected RecruitmentService $recruitmentService;

    public function __construct(RecruitmentService $recruitmentService)
    {
        $this->recruitmentService = $recruitmentService;
    }

    /**
     * Step 1: Candidate Landing Page with Terms Checklist.
     */
    public function index(): View
    {
        return view('public.career.landing');
    }

    /**
     * Step 2: Handle Agreement Consent and Unlock Form.
     */
    public function consent(Request $request): RedirectResponse
    {
        $request->validate([
            'consent' => 'required|in:1',
        ], [
            'consent.required' => 'Anda harus menyetujui syarat & ketentuan pendaftaran untuk melanjutkan.',
        ]);

        session([
            'candidate_consent' => true,
            'candidate_consent_evidence' => $this->consentEvidence($request, 'landing_consent'),
        ]);

        return redirect()->route('public.career.form');
    }

    /**
     * Step 3: Candidate Application Form (Validated against Consent).
     */
    public function form(): View|RedirectResponse
    {
        if (session(self::RECRUITMENT_SUBMISSION_COMPLETED)) {
            return redirect()->route('public.career.submission-success');
        }

        if (!session('candidate_consent')) {
            return redirect()->route('public.career.index')
                ->with('error', 'Harap membaca dan menyetujui syarat & ketentuan pendaftaran terlebih dahulu.');
        }

        $positions = app(\App\Services\JobPositionService::class)->getPositionNames();
        return view('public.career.apply', compact('positions'));
    }

    /**
     * Probe whether a NIK already has a recruitment record.
     */
    public function checkNik(Request $request): JsonResponse
    {
        if (!session('candidate_consent')) {
            return response()->json([
                'available' => false,
                'message' => 'Harap menyetujui syarat & ketentuan terlebih dahulu.',
            ], 403);
        }

        $validated = $request->validate([
            'nik' => ['required', 'digits:16'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus berupa 16 digit angka.',
        ]);

        $status = $this->recruitmentService->nikRegistrationStatus($validated['nik']);

        return response()->json($status);
    }

    /**
     * Step 4: Store new candidate application into Google Sheets.
     */
    public function store(ApplyJobRequest $request): RedirectResponse
    {
        if (session(self::RECRUITMENT_SUBMISSION_COMPLETED)) {
            return redirect()->route('public.career.submission-success');
        }

        // Re-flash consent so the form page stays accessible if we redirect back
        session(['candidate_consent' => true]);

        try {
            $candidate = $this->recruitmentService->apply(
                validatedData: array_merge($request->validated(), [
                    'consent_evidence' => [
                        'landing_consent' => session('candidate_consent_evidence', []),
                        'application_agreement' => $this->consentEvidence($request, 'application_agreement'),
                    ],
                ]),
                cvFile: $request->file('cv_file')
            );

            session()->forget(['candidate_consent', 'candidate_consent_evidence']);
            session([self::RECRUITMENT_SUBMISSION_COMPLETED => true]);

            return redirect()->route('public.career.submission-success');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the server-controlled success page for a completed candidate submission.
     */
    public function submissionSuccess(): View|RedirectResponse
    {
        if (!session(self::RECRUITMENT_SUBMISSION_COMPLETED)) {
            return redirect()->route('public.career.form');
        }

        return view('public.career.success', ['candidate' => null, 'id' => null]);
    }

    /**
     * Show public status check page.
     */
    public function checkStatus(Request $request): View
    {
        $query = $request->input('q', '');
        $candidate = null;

        if (!empty($query)) {
            $candidate = $this->recruitmentService->checkApplicationStatus($query);
        }

        return view('public.career.check-status', compact('candidate', 'query'));
    }

    /**
     * Show candidate self-update form.
     */
    public function selfUpdate(string $id): View
    {
        $candidate = $this->recruitmentService->checkApplicationStatus($id);
        if (!$candidate) {
            abort(404, 'Data pelamar tidak ditemukan.');
        }

        return view('public.career.self-update', compact('candidate'));
    }

    /**
     * Store candidate self-updated data.
     */
    public function storeSelfUpdate(CandidateSelfUpdateRequest $request, string $id): RedirectResponse
    {
        try {
            $this->recruitmentService->updateCandidateStatus(
                recruitmentId: $id,
                newStatus: 'Screening',
                notes: 'Data diperbarui secara mandiri oleh pelamar.',
                user: 'Pelamar (Self-Update)'
            );

            return back()->with('success', 'Data lamaran Anda berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function consentEvidence(Request $request, string $stage): array
    {
        return [
            'stage' => $stage,
            'accepted' => true,
            'server_timestamp' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'device' => substr((string) $request->input('consent_device', $request->input('device')), 0, 120),
            'client_timestamp' => $request->input('consent_timestamp', $request->input('client_timestamp')),
            'latitude' => $request->input('consent_latitude', $request->input('latitude')),
            'longitude' => $request->input('consent_longitude', $request->input('longitude')),
            'location' => $request->input('consent_location', $request->input('location')),
        ];
    }
}
