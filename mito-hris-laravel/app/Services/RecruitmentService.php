<?php

namespace App\Services;

use App\DTOs\CandidateData;
use App\DTOs\EmployeeData;
use App\Enums\CandidateStatus;
use App\Events\CandidateApplied;
use App\Events\CandidateStatusChanged;
use App\Events\EmployeeHired;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use App\Services\Google\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class RecruitmentService
{
    protected CandidateRepositoryInterface $candidateRepo;
    protected EmployeeRepositoryInterface $employeeRepo;
    protected GoogleDriveService $drive;
    protected AuditLogRepositoryInterface $auditRepo;
    protected EmployeeIdGenerator $idGenerator;

    public function __construct(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo,
        GoogleDriveService $drive,
        AuditLogRepositoryInterface $auditRepo,
        EmployeeIdGenerator $idGenerator
    ) {
        $this->candidateRepo = $candidateRepo;
        $this->employeeRepo = $employeeRepo;
        $this->drive = $drive;
        $this->auditRepo = $auditRepo;
        $this->idGenerator = $idGenerator;
    }

    /**
     * Submit a new job application from the Public Portal.
     */
    public function apply(array $validatedData, ?UploadedFile $cvFile = null): CandidateData
    {
        // Check for duplicate active application by NIK
        if (!empty($validatedData['nik'])) {
            $existing = $this->candidateRepo->findByNik($validatedData['nik']);
            if ($existing && !in_array($existing->status, ['Rejected', 'Deleted'])) {
                // If already blacklist, refuse
                if ($existing->status === 'Blacklist') {
                    throw new RuntimeException('NIK Anda terdaftar dalam daftar hitam (blacklist) sistem.');
                }
            }
        }

        // Extract CV link from input or upload
        $cvLink = $validatedData['cv_link'] ?? ($validatedData['cvLink'] ?? null);
        if ($cvFile !== null) {
            $uploadRes = $this->drive->uploadUploadedFile($cvFile);
            if ($uploadRes && isset($uploadRes['view_url'])) {
                $cvLink = $uploadRes['view_url'];
            }
        }

        // Resolve kecamatan: prefer dropdown value, fallback to manual input
        $kecamatan = $validatedData['kecamatan'] ?? null;
        if (empty($kecamatan)) {
            $kecamatan = $validatedData['kecamatan_manual'] ?? null;
        }

        // Build full address string including kecamatan if available
        $alamatDomisili = $validatedData['alamat_domisili'] ?? ($validatedData['alamat'] ?? ($validatedData['address'] ?? null));
        if (!empty($kecamatan) && !empty($alamatDomisili)) {
            $alamatDomisili = $kecamatan . ', ' . $alamatDomisili;
        }

        $candidate = new CandidateData(
            fullName: $validatedData['nama_lengkap'] ?? ($validatedData['full_name'] ?? null),
            nik: $validatedData['nik'] ?? null,
            birthDate: $validatedData['birth_date'] ?? ($validatedData['tanggal_lahir'] ?? null),
            age: $validatedData['usia'] ?? ($validatedData['age'] ?? null),
            gender: $validatedData['jenis_kelamin'] ?? ($validatedData['gender'] ?? null),
            maritalStatus: $validatedData['marital_status'] ?? null,
            email: $validatedData['email'] ?? null,
            phone: $validatedData['nomor_telepon'] ?? ($validatedData['no_telp'] ?? ($validatedData['phone'] ?? null)),
            address: $alamatDomisili,
            city: $validatedData['kota'] ?? ($validatedData['city'] ?? null),
            positionApplied: $validatedData['posisi_dilamar'] ?? ($validatedData['posisi'] ?? ($validatedData['position_applied'] ?? null)),
            education: $validatedData['pendidikan_terakhir'] ?? ($validatedData['pendidikan'] ?? ($validatedData['education'] ?? null)),
            workExperience: $validatedData['pengalaman_kerja'] ?? ($validatedData['work_experience'] ?? null),
            lastCompany: $validatedData['perusahaan_terakhir'] ?? ($validatedData['last_company'] ?? null),
            currentEmploymentStatus: $validatedData['status_bekerja'] ?? ($validatedData['status_kerja_saat_ini'] ?? null),
            availableToJoin: $validatedData['kesediaan_bergabung'] ?? ($validatedData['ketersediaan_bergabung'] ?? null),
            expectedSalary: $validatedData['ekspektasi_gaji'] ?? ($validatedData['gaji_yang_diharapkan'] ?? null),
            recruitmentSource: $validatedData['sumber_informasi'] ?? 'Website Perusahaan',
            cvLink: $cvLink,
            status: CandidateStatus::NEW->value,
            createdBy: 'Candidate',
            updatedAt: now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
        );

        $created = $this->candidateRepo->create($candidate);

        // Fire Domain Event for Audit Log & Cache Invalidation
        event(new CandidateApplied($created));

        $this->auditRepo->log(
            entityType: 'Candidate',
            entityId: $created->recruitmentId ?? 'NEW',
            action: 'consent_accepted',
            field: 'agreement_evidence',
            oldValue: null,
            newValue: $validatedData['consent_evidence'] ?? ['accepted' => true],
            user: 'Public Applicant',
            source: 'Public'
        );

        return $created;
    }

    /**
     * Update Candidate status in recruitment ATS pipeline.
     */
    public function updateCandidateStatus(string $recruitmentId, string $newStatus, ?string $notes = null, ?string $user = null): bool
    {
        $candidate = $this->candidateRepo->findById($recruitmentId);
        if (!$candidate) {
            throw new RuntimeException("Kandidat dengan ID {$recruitmentId} tidak ditemukan.");
        }

        $oldStatus = $candidate->status ?? 'Pending';
        $success = $this->candidateRepo->updateStatus($recruitmentId, $newStatus, $notes);

        if ($success) {
            event(new CandidateStatusChanged($recruitmentId, $oldStatus, $newStatus, $notes, $user));
        }

        return $success;
    }

    /**
     * Put candidate on Hold status with reason and follow-up date.
     */
    public function holdCandidate(string $recruitmentId, string $reason, ?string $followUpDate = null, ?string $notes = null, ?string $user = null): bool
    {
        $candidate = $this->candidateRepo->findById($recruitmentId);
        if (!$candidate) {
            throw new RuntimeException("Kandidat dengan ID {$recruitmentId} tidak ditemukan.");
        }

        $oldStatus = $candidate->status ?? 'Pending';
        
        $extraData = [
            'Hold Reason' => $reason,
            'Hold Follow Up Date' => $followUpDate ?? '',
            'Processed Date' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Processed By' => $user ?? 'HR Administrator',
        ];
        if ($notes !== null) {
            $extraData['HR Notes'] = $notes;
        }

        $success = $this->candidateRepo->moveToSheet($recruitmentId, 'candidates_hold', $extraData);
        if ($success) {
            event(new CandidateStatusChanged($recruitmentId, $oldStatus, CandidateStatus::HOLD->value, "Hold: {$reason}", $user));
        }

        return $success;
    }

    /**
     * Blacklist candidate with reason.
     */
    public function blacklistCandidate(string $recruitmentId, string $reason, ?string $notes = null, ?string $user = null): bool
    {
        $candidate = $this->candidateRepo->findById($recruitmentId);
        if (!$candidate) {
            throw new RuntimeException("Kandidat dengan ID {$recruitmentId} tidak ditemukan.");
        }

        $oldStatus = $candidate->status ?? 'Pending';
        
        $extraData = [
            'Blacklist Reason' => $reason,
            'Blacklist Date' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Blacklist Updated By' => $user ?? 'HR Administrator',
            'Processed Date' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Processed By' => $user ?? 'HR Administrator',
        ];
        if ($notes !== null) {
            $extraData['HR Notes'] = $notes;
        }

        $success = $this->candidateRepo->moveToSheet($recruitmentId, 'candidates_blacklist', $extraData);
        if ($success) {
            event(new CandidateStatusChanged($recruitmentId, $oldStatus, CandidateStatus::BLACKLIST->value, "Blacklist: {$reason}", $user));
        }

        return $success;
    }

    /**
     * Accept candidate: pindahkan ke kandidat_accepted.
     * 1:1 dengan GAS acceptCandidateToEmployee() — HANYA memindahkan kandidat
     * ke sheet kandidat_accepted dan menghapus dari data_kandidat.
     *
     * NOTE: Employee record dibuat nanti saat proses Kontrak PKWT
     * (processContractOnboarding), BUKAN saat accept dari Pending.
     */
    public function acceptCandidateToEmployee(string $recruitmentId, array $extraEmployeeData = [], ?string $user = null): bool
    {
        $candidate = $this->candidateRepo->findById($recruitmentId);
        if (!$candidate) {
            throw new RuntimeException("Kandidat dengan ID {$recruitmentId} tidak ditemukan.");
        }

        $oldStatus = $candidate->status ?? 'Pending';
        $user      = $user ?? 'HR Administrator';
        $nowStr    = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $extraData = [
            'Status'         => 'Accepted',
            'Processed Date' => $nowStr,
            'Processed By'   => $user,
        ];

        $success = $this->candidateRepo->moveToSheet($recruitmentId, 'candidates_accepted', $extraData);

        if ($success) {
            event(new CandidateStatusChanged($recruitmentId, $oldStatus, CandidateStatus::ACCEPTED->value, 'Accepted', $user));
        }

        return $success;
    }

    /**
     * Proses Kontrak PKWT & Onboarding — 1:1 dengan GAS processContractOnboarding().
     * Membuat record Employee (status "Contract"), menandai Onboarding Status di
     * sheet kandidat_accepted, menulis audit log, dan mengembalikan detail kontrak
     * (termasuk nomor PKWT & Employee ID) untuk generate PDF.
     *
     * @return array{success:bool, employeeId:string, recruitmentId:string, contractNumber:string, onboardingDate:string, onboardingBy:string, message:string}
     */
    public function processContractOnboarding(string $recruitmentId, array $contractData, ?string $user = null): array
    {
        $candidate = $this->candidateRepo->findById($recruitmentId);
        if (!$candidate) {
            throw new RuntimeException("Kandidat dengan ID {$recruitmentId} tidak ditemukan.");
        }

        // Guard: hanya kandidat yang offering-nya sudah Diterima yang boleh diproses (1:1 GAS)
        if (trim($candidate->offeringResponse ?? '') !== 'Diterima') {
            throw new RuntimeException('Kandidat belum menerima offering letter (Offering Response harus "Diterima"). Nilai saat ini: "' . ($candidate->offeringResponse ?? 'kosong') . '"');
        }

        $user = $user ?: 'HR Administrator';
        $now = now()->timezone('Asia/Jakarta');
        $nowStr = $now->format('Y-m-d H:i:s');

        // -- Employee ID (pakai yang ada, atau generate) ----------
        $employeeId = $candidate->employeeId ?: ($contractData['employee_id'] ?? '');
        if (empty($employeeId)) {
            $employeeId = $this->idGenerator->generate();
        }

        // -- Data kontrak ------------------------------------------
        $branchName = $contractData['branch_name'] ?? $candidate->offeringCompanyEntity ?? '';
        $division   = $contractData['division'] ?? $candidate->offeringDivision ?? '';
        $department = $contractData['department'] ?? $candidate->offeringDepartment ?? '';
        $position   = $contractData['position'] ?? $candidate->offeringPosition ?? $candidate->positionApplied ?? '';
        $jobLevel   = $contractData['job_level'] ?? $candidate->offeringJobLevel ?? '';
        $lokasiKerja = $contractData['lokasi_kerja'] ?? $candidate->offeringLokasiKerja ?? $candidate->city ?? '';
        $directSuperior = $contractData['direct_superior'] ?? '';
        $joinDate   = $contractData['join_date'] ?? $candidate->offeringJoinDate ?? '';
        $contractEnd = $contractData['contract_end'] ?? '';

        $titles = $this->composeEmployeeJobTitles($position, $jobLevel, $lokasiKerja);

        // -- Nomor PKWT (generate bila kosong) --------------------
        $contractNumber = trim($contractData['contract_number'] ?? '');
        if ($contractNumber === '') {
            $contractNumber = $this->generatePkwtNumber($branchName, $contractData['doc_date'] ?? $nowStr, $now);
        }

        // -- 1. Buat record Employee (status Contract) ------------
        $employee = new EmployeeData(
            employeeId: $employeeId,
            fullName: $candidate->fullName ?? '',
            branchName: $branchName,
            division: $division,
            department: $department,
            jobPositionLocation: $titles['jobPositionLocation'],
            jobPosition: $titles['jobPosition'],
            lokasiKerja: $lokasiKerja,
            jobLevel: $jobLevel,
            joinDate: $joinDate,
            statusEmployee: 'Contract',
            directSuperior: $directSuperior,
            personalEmail: $candidate->email ?? '',
            endDateContract: $contractEnd,
            birthPlace: $candidate->city ?? '',
            birthDate: $candidate->birthDate ?? '',
            citizenIdAddress: $candidate->address ?? '',
            residentialAddress: $candidate->address ?? '',
            nikNpwp: $candidate->nik ?? '',
            mobilePhone: $candidate->phone ?? '',
            gender: $candidate->gender ?? '',
            maritalStatus: $candidate->maritalStatus ?? '',
            hrNotes: $contractData['notes'] ?? '',
            createdBy: $user,
            createdAt: $nowStr,
            updatedAt: $nowStr
        );

        // Buat baru bila belum ada; kalau sudah ada, update jadi Contract
        $existingEmp = $this->employeeRepo->findById($employeeId);
        if ($existingEmp) {
            $this->employeeRepo->update($employeeId, [
                'Branch Name' => $branchName,
                'Division' => $division,
                'Department' => $department,
                'Job Position' => $titles['jobPosition'],
                'Job Position (Location)' => $titles['jobPositionLocation'],
                'Job Level' => $jobLevel,
                'Lokasi Kerja' => $lokasiKerja,
                'Direct Superior' => $directSuperior,
                'Join Date' => $joinDate,
                'End Date (Contract)' => $contractEnd,
                'Status Employee' => 'Contract',
                'Updated At' => $nowStr,
            ]);
        } else {
            $this->employeeRepo->create($employee);
        }

        // -- 2. Tandai Onboarding di sheet kandidat_accepted ------
        $this->candidateRepo->update($recruitmentId, [
            'Onboarding Status' => 'Contract',
            'Onboarding Date' => $nowStr,
            'Onboarding By' => $user,
            'Employee ID' => $employeeId,
        ]);

        // -- 3. Audit log (event + entri khusus Kontrak PKWT) -----
        event(new EmployeeHired($employee, $recruitmentId, $user));
        $this->auditRepo->log(
            entityType: 'Employee',
            entityId: $employeeId,
            action: 'created',
            field: 'Employment Status',
            oldValue: 'Accepted',
            newValue: "Contract — Employee {$employeeId} ({$contractNumber}) by {$user}",
            user: $user,
            source: 'Dashboard'
        );

        return [
            'success' => true,
            'employeeId' => $employeeId,
            'recruitmentId' => $recruitmentId,
            'contractNumber' => $contractNumber,
            'onboardingDate' => $nowStr,
            'onboardingBy' => $user,
            'message' => 'Kontrak PKWT berhasil diproses. Karyawan kini aktif berstatus Contract.',
        ];
    }

    /**
     * Susun Job Position & Job Position (Location) — 1:1 GAS composeEmployeeJobTitles_.
     *
     * @return array{jobPosition:string, jobPositionLocation:string}
     */
    private function composeEmployeeJobTitles(?string $position, ?string $jobLevel, ?string $lokasiKerja): array
    {
        $title = trim((string) $position);
        $level = trim((string) $jobLevel);
        $loc   = trim((string) $lokasiKerja);

        $noLoc = $title;
        if ($level && $title && stripos($title, $level) === false) {
            $noLoc = $title . ' ' . $level;
        } elseif (!$title && $level) {
            $noLoc = $level;
        }

        $withLoc = $noLoc;
        if ($loc && $noLoc && strpos($noLoc, "({$loc})") === false) {
            $withLoc = $noLoc . " ({$loc})";
        } elseif (!$noLoc && $loc) {
            $withLoc = $loc;
        }

        return ['jobPosition' => $noLoc, 'jobPositionLocation' => $withLoc];
    }

    /**
     * Generate nomor Surat PKWT: NNN/{branchCode}-HR/PKWT/{RomawiBulan}/{tahun}.
     * Port dari GAS generatePkwtNumber_ + toRomanMonth_ (counter harian via Cache).
     */
    private function generatePkwtNumber(string $branchName, string $docDate, \Illuminate\Support\Carbon $now): string
    {
        $bLower = strtolower($branchName);
        $branchCode = 'MSI';
        if (str_contains($bLower, 'stein')) $branchCode = 'SPI';
        elseif (str_contains($bLower, 'injeksi')) $branchCode = 'PII';
        elseif (str_contains($bLower, 'mitra') || str_contains($bLower, 'elektro')) $branchCode = 'MEP';

        $docObj = $docDate ? \Illuminate\Support\Carbon::parse($docDate) : $now;
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][$docObj->month - 1];

        $datePart = $now->format('Ymd');
        $key = "PKWT_COUNTER_{$datePart}";
        $lock = Cache::lock("lock_{$key}", 10);
        try {
            $lock->block(10);
            $seq = (int) Cache::get($key, 0) + 1;
            Cache::put($key, $seq, $now->endOfDay());
        } finally {
            $lock->release();
        }

        return sprintf('%03d/%s-HR/PKWT/%s/%d', $seq, $branchCode, $roman, $docObj->year);
    }

    /**
     * Check application status for public applicant portal.
     */
    public function checkApplicationStatus(string $query): ?CandidateData
    {
        $query = trim($query);
        if (empty($query)) return null;

        if (str_starts_with(strtoupper($query), 'REC-')) {
            return $this->candidateRepo->findById($query);
        }

        return $this->candidateRepo->findByNik($query);
    }
}
