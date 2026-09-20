<?php

namespace App\Http\Controllers\Public;

use App\DTOs\EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\OutsourceApplyRequest;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use App\Services\RecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OutsourceApplyController extends Controller
{
    private const SUBMISSION_COMPLETED = 'public_outsource_submission_completed';

    protected EmployeeRepositoryInterface $employeeRepo;
    protected AuditLogRepositoryInterface $auditRepo;
    protected EmployeeIdGenerator $idGenerator;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        AuditLogRepositoryInterface $auditRepo,
        EmployeeIdGenerator $idGenerator
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->auditRepo = $auditRepo;
        $this->idGenerator = $idGenerator;
    }

    public function index(): View|RedirectResponse
    {
        if (session(self::SUBMISSION_COMPLETED)) {
            return redirect()->route('public.outsource.success');
        }

        $positions = app(\App\Services\JobPositionService::class)->getPositionNames();
        return view('public.outsource.apply', compact('positions'));
    }

    public function success(): View|RedirectResponse
    {
        if (!session(self::SUBMISSION_COMPLETED)) {
            return redirect()->route('public.outsource.apply');
        }

        return view('public.career.success', [
            'candidate' => null,
            'id' => null,
            'isOutsource' => true,
        ]);
    }

    public function store(OutsourceApplyRequest $request): RedirectResponse
    {
        if (session(self::SUBMISSION_COMPLETED)) {
            return redirect()->route('public.outsource.success');
        }

        $validated = $request->validated();

        $consentEvidence = [
            'stage' => 'outsource_agreement',
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

        $existingIds = $this->employeeRepo->getAll()
            ->pluck('employeeId')
            ->filter()
            ->map(fn ($id) => strtoupper(trim((string) $id)))
            ->all();

        $joinDate = $validated['tanggal_masuk'] ?? null;
        $employeeId = $this->idGenerator->generate($joinDate, $existingIds);
        $attempts = 0;
        while (in_array(strtoupper($employeeId), $existingIds, true) && $attempts < 10) {
            $employeeId = $this->idGenerator->generate($joinDate, $existingIds);
            $attempts++;
        }

        // Resolve kecamatan: dropdown first, fallback manual
        $kecamatan = $validated['kecamatan'] ?? null;
        if (empty($kecamatan)) {
            $kecamatan = $validated['kecamatan_manual'] ?? null;
        }
        $alamatKtp = $validated['alamat_ktp'] ?? '';
        if (!empty($kecamatan) && !empty($alamatKtp)) {
            $alamatKtp = $kecamatan . ', ' . $alamatKtp;
        }

        // BUG FIX #1 — Canonical phone: prepend +62 (visual prefix was display-only).
        $canonicalPhone = RecruitmentService::normalizePhone($validated['nomor_telepon'] ?? null);

        // BUG FIX #2 — City name: kota_nama is a hidden input populated by JS with the
        // human-readable city name (e.g. "KAB. PEMALANG") before submit.  The kota field
        // itself carries only the numeric region code (e.g. "3327").
        $cityRaw  = $validated['kota_nama'] ?? ($validated['kota'] ?? null);
        $cityName = (is_string($cityRaw) && !ctype_digit(trim((string) $cityRaw))) ? $cityRaw : null;

        $employee = new EmployeeData(
            employeeId: $employeeId,
            fullName: $validated['nama_lengkap'],
            nikNpwp: $validated['nik'],
            personalEmail: $validated['email_pribadi'],
            workingEmail: $validated['email_kantor'],
            mobilePhone: $canonicalPhone,
            branchName: $validated['cabang_penempatan'],
            division: $validated['divisi'],
            department: $validated['departemen'],
            areaKerja: $validated['area_kerja'],
            costCenter: $validated['cost_center'],
            lokasiKerja: $validated['lokasi_kerja'],
            jobPosition: $validated['posisi_jabatan'],
            jobLevel: $validated['job_level'],
            statusEmployee: $validated['status_karyawan'],
            joinDate: $validated['tanggal_masuk'],
            endDateContract: $validated['tanggal_berakhir_kontrak'],
            directSuperior: $validated['atasan_langsung'],
            indirectSuperior: $validated['atasan_tidak_langsung'],
            outsourceVendor: $validated['vendor_outsource'],
            outsourceContractSeq: 0,
            birthDate: $validated['birth_date'],
            birthPlace: $validated['tempat_lahir'],
            citizenIdAddress: $alamatKtp,
            residentialAddress: $validated['alamat_domisili'],
            npwp: $validated['npwp'],
            ptkpStatus: $validated['status_ptkp'],
            bankName: $validated['nama_bank'] ?? 'BCA',
            bankAccount: $validated['nomor_rekening'],
            bankAccountHolder: $validated['nama_pemilik_rekening'],
            bpjsKetenagakerjaan: $validated['bpjs_ketenagakerjaan'],
            bpjsKesehatan: $validated['bpjs_kesehatan'],
            gender: $validated['jenis_kelamin'],
            religion: $validated['agama'],
            bloodType: $validated['golongan_darah'],
            maritalStatus: $validated['status_pernikahan'],
            createdBy: 'Portal Outsource'
        );

        $this->employeeRepo->create($employee);

        $this->auditRepo->log(
            entityType: 'Outsource',
            entityId: $employeeId,
            action: 'created',
            field: 'Employee ID',
            oldValue: '-',
            newValue: [
                'employee_id' => $employeeId,
                'vendor' => $validated['vendor_outsource'],
                'agreement_evidence' => $consentEvidence,
            ],
            user: 'Public Applicant',
            source: 'Public'
        );

        session([self::SUBMISSION_COMPLETED => true]);

        return redirect()->route('public.outsource.success');
    }
}
