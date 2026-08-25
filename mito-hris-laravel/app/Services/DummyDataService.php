<?php

namespace App\Services;

use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Facades\Log;

/**
 * DummyDataService — mirrors GenerateDummyData.gs behavior.
 *
 * Generates demo data into Google Sheets:
 *   data_kandidat      — pending candidates
 *   kandidat_hold      — hold candidates
 *   kandidat_accepted  — accepted candidates (with offering data)
 *   kandidat_blacklist — blacklisted candidates
 *   Employee           — employees from accepted + legacy
 *   kandidat_probation — probation records
 *   Audit_Log          — activity trail
 *
 * Users sheet is NOT modified.
 */
class DummyDataService
{
    protected GoogleSheetsService $sheets;

    // --- Reference data (mirrors _GD_* in GAS) ---
    private array $sources = [
        'JobStreet', 'LinkedIn', 'Indeed', 'Instagram', 'Website Perusahaan',
        'Referensi Karyawan', 'Kampus / Career Fair', 'Loker.id', 'Karir.com',
        'Glassdoor', 'Walk In', 'Other',
    ];

    private array $positions = [
        'Director', 'GM', 'Manager', 'Supervisor', 'Team Lead', 'Senior Staff',
        'Staff', 'Junior Staff', 'IT Support', 'Network Engineer', 'Software Engineer',
        'Backend Developer', 'Frontend Developer', 'Fullstack Developer', 'HR Staff',
        'HR Recruiter', 'Finance Staff', 'Accounting Staff', 'Digital Marketing Specialist',
        'Graphic Designer', 'UI/UX Designer', 'Sales Executive', 'Purchasing Staff',
        'Warehouse Staff', 'Admin', 'Customer Service', 'Quality Control Staff',
        'Driver', 'Security', 'Office Boy',
    ];

    private array $departments = [
        'Human Resources', 'Finance', 'Accounting', 'Marketing', 'Digital Marketing',
        'Sales', 'IT', 'Engineering', 'Operations', 'Legal', 'GA', 'Warehouse',
        'Purchasing', 'Quality Control', 'Customer Service', 'Admin',
    ];

    private array $deptMap = [
        'IT Support'                   => ['IT', 'Engineering', 'Operations'],
        'Network Engineer'             => ['IT', 'Engineering'],
        'Software Engineer'            => ['IT', 'Engineering'],
        'Backend Developer'            => ['IT', 'Engineering'],
        'Frontend Developer'           => ['IT', 'Engineering'],
        'Fullstack Developer'          => ['IT', 'Engineering'],
        'HR Staff'                     => ['Human Resources'],
        'HR Recruiter'                 => ['Human Resources'],
        'Finance Staff'                => ['Finance', 'Accounting'],
        'Accounting Staff'             => ['Finance', 'Accounting'],
        'Digital Marketing Specialist' => ['Marketing', 'Digital Marketing'],
        'Graphic Designer'             => ['Marketing', 'Digital Marketing'],
        'UI/UX Designer'               => ['Marketing', 'Digital Marketing', 'IT'],
        'Sales Executive'              => ['Sales'],
        'Purchasing Staff'             => ['Purchasing', 'Warehouse'],
        'Warehouse Staff'              => ['Warehouse', 'Operations'],
        'Admin'                        => ['Admin', 'GA', 'Human Resources'],
        'Customer Service'             => ['Customer Service'],
        'Quality Control Staff'        => ['Quality Control', 'Operations'],
        'Driver'                       => ['GA', 'Operations', 'Warehouse'],
        'Security'                     => ['GA', 'Operations'],
        'Office Boy'                   => ['Admin', 'GA'],
    ];

    private array $educations  = ['SD', 'SMP', 'SMA/SMK', 'D3', 'S1', 'S2', 'S3'];
    private array $workExps    = ['Fresh Graduate', '1-2 Tahun', '3-5 Tahun', '5-10 Tahun', 'Lebih dari 10 Tahun'];
    private array $empStatuses = ['Employed Full Time', 'Employed Contract', 'Part Time', 'Freelance', 'Unemployed', 'Resigned', 'Fresh Graduate'];
    private array $availables  = ['Segera', '1 Minggu', '2 Minggu', '1 Bulan', '2 Bulan', '3 Bulan', 'Negosiasi'];
    private array $maritals    = ['Belum Menikah', 'Menikah', 'Cerai'];
    private array $cities      = ['Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Barat', 'Jakarta Utara', 'Jakarta Timur', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Medan', 'Makassar', 'Balikpapan', 'Denpasar', 'Palembang'];
    private array $streets     = ['Jl. Sudirman', 'Jl. Thamrin', 'Jl. Gatot Subroto', 'Jl. Diponegoro', 'Jl. Ahmad Yani', 'Jl. Imam Bonjol', 'Jl. Hayam Wuruk', 'Jl. Gajah Mada', 'Jl. Veteran', 'Jl. Pahlawan', 'Jl. Merdeka', 'Jl. Asia Afrika', 'Jl. Kemang', 'Jl. Fatmawati'];
    private array $companies   = ['PT Telkom Indonesia', 'PT Bank Mandiri', 'PT Pertamina', 'PT PLN', 'PT Garuda Indonesia', 'PT Astra International', 'PT Unilever Indonesia', 'PT Indofood Sukses Makmur', 'PT BRI', 'PT BCA', 'PT Gojek Indonesia', 'PT Tokopedia', 'PT Traveloka', 'PT Shopee Indonesia'];
    private array $maleNames   = ['Ahmad', 'Budi', 'Dedi', 'Eko', 'Fajar', 'Gilang', 'Hendra', 'Irfan', 'Joko', 'Krisna', 'Luthfi', 'Muhammad', 'Nanda', 'Prasetyo', 'Rizki', 'Satria', 'Wahyu', 'Yoga', 'Aditya', 'Bagus', 'Dimas', 'Farhan', 'Hafiz', 'Indra', 'Kurniawan', 'Lukman', 'Maulana', 'Nugroho', 'Rian', 'Surya'];
    private array $femaleNames = ['Ani', 'Bunga', 'Citra', 'Dewi', 'Eka', 'Fitri', 'Gita', 'Hana', 'Indah', 'Kartika', 'Lestari', 'Maya', 'Nina', 'Putri', 'Ratna', 'Sari', 'Wati', 'Yunita', 'Ayu', 'Dian', 'Elsa', 'Fiona', 'Hani', 'Intan', 'Kirana', 'Luna', 'Mega', 'Nabila', 'Pratiwi', 'Rina'];
    private array $lastNames   = ['Susanto', 'Wijaya', 'Pratama', 'Kurniawan', 'Setiawan', 'Saputra', 'Hidayat', 'Santoso', 'Putra', 'Nugroho', 'Suryadi', 'Wibowo', 'Rahman', 'Firmansyah', 'Gunawan', 'Hartono', 'Budiman', 'Siregar', 'Purba', 'Simanjuntak', 'Purnama', 'Prasetyo', 'Wahyudi', 'Lestari'];
    private array $hrNotes     = ['Kandidat memiliki pengalaman yang relevan.', 'CV lengkap, perlu follow up interview.', 'Kandidat direferensikan tim internal.', 'Hasil tes technical baik, perlu evaluasi.', 'Komunikasi sangat baik saat screening.', ''];
    private array $holdReasons = ['Kandidat meminta penundaan jadwal interview', 'Posisi belum resmi dibuka', 'Budget rekrutmen belum tersedia', 'Menunggu hasil background check'];
    private array $blReasons   = ['Tidak hadir interview tanpa konfirmasi', 'Data CV terbukti tidak sesuai', 'Pelanggaran etika selama seleksi', 'Memberikan informasi palsu'];
    private array $internalCos = ['PT Mahakarya Sukses Indonesia', 'PT Stein Perkasa Internasional', 'PT Perkasa Injeksi Indonesia', 'PT Mitra Elektro Perkasa'];
    private array $divisions   = ['RnD & aftersales', 'Sales', 'FAT & GA', 'Manufacture', 'E-Commerce', 'IT', 'Digital Marketing', 'Marketing', 'Creative', 'HR & Legal'];
    private array $areas       = ['Head Office (HO)', 'Depo Jakarta', 'Depo Bandung', 'Depo Surabaya', 'Pabrik'];
    private array $jobLevels   = ['Associate', 'Supervisor', 'Manager'];
    private array $ptkp        = ['TK/0', 'TK/1', 'K/0', 'K/1', 'K/2', 'K/3'];
    private array $religions   = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha'];
    private array $bloodTypes  = ['A', 'B', 'AB', 'O'];
    private array $superiors   = ['Budi Santoso', 'Siti Nurhaliza', 'Ahmad Wijaya', 'Dewi Lestari', 'Eko Prasetyo'];
    private array $empTypes    = ['Project', 'PKWTT', 'PKWT', 'Outsource', 'Intern'];
    private array $contractDurs = ['3 Bulan', '6 Bulan', '1 Tahun', '2 Tahun'];
    private array $empStatEmp  = ['Permanent', 'Contract', 'Probation'];
    private array $branches    = ['PT Mahakarya Sukses Indonesia', 'PT Stein Perkasa Internasional', 'PT Perkasa Injeksi Indonesia', 'PT Mitra Elektro Perkasa'];

    // Used to avoid duplicates
    private array $usedEmails  = [];
    private array $usedPhones  = [];
    private array $usedNiks    = [];
    private int $counter       = 0;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
    }

    public function generateAll(int $count = 50): array
    {
        $this->usedEmails = [];
        $this->usedPhones = [];
        $this->usedNiks   = [];
        $this->counter    = 0;

        try {
            $now = now()->timezone('Asia/Jakarta');

            // Build candidate pool scaled to $count
            $pendingCount   = $count;
            $holdCount      = max(1, (int)($count * 0.3));
            $acceptedCount  = max(1, (int)($count * 0.6));
            $blacklistCount = max(1, (int)($count * 0.3));

            $pool = $this->buildPool($now, $pendingCount, $holdCount, $acceptedCount, $blacklistCount);

            // Clear existing data rows (preserve headers)
            $this->clearSheetData('data_kandidat');
            $this->clearSheetData('kandidat_hold');
            $this->clearSheetData('kandidat_accepted');
            $this->clearSheetData('kandidat_blacklist');
            $this->clearSheetData('Employee');
            $this->clearSheetData('kandidat_probation');
            $this->clearSheetData('Audit_Log');

            // Write each sheet
            $this->writePendingSheet($pool['pending']);
            $this->writeHoldSheet($pool['hold'], $now);
            $this->writeAcceptedSheet($pool['accepted'], $now);
            $this->writeBlacklistSheet($pool['blacklist'], $now);
            $employeesWritten = $this->writeEmployeeSheet($pool['accepted'], $now);
            $probationWritten = $this->writeProbationSheet($pool['accepted'], $now);
            $auditWritten     = $this->writeAuditLog($pool['all'], $now);

            return [
                'pending'   => count($pool['pending']),
                'hold'      => count($pool['hold']),
                'accepted'  => count($pool['accepted']),
                'blacklist' => count($pool['blacklist']),
                'employees' => $employeesWritten,
                'probation' => $probationWritten,
                'audit'     => $auditWritten,
            ];
        } catch (\Throwable $e) {
            Log::error('DummyDataService::generateAll error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // ==============================================================
    // POOL BUILDER
    // ==============================================================
    private function buildPool($now, int $pendingN, int $holdN, int $acceptedN, int $blacklistN): array
    {
        $pending = $hold = $accepted = $blacklist = [];

        for ($i = 0; $i < $pendingN; $i++) {
            $pending[] = $this->makeCandidate($now, ['status' => 'Pending']);
        }
        for ($i = 0; $i < $holdN; $i++) {
            $fuDate = $now->copy()->addDays(rand(7, 30))->format('Y-m-d');
            $hold[] = $this->makeCandidate($now, [
                'status'           => 'Hold',
                'holdReason'       => $this->pick($this->holdReasons),
                'holdFollowUpDate' => $fuDate,
            ]);
        }
        for ($i = 0; $i < $acceptedN; $i++) {
            $accepted[] = $this->makeCandidate($now, ['status' => 'Accepted']);
        }
        for ($i = 0; $i < $blacklistN; $i++) {
            $blacklist[] = $this->makeCandidate($now, [
                'status'              => 'Blacklist',
                'blacklistReason'     => $this->pick($this->blReasons),
                'blacklistDate'       => $now->format('Y-m-d H:i:s'),
                'blacklistUpdatedBy'  => 'HR Admin',
            ]);
        }

        return [
            'pending'   => $pending,
            'hold'      => $hold,
            'accepted'  => $accepted,
            'blacklist' => $blacklist,
            'all'       => array_merge($pending, $hold, $accepted, $blacklist),
        ];
    }

    private function makeCandidate($now, array $overrides = []): array
    {
        $this->counter++;
        $isMale   = rand(0, 1) === 1;
        $fullName = $this->randomName($isMale);
        $pos      = $this->pick($this->positions);
        $exp      = $this->pick($this->workExps);
        $age      = min(50, $this->ageForExp($exp));
        $city     = $this->pick($this->cities);
        $marital  = $this->pick($this->maritals);

        // Unique phone
        do {
            $phone = '08' . $this->randDigits(10);
        } while (isset($this->usedPhones[$phone]));
        $this->usedPhones[$phone] = true;

        // Unique email
        $nameParts = explode(' ', strtolower($fullName));
        $base      = ($nameParts[0] ?? 'user') . '.' . preg_replace('/[^a-z]/', '', $nameParts[1] ?? 'x');
        $emailIdx  = rand(1, 999);
        $email     = $base . $emailIdx . '@gmail.com';
        while (isset($this->usedEmails[$email])) {
            $emailIdx++;
            $email = $base . $emailIdx . '@gmail.com';
        }
        $this->usedEmails[$email] = true;

        // Unique NIK
        do {
            $nik = $this->randDigits(16);
        } while (isset($this->usedNiks[$nik]));
        $this->usedNiks[$nik] = true;

        $birthYear = $now->year - $age;
        $birthDate = $birthYear . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $address   = $this->pick($this->streets) . ' No. ' . rand(1, 150) . ', ' . $city;
        $cvLink    = rand(0, 9) > 3 ? 'https://drive.google.com/file/d/' . $this->randAlphanum(15) . '/view' : '';

        $daysAgo   = rand(5, 120);
        $createdAt = $now->copy()->subDays($daysAgo)->subHours(rand(0, 8))->subMinutes(rand(0, 59));
        $updatedAt = $createdAt->copy()->addDays(rand(0, 30));
        if ($updatedAt->gt($now)) {
            $updatedAt = $now->copy();
        }

        $recId     = 'REC-' . $createdAt->format('Ymd') . '-' . str_pad($this->counter, 6, '0', STR_PAD_LEFT);
        $salary    = $this->randSalary($pos);
        $company   = $exp === 'Fresh Graduate' ? '-' : $this->pick($this->companies);
        $dept      = $this->deptForPos($pos);

        $candidate = [
            'recruitmentId'            => $recId,
            'createdDate'              => $createdAt->format('Y-m-d H:i:s'),
            'fullName'                 => $fullName,
            'nik'                      => $nik,
            'birthDate'                => $birthDate,
            'age'                      => $age,
            'gender'                   => $isMale ? 'Laki-laki' : 'Perempuan',
            'maritalStatus'            => $marital,
            'email'                    => $email,
            'phone'                    => $phone,
            'address'                  => $address,
            'city'                     => $city,
            'positionApplied'          => $pos,
            'education'                => $this->pick($this->educations),
            'workExperience'           => $exp,
            'lastCompany'              => $company,
            'currentEmploymentStatus'  => $this->pick($this->empStatuses),
            'availableToJoin'          => $this->pick($this->availables),
            'expectedSalary'           => $salary,
            'recruitmentSource'        => $this->pick($this->sources),
            'cvLink'                   => $cvLink,
            'status'                   => 'Pending',
            'hrNotes'                  => $this->pick($this->hrNotes),
            'createdBy'                => 'Demo Generator',
            'updatedAt'                => $updatedAt->format('Y-m-d H:i:s'),
            'holdReason'               => '',
            'holdFollowUpDate'         => '',
            'blacklistReason'          => '',
            'blacklistDate'            => '',
            'blacklistUpdatedBy'       => '',
            'employeeId'               => '',
            // internal use
            '_department'              => $dept,
            '_isMale'                  => $isMale,
        ];

        foreach ($overrides as $k => $v) {
            $candidate[$k] = $v;
        }

        return $candidate;
    }

    // ==============================================================
    // ROW BUILDERS
    // ==============================================================
    private function candidateRow(array $c, bool $withProcessed = false): array
    {
        $row = [
            $c['recruitmentId'],
            $c['createdDate'],
            $c['fullName'],
            "'" . $c['nik'],
            $c['birthDate'],
            $c['age'],
            $c['gender'],
            $c['maritalStatus'],
            $c['email'],
            "'" . $c['phone'],
            $c['address'],
            $c['city'],
            $c['positionApplied'],
            $c['education'],
            $c['workExperience'],
            $c['lastCompany'],
            $c['currentEmploymentStatus'],
            $c['availableToJoin'],
            $c['expectedSalary'],
            $c['recruitmentSource'],
            $c['cvLink'],
            $c['status'],
            $c['hrNotes'],
            $c['createdBy'],
            $c['updatedAt'],
            // Extra headers
            $c['holdReason'],
            $c['holdFollowUpDate'],
            $c['blacklistReason'],
            $c['blacklistDate'],
            $c['blacklistUpdatedBy'],
            $c['employeeId'],
        ];

        if ($withProcessed) {
            $row[] = $c['processedDate'] ?? '';
            $row[] = $c['processedBy'] ?? '';
        }

        return $row;
    }

    // ==============================================================
    // SHEET WRITERS
    // ==============================================================
    private function writePendingSheet(array $candidates): void
    {
        if (empty($candidates)) return;
        $rows = array_map(fn($c) => $this->candidateRow($c, false), $candidates);
        $this->batchAppend('data_kandidat', $rows);
    }

    private function writeHoldSheet(array $candidates, $now): void
    {
        if (empty($candidates)) return;
        $nowStr = $now->format('Y-m-d H:i:s');
        $rows = array_map(function ($c) use ($nowStr) {
            $c['processedDate'] = $nowStr;
            $c['processedBy']   = 'Demo Generator';
            return $this->candidateRow($c, true);
        }, $candidates);
        $this->batchAppend('kandidat_hold', $rows);
    }

    private function writeAcceptedSheet(array $candidates, $now): void
    {
        if (empty($candidates)) return;
        $nowStr = $now->format('Y-m-d H:i:s');
        $rows = [];

        foreach ($candidates as $idx => $c) {
            $c['processedDate'] = $nowStr;
            $c['processedBy']   = 'Demo Generator';
            $base = $this->candidateRow($c, true);

            // Distribute offering states (0=none, 1=waiting, 2=accepted+onboarded)
            $segment = $idx % 3;

            $offeringCreated = $offeringUpdated = $offeringCreatedBy = $offeringUpdatedBy = '';
            $offerCompany = $offerPosition = $offerDept = $offerSalary = $offerJoinDate = '';
            $offerNotes = $offerResponse = $offerRespNotes = $offerRespDate = $offerRespBy = '';
            $onboardingStatus = $onboardingDate = $onboardingBy = '';
            $offerDivision = $offerJobLevel = $offerAreaKerja = $offerLokasiKerja = '';
            $offerSalaryBasic = $offerPulsa = $offerTransport = $offerEmpStatus = $offerContractDur = $offerWorkingHours = '';

            if ($segment >= 1) {
                $offeringCreated   = $now->copy()->subDays(rand(5, 30))->format('Y-m-d H:i:s');
                $offeringCreatedBy = 'Demo Generator';
                $offerCompany      = $this->pick($this->internalCos);
                $offerPosition     = $c['positionApplied'];
                $offerDept         = $c['_department'] ?? '';
                $offerDivision     = $this->pick($this->divisions);
                $offerJobLevel     = $this->pick($this->jobLevels);
                $offerAreaKerja    = $this->pick($this->areas);
                $offerLokasiKerja  = $c['city'];
                $offerSalary       = (string)$this->randSalary($c['positionApplied']);
                $offerJoinDate     = $now->copy()->subDays(rand(1, 20))->format('Y-m-d');
                $offerResponse     = 'Menunggu';
                $offerSalaryBasic  = $offerSalary;
                $offerPulsa        = '100000';
                $offerTransport    = '200000';
                $offerEmpStatus    = 'Perjanjian Kerja Waktu Tertentu';
                $offerContractDur  = $this->pick($this->contractDurs);
                $offerWorkingHours = 'Senin – Jumat mulai pukul 08.00 – 17.00 WIB';
            }

            if ($segment >= 2) {
                $respTs            = $now->copy()->subDays(rand(1, 15))->format('Y-m-d H:i:s');
                $offerResponse     = 'Diterima';
                $offerRespNotes    = 'Kandidat menyetujui semua syarat dan kondisi.';
                $offerRespDate     = $respTs;
                $offerRespBy       = 'Demo Generator';
                $onboardingStatus  = 'Probation';
                $onboardingDate    = $respTs;
                $onboardingBy      = 'Demo Generator';
            }

            $row = array_merge($base, [
                $offeringCreated,   // Offering Created
                $offeringUpdated,   // Offering Updated
                $offeringCreatedBy, // Offering Created By
                $offeringUpdatedBy, // Offering Updated By
                $offerCompany,      // Offering Company Entity
                $offerPosition,     // Offering Position
                $offerSalary,       // Offering Salary
                $offerJoinDate,     // Offering Join Date
                $offerNotes,        // Offering Notes
                $offerResponse,     // Offering Response
                $offerRespNotes,    // Offering Response Notes
                $offerRespDate,     // Offering Response Date
                $offerRespBy,       // Offering Response By
                $onboardingStatus,  // Onboarding Status
                $onboardingDate,    // Onboarding Date
                $onboardingBy,      // Onboarding By
                $offerDivision,     // Offering Division
                $offerJobLevel,     // Offering Job Level
                $offerLokasiKerja,  // Offering Lokasi Kerja
                $offerSalaryBasic,  // Offering Salary Basic
                $offerPulsa,        // Offering Allow Pulsa
                $offerTransport,    // Offering Allow Transport
                $offerEmpStatus,    // Offering Employment Status
                $offerContractDur,  // Offering Contract Duration
                $offerWorkingHours, // Offering Working Hours
            ]);

            $rows[] = $row;
        }

        $this->batchAppend('kandidat_accepted', $rows);
    }

    private function writeBlacklistSheet(array $candidates, $now): void
    {
        if (empty($candidates)) return;
        $nowStr = $now->format('Y-m-d H:i:s');
        $rows = array_map(function ($c) use ($nowStr) {
            $c['processedDate'] = $nowStr;
            $c['processedBy']   = 'Demo Generator';
            return $this->candidateRow($c, true);
        }, $candidates);
        $this->batchAppend('kandidat_blacklist', $rows);
    }

    private function writeEmployeeSheet(array $acceptedCandidates, $now): int
    {
        $nowStr    = $now->format('Y-m-d H:i:s');
        $rows      = [];
        $seqByDate = [];

        // From accepted candidates (30 employees)
        foreach ($acceptedCandidates as $c) {
            $joinDate = $now->copy()->subDays(rand(10, 365))->format('Y-m-d');
            $dateKey  = str_replace('-', '', $joinDate);
            $seqByDate[$dateKey] = ($seqByDate[$dateKey] ?? 0) + 1;
            $empId    = $dateKey . str_pad((string)$seqByDate[$dateKey], 2, '0', STR_PAD_LEFT);
            $empType  = $this->pick($this->empTypes);
            $isContract = in_array($empType, ['PKWT', 'Outsource', 'Intern']);

            $rows[] = $this->buildEmployeeRow($empId, $c, $joinDate, $empType, $isContract, $nowStr);
        }

        // 20 legacy employees (not from recruitment)
        for ($i = 0; $i < 20; $i++) {
            $isMale   = rand(0, 1) === 1;
            $fullName = $this->randomName($isMale);
            $pos      = $this->pick($this->positions);
            $joinDate = $now->copy()->subDays(rand(365, 1825))->format('Y-m-d');
            $dateKey  = str_replace('-', '', $joinDate);
            $seqByDate[$dateKey] = ($seqByDate[$dateKey] ?? 0) + 1;
            $empId    = $dateKey . str_pad((string)$seqByDate[$dateKey], 2, '0', STR_PAD_LEFT);
            $empType  = $this->pick(['PKWTT', 'PKWT']);

            $fakeCandidate = [
                'fullName'       => $fullName,
                'positionApplied'=> $pos,
                '_department'    => $this->deptForPos($pos),
                '_isMale'        => $isMale,
                'city'           => $this->pick($this->cities),
                'email'          => strtolower(str_replace(' ', '.', $fullName)) . rand(1, 99) . '@example.com',
                'phone'          => '08' . $this->randDigits(10),
                'nik'            => $this->randDigits(16),
                'recruitmentId'  => '',
                'birthDate'      => ($now->year - rand(25, 45)) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
                'maritalStatus'  => $this->pick($this->maritals),
                'education'      => $this->pick($this->educations),
            ];

            $rows[] = $this->buildEmployeeRow($empId, $fakeCandidate, $joinDate, $empType, $empType === 'PKWT', $nowStr);
        }

        $this->batchAppend('Employee', $rows);
        return count($rows);
    }

    private function buildEmployeeRow(string $empId, array $c, string $joinDate, string $empType, bool $isContract, string $nowStr): array
    {
        $pos    = $c['positionApplied'] ?? $this->pick($this->positions);
        $dept   = $c['_department']     ?? $this->deptForPos($pos);
        $branch = $this->pick($this->branches);

        return [
            $empId,                                  // Employee ID
            $c['fullName'],                          // Full Name
            $branch,                                 // Branch Name
            $this->pick($this->divisions),           // Division
            $dept,                                   // Department
            $pos,                                    // Job Position (Location)
            $pos,                                    // Job Position
            $this->pick($this->areas),               // Area Kerja
            $c['city'] ?? $this->pick($this->cities), // Lokasi Kerja
            $this->pick($this->jobLevels),           // Job Level
            '',                                      // Grade
            $joinDate,                               // Join Date
            $this->pick($this->empStatEmp),          // Status Employee
            $this->pick($this->superiors),           // Direct Superior
            $this->pick($this->superiors),           // Indirect Superior
            $c['email'] ?? '',                       // Personal Email
            strtolower(explode('@', $c['email'] ?? 'user@x')[0]) . '@mito.co.id', // Working Email
            $isContract ? date('Y-m-d', strtotime('+1 year', strtotime($joinDate))) : '', // End Date (Contract)
            $this->pick($this->cities),              // Birth Place
            $c['birthDate'] ?? '',                   // Birth Date
            $c['address'] ?? ($this->pick($this->streets) . ' No. ' . rand(1, 100)), // Citizen ID Address
            $c['address'] ?? '',                     // Residential Address
            "'" . ($c['nik'] ?? $this->randDigits(16)), // NIK - NPWP 16 digit
            $this->randDigits(15),                   // NPWP
            $this->pick($this->ptkp),                // PTKP Status
            'BCA',                                   // Bank Name
            $this->randDigits(10),                   // Bank Account
            $c['fullName'],                          // Bank Account Holder
            $this->randDigits(11),                   // BPJS Ketenagakerjaan
            $this->randDigits(13),                   // BPJS Kesehatan
            "'" . ($c['phone'] ?? '0812' . $this->randDigits(8)), // Mobile Phone
            $this->pick($this->religions),           // Religion
            ($c['_isMale'] ?? true) ? 'Laki-laki' : 'Perempuan', // Gender
            $c['maritalStatus'] ?? $this->pick($this->maritals), // Marital Status
            $this->pick($this->bloodTypes),          // Blood Type
            '',                                      // Cost Center
            '',                                      // Job Position (Former)
            '',                                      // Type of Rotation
            '',                                      // Tanggal Mutasi/Demosi/Promosi
            '',                                      // Nomor SK
            '',                                      // Resign Date
            '',                                      // HR Notes
            '',                                      // Offboarding Type
            '',                                      // Offboarding Reason
            '',                                      // Offboarding Approved By
            '',                                      // Offboarding Documents Folder
            '',                                      // Offboarding Document Links
            '',                                      // Outsource Vendor
            'Demo Generator',                        // Created By
            $nowStr,                                 // Created At
            $nowStr,                                 // Updated At
        ];
    }

    private function writeProbationSheet(array $acceptedCandidates, $now): int
    {
        // Only candidates from the first 1/3 (segment=2 in accepted, i.e. onboarded ones)
        $probationCandidates = array_values(array_filter(
            $acceptedCandidates,
            fn($c, $idx) => ($idx % 3) === 2,
            ARRAY_FILTER_USE_BOTH
        ));

        if (empty($probationCandidates)) return 0;

        $nowStr = $now->format('Y-m-d H:i:s');
        $rows   = [];
        $probSeq = 1;

        foreach ($probationCandidates as $idx => $c) {
            $contractStart = $now->copy()->subDays(rand(10, 60))->format('Y-m-d');
            $contractEnd   = date('Y-m-d', strtotime('+3 months', strtotime($contractStart)));
            $joinDate      = $contractStart;

            $decision = '';
            $evalDate = '';
            $avgScore = '';
            $scores   = array_fill(0, 5, '');

            // ~40% already have evaluation
            if ($idx % 5 < 2) {
                $evalDate  = $now->copy()->subDays(rand(1, 30))->format('Y-m-d H:i:s');
                $scores    = [rand(60, 100), rand(60, 100), rand(60, 100), rand(60, 100), rand(60, 100)];
                $avgScore  = number_format(array_sum($scores) / 5, 2);
                $decision  = (float)$avgScore >= 70 ? 'Lulus' : 'Perpanjang';
            }

            $status = $decision === 'Lulus' ? 'Completed' : ($decision === 'Perpanjang' ? 'Extended' : 'Ongoing');

            $rows[] = [
                'PRO-' . str_pad((string)$probSeq++, 6, '0', STR_PAD_LEFT), // Probation ID
                $c['employeeId'] ?? '',              // Employee ID
                $c['recruitmentId'],                 // Recruitment ID
                'PKB-' . rand(1000, 9999),           // Contract Number
                '3 Bulan',                           // Contract Duration
                $contractStart,                      // Contract Start
                $contractEnd,                        // Contract End
                $joinDate,                           // Join Date
                $status,                             // Status
                $joinDate,                           // Onboarding Date
                'Demo Generator',                    // Onboarding By
                $evalDate ? ('EVAL-' . rand(100, 999)) : '', // Eval ID
                $evalDate,                           // Eval Date
                $scores[0] ?? '',                    // Score Performance
                $scores[1] ?? '',                    // Score Discipline
                $scores[2] ?? '',                    // Score Communication
                $scores[3] ?? '',                    // Score Initiative
                $scores[4] ?? '',                    // Score Teamwork
                $avgScore,                           // Average Score
                $decision,                           // Decision
                '',                                  // Extension Duration
                '',                                  // New Contract Start
                '',                                  // New Contract End
                '',                                  // Evaluator Notes
                $evalDate ? 'HR Manager' : '',       // Evaluator
                '',                                  // SK Status
                '',                                  // Notes
                $nowStr,                             // Created At
                $nowStr,                             // Updated At
            ];
        }

        $this->batchAppend('kandidat_probation', $rows);
        return count($rows);
    }

    private function writeAuditLog(array $candidates, $now): int
    {
        $rows   = [];
        $nowStr = $now->format('Y-m-d H:i:s');

        foreach ($candidates as $c) {
            // Created entry
            $rows[] = [$c['recruitmentId'], 'Created', 'Status', '', 'Pending', 'Demo Generator', $c['createdDate']];

            // Status change entry
            if ($c['status'] !== 'Pending') {
                $rows[] = [$c['recruitmentId'], 'Status Changed', 'Status', 'Pending', $c['status'], 'HR Admin', $c['updatedAt']];
            }

            // Notes entry (occasionally)
            if (!empty($c['hrNotes']) && rand(0, 3) > 1) {
                $rows[] = [$c['recruitmentId'], 'Notes Updated', 'HR Notes', '', $c['hrNotes'], 'HR Admin', $c['updatedAt']];
            }
        }

        if (empty($rows)) return 0;
        $this->batchAppend('Audit_Log', $rows);
        return count($rows);
    }

    // ==============================================================
    // SHEET UTILITIES
    // ==============================================================
    private function clearSheetData(string $sheetName): void
    {
        try {
            $spreadsheetId = config('google.spreadsheet_id');
            $service       = $this->sheets->getSheetsService();

            // Get sheet metadata to find total rows
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            $sheetId     = null;
            $totalRows   = 0;
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheetName) {
                    $sheetId   = $sheet->getProperties()->getSheetId();
                    $totalRows = $sheet->getProperties()->getGridProperties()->getRowCount();
                    break;
                }
            }

            if ($sheetId === null || $totalRows <= 1) {
                return; // Sheet missing or only header
            }

            // Clear from row 2 to end
            $range    = "{$sheetName}!A2:ZZ{$totalRows}";
            $clearBody = new \Google\Service\Sheets\ClearValuesRequest();
            $service->spreadsheets_values->clear($spreadsheetId, $range, $clearBody);
            $this->sheets->clearCache($sheetName);
        } catch (\Throwable $e) {
            Log::warning("DummyDataService::clearSheetData({$sheetName}): " . $e->getMessage());
        }
    }

    private function batchAppend(string $sheetName, array $rows): void
    {
        if (empty($rows)) return;

        try {
            $spreadsheetId = config('google.spreadsheet_id');
            $service       = $this->sheets->getSheetsService();

            $body = new \Google\Service\Sheets\ValueRange(['values' => $rows]);
            $params = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->append(
                $spreadsheetId,
                "{$sheetName}!A1",
                $body,
                $params
            );
            $this->sheets->clearCache($sheetName);
        } catch (\Throwable $e) {
            Log::error("DummyDataService::batchAppend({$sheetName}): " . $e->getMessage());
            throw $e;
        }
    }

    // ==============================================================
    // HELPERS
    // ==============================================================
    private function pick(array $arr): string
    {
        return $arr[array_rand($arr)];
    }

    private function randomName(bool $isMale): string
    {
        $first = $this->pick($isMale ? $this->maleNames : $this->femaleNames);
        $last  = $this->pick($this->lastNames);
        return "{$first} {$last}";
    }

    private function randDigits(int $n): string
    {
        $s = '';
        for ($i = 0; $i < $n; $i++) $s .= rand(0, 9);
        return $s;
    }

    private function randAlphanum(int $n): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $s     = '';
        for ($i = 0; $i < $n; $i++) $s .= $chars[rand(0, strlen($chars) - 1)];
        return $s;
    }

    private function ageForExp(string $exp): int
    {
        return match (true) {
            $exp === 'Fresh Graduate' => rand(20, 25),
            $exp === '1-2 Tahun'     => rand(22, 28),
            $exp === '3-5 Tahun'     => rand(26, 33),
            $exp === '5-10 Tahun'    => rand(28, 38),
            default                  => rand(32, 50),
        };
    }

    private function deptForPos(string $pos): string
    {
        $candidates = $this->deptMap[$pos] ?? $this->departments;
        return $this->pick($candidates);
    }

    private function randSalary(string $pos): int
    {
        $map = [
            'Director'                     => [15000000, 30000000],
            'GM'                           => [12000000, 25000000],
            'Manager'                      => [8000000,  20000000],
            'Supervisor'                   => [5000000,  12000000],
            'Team Lead'                    => [5000000,  12000000],
            'Senior Staff'                 => [4000000,  10000000],
            'Staff'                        => [3500000,  8000000],
            'Software Engineer'            => [7000000,  15000000],
            'Backend Developer'            => [7000000,  14000000],
            'Frontend Developer'           => [6000000,  13000000],
            'Fullstack Developer'          => [8000000,  16000000],
            'HR Staff'                     => [4500000,  8000000],
            'Finance Staff'                => [5000000,  9000000],
            'Sales Executive'              => [4000000,  9000000],
            'Driver'                       => [3000000,  5500000],
            'Security'                     => [3000000,  5000000],
            'Office Boy'                   => [2500000,  4500000],
        ];
        $range = $map[$pos] ?? [3500000, 8000000];
        $raw   = rand($range[0], $range[1]);
        return (int)(round($raw / 500000) * 500000);
    }
}
