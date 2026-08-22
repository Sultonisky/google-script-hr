<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ApplyJobRequest;
use App\Http\Requests\Public\CandidateSelfUpdateRequest;
use App\Services\RecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerController extends Controller
{
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

        session(['candidate_consent' => true]);

        return redirect()->route('public.career.form');
    }

    /**
     * Step 3: Candidate Application Form (Validated against Consent).
     */
    public function form(): View|RedirectResponse
    {
        if (!session('candidate_consent')) {
            return redirect()->route('public.career.index')
                ->with('error', 'Harap membaca dan menyetujui syarat & ketentuan pendaftaran terlebih dahulu.');
        }

        return view('public.career.apply');
    }

    /**
     * Step 4: Store new candidate application into Google Sheets.
     */
    public function store(ApplyJobRequest $request): RedirectResponse
    {
        // Re-flash consent so the form page stays accessible if we redirect back
        session(['candidate_consent' => true]);

        try {
            $candidate = $this->recruitmentService->apply(
                validatedData: $request->validated(),
                cvFile: $request->file('cv_file')
            );

            session()->forget('candidate_consent');

            return redirect()->route('public.career.success', ['id' => $candidate->recruitmentId])
                ->with('success', "Lamaran Anda berhasil dikirim! Nomor Pendaftaran Anda adalah: {$candidate->recruitmentId}");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show success page after application.
     */
    public function success(Request $request): View
    {
        $id = $request->query('id', '');
        $candidate = $id ? $this->recruitmentService->checkApplicationStatus($id) : null;

        return view('public.career.success', compact('candidate', 'id'));
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
}
