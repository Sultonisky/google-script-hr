<?php

namespace App\Http\Controllers\Public;

use App\DTOs\EmployeeData;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutsourceApplyController extends Controller
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected AuditLogRepositoryInterface $auditRepo;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        AuditLogRepositoryInterface $auditRepo
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->auditRepo = $auditRepo;
    }

    public function index(): View
    {
        $positions = app(\App\Services\JobPositionService::class)->getPositionNames();
        return view('public.outsource.apply', compact('positions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap'              => ['required', 'string', 'min:3', 'max:255', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u'],
            'nik'                       => 'required|digits:16',
            'birth_date'                => 'required|string|max:10', // DD/MM/YYYY from JS auto-slash
            'tempat_lahir'              => 'required|string',
            'usia'                      => 'nullable|numeric',
            'jenis_kelamin'             => 'required|string',
            'agama'                     => 'required|string',
            'golongan_darah'            => 'required|string',
            'status_pernikahan'         => 'required|string',
            'email_pribadi'             => 'required|email',
            'email_kantor'              => 'required|email',
            'nomor_telepon'             => 'required|string',
            'provinsi'                  => 'required|string',
            'kota'                      => 'required|string',
            'kecamatan'                 => 'nullable|string',
            'kecamatan_manual'          => 'nullable|string',
            'alamat_ktp'                => 'required|string',
            'alamat_domisili'           => 'required|string',
            'cabang_penempatan'         => 'required|string',
            'vendor_outsource'          => 'required|string|min:3',
            'divisi'                    => 'required|string',
            'departemen'                => 'required|string',
            'area_kerja'                => 'required|string',
            'cost_center'               => 'required|string',
            'lokasi_kerja'              => 'required|string',
            'posisi_jabatan'            => 'required|string',
            'job_level'                 => 'required|string',
            'status_karyawan'           => 'required|string',
            'tanggal_masuk'             => 'required|date',
            'tanggal_berakhir_kontrak'  => 'required|date',
            'atasan_langsung'           => 'required|string',
            'atasan_tidak_langsung'     => 'required|string',
            'nama_bank'                 => 'nullable|string',
            'nomor_rekening'            => 'required|string',
            'nama_pemilik_rekening'     => 'required|string',
            'npwp'                      => 'required|string',
            'status_ptkp'               => 'required|string',
            'bpjs_ketenagakerjaan'      => 'required|string',
            'bpjs_kesehatan'            => 'required|string',
            'agreement'                 => 'required|in:1',
        ], [
            'nama_lengkap.regex'       => 'Nama lengkap hanya boleh berisi huruf dan spasi.',
            'agreement.required'        => 'Anda harus menyetujui pernyataan keabsahan data untuk melanjutkan.',
        ]);

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

        $now = now()->timezone('Asia/Jakarta');
        $employeeId = 'EMP-OS-' . $now->format('Y') . '-' . str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        // Resolve kecamatan: dropdown first, fallback manual
        $kecamatan = $validated['kecamatan'] ?? null;
        if (empty($kecamatan)) {
            $kecamatan = $validated['kecamatan_manual'] ?? null;
        }
        $alamatKtp = $validated['alamat_ktp'] ?? '';
        if (!empty($kecamatan) && !empty($alamatKtp)) {
            $alamatKtp = $kecamatan . ', ' . $alamatKtp;
        }

        $employee = new EmployeeData(
            employeeId: $employeeId,
            fullName: $validated['nama_lengkap'],
            nikNpwp: $validated['nik'],
            personalEmail: $validated['email_pribadi'],
            workingEmail: $validated['email_kantor'],
            mobilePhone: $validated['nomor_telepon'],
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

        return redirect()->route('public.career.success', ['id' => $employeeId])
            ->with('success', "Data tenaga kerja outsource berhasil didaftarkan ke sistem HRIS MITO. Nomor ID Anda: {$employeeId}");
    }
}
