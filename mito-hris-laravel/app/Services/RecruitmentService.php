<?php

namespace App\Services;

use App\DTOs\CandidateData;
use App\DTOs\EmployeeData;
use App\Enums\CandidateStatus;
use App\Events\CandidateApplied;
use App\Events\CandidateStatusChanged;
use App\Events\EmployeeHired;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Google\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;

class RecruitmentService
{
    protected CandidateRepositoryInterface $candidateRepo;
    protected EmployeeRepositoryInterface $employeeRepo;
    protected GoogleDriveService $drive;

    public function __construct(
        CandidateRepositoryInterface $candidateRepo,
        EmployeeRepositoryInterface $employeeRepo,
        GoogleDriveService $drive
    ) {
        $this->candidateRepo = $candidateRepo;
        $this->employeeRepo = $employeeRepo;
        $this->drive = $drive;
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

        $oldStatus = $candidate->status ?? 'New';
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

        $oldStatus = $candidate->status ?? 'New';
        
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

        $oldStatus = $candidate->status ?? 'New';
        
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
     * Accept candidate and move to kandidat_accepted sheet.
     */
    public function acceptCandidateToEmployee(string $recruitmentId, array $extraEmployeeData = [], ?string $user = null): bool
    {
        $candidate = $this->candidateRepo->findById($recruitmentId);
        if (!$candidate) {
            throw new RuntimeException("Kandidat dengan ID {$recruitmentId} tidak ditemukan.");
        }

        $oldStatus = $candidate->status ?? 'New';
        
        $extraData = [
            'Processed Date' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Processed By' => $user ?? 'HR Administrator',
        ];

        $success = $this->candidateRepo->moveToSheet($recruitmentId, 'candidates_accepted', $extraData);
        if ($success) {
            event(new CandidateStatusChanged($recruitmentId, $oldStatus, CandidateStatus::ACCEPTED->value, 'Accepted', $user));
        }

        return $success;
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
