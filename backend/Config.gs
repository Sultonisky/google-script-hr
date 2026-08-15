// ============================================================
// backend/Config.gs — SINGLE SOURCE OF TRUTH FOR ALL SCHEMAS
// ============================================================
// Every backend module MUST reference these constants.
// No module may define its own headers or column indexes.
// ============================================================

// ============= SHEET NAMES =============
var SHEET_NAME = "data_kandidat";
var DASHBOARD_SHEET_NAME = "data_kandidat";
var EMPLOYEE_SHEET_NAME = "Employee";
var AUDIT_SHEET_NAME = "Audit_Log";
var USERS_SHEET_NAME = "Users";
var OFFBOARDING_SHEET_NAME = "Offboarding";
var MASTER_DATA_SHEET = "master_data"; // Deprecated: master data now derived from Employee sheet

// Sheet-sheet status kandidat (independen dari data_kandidat)
var HOLD_SHEET_NAME = "kandidat_hold";
var ACCEPTED_SHEET_NAME = "kandidat_accepted";
var BLACKLIST_SHEET_NAME = "kandidat_blacklist";

// ============= COLUMN LOOKUP KEYS =============
var STATUS_COLUMN_NAME = "Status";
var ID_COLUMN_NAME = "Recruitment ID";
var NOTES_COLUMN_NAME = "HR Notes";

// ============================================================
// SHEET: data_kandidat
// Purpose: Candidate registration database
// ============================================================
var SHEET_HEADERS = [
  "Recruitment ID", // 1
  "Created Date", // 2
  "Full Name", // 3
  "NIK", // 4
  "Birth Date", // 5
  "Age", // 6
  "Gender", // 7
  "Marital Status", // 8
  "Email", // 9
  "Phone", // 10
  "Address", // 11
  "City", // 12
  "Position Applied", // 13
  "Education", // 14
  "Work Experience", // 15
  "Last Company", // 16
  "Current Employment Status", // 17
  "Available to Join", // 18
  "Expected Salary", // 19
  "Recruitment Source", // 20
  "CV Link", // 21
  "Status", // 22
  "HR Notes", // 23
  "Created By", // 24
  "Updated At", // 25
];

// Extra ATS columns (appended after core headers for backward compatibility)
var EXTRA_HEADERS = [
  "Hold Reason", // 26
  "Hold Follow Up Date", // 27
  "Blacklist Reason", // 28
  "Blacklist Date", // 29
  "Blacklist Updated By", // 30
  "Employee ID", // 31
];

var RAW_KANDIDAT_COL = {};
SHEET_HEADERS.concat(EXTRA_HEADERS).forEach(function (h, i) {
  RAW_KANDIDAT_COL[h] = i + 1;
});

// ============================================================
// SHEET: Employee
// Purpose: Complete employee master — final authority
// ============================================================
var EMPLOYEE_HEADERS = [
  "Employee ID", // 1
  "Recruitment ID", // 2
  "Full Name", // 3
  "Position", // 4
  "Email", // 5
  "Phone", // 6
  "Join Date", // 7
  "Status", // 8
  "Notes", // 9
  "Created At", // 10
  "Company Entity", // 11
  "Employee Type", // 12
  "NIK", // 13
  "Birth Date", // 14
  "Age", // 15
  "Gender", // 16
  "Marital Status", // 17
  "Address", // 18
  "City", // 19
  "Education", // 20
  "Work Experience", // 21
  "Department", // 22
  "Division", // 23
  "Branch", // 24
  "Contract Start", // 25
  "Contract End", // 26
  "Contract Duration", // 27
  "Employment Status", // 28
  "Salary", // 29
  "Salary Type", // 30
  "Outsource Vendor", // 31
  "Contract Number", // 32
  "District", // 33
  "Recruitment Source", // 34
  "HR Notes", // 35
  "Created By", // 36
  "Updated At", // 37
];

var EMPLOYEE_COL = {};
EMPLOYEE_HEADERS.forEach(function (h, i) {
  EMPLOYEE_COL[h] = i + 1;
});

// ============================================================
// SHEET: raw_outsource  (DEPRECATED — outsource data now goes directly
// to the Employee sheet via simpanDataOutsource)
// Constants retained for reference / backward compatibility.
// ============================================================
var OUTSOURCE_SHEET_NAME = "raw_outsource"; // Deprecated: no longer created by setupSpreadsheet()
var OUTSOURCE_HEADERS = [
  "Outsource ID", // 1
  "Created Date", // 2
  "Full Name", // 3
  "NIK", // 4
  "Birth Date", // 5
  "Age", // 6
  "Gender", // 7
  "Marital Status", // 8
  "Email", // 9
  "Phone", // 10
  "Address", // 11
  "City", // 12
  "Province", // 13
  "Position", // 14
  "Department", // 15
  "Join Date", // 16
  "Education", // 17
  "Work Experience", // 18
  "Vendor Company", // 19
  "Contract Number", // 20
  "Contract Duration", // 21
  "Salary", // 22
  "Recruitment Source", // 23
  "Status", // 24
  "Notes", // 25
  "Created By", // 26
  "Updated At", // 27
];

var OUTSOURCE_COL = {};
OUTSOURCE_HEADERS.forEach(function (h, i) {
  OUTSOURCE_COL[h] = i + 1;
});

// ============================================================
// SHEET: Audit_Log
// Purpose: Activity tracking for all modules
// ============================================================
var AUDIT_LOG_HEADERS = [
  "Recruitment ID", // 1
  "Action", // 2
  "Field", // 3
  "Old Value", // 4
  "New Value", // 5
  "User", // 6
  "Timestamp", // 7
];

var AUDIT_COL = {};
AUDIT_LOG_HEADERS.forEach(function (h, i) {
  AUDIT_COL[h] = i + 1;
});

// Alias used by some modules
var AUDIT_LOG_SHEET_NAME = AUDIT_SHEET_NAME;

// ============================================================
// SHEET: Users
// Purpose: Google Workspace whitelist, login validation, RBAC
// ============================================================
var USERS_HEADERS = [
  "Email", // 1
  "Username", // 2
  "Full Name", // 3
  "Role", // 4
  "Status", // 5
  "Password Hash", // 6
  "Last Login", // 7
  "Created At", // 8
  "Updated At", // 9
  "Created By", // 10
];

var USERS_COL = {};
USERS_HEADERS.forEach(function (h, i) {
  USERS_COL[h] = i + 1;
});

// ============================================================
// SHEET: kandidat_hold
// Kolom = SHEET_HEADERS + EXTRA_HEADERS + kolom khusus hold
// ============================================================
var HOLD_HEADERS = SHEET_HEADERS.concat(EXTRA_HEADERS).concat([
  "Processed Date", // tanggal dipindahkan ke sheet ini
  "Processed By", // user yang mengubah status
]);

var HOLD_COL = {};
HOLD_HEADERS.forEach(function (h, i) {
  HOLD_COL[h] = i + 1;
});

// ============================================================
// SHEET: kandidat_accepted
// Kolom = SHEET_HEADERS + EXTRA_HEADERS + kolom khusus accepted
// ============================================================
var ACCEPTED_HEADERS = SHEET_HEADERS.concat(EXTRA_HEADERS).concat([
  "Processed Date",
  "Processed By",
  "Offering Created",        // timestamp saat offering pertama dibuat
  "Offering Updated",        // timestamp jika ada update offering
  "Offering Created By",     // user HR yang pertama membuat offering
  "Offering Updated By",     // user HR yang terakhir update offering
  "Offering Company Entity", // entitas perusahaan penawaran
  "Offering Position",       // posisi yang ditawarkan
  "Offering Department",     // departemen penawaran
  "Offering Salary",         // gaji ditawarkan
  "Offering Join Date",      // tanggal bergabung penawaran
  "Offering Benefit",        // benefit/fasilitas
  "Offering Notes",          // catatan tambahan offering
  "Offering Response",       // "Menunggu" | "Diterima" | "Ditolak"
  "Offering Response Notes", // catatan HR saat update respons
  "Offering Response Date",  // timestamp update respons
  "Offering Response By",    // user HR yang update respons
  "Onboarding Status",       // "Belum Onboarding" | "Probation" | "Active"
  "Onboarding Date",         // timestamp proses onboarding dilakukan
  "Onboarding By",           // user HR yang proses onboarding
]);

var ACCEPTED_COL = {};
ACCEPTED_HEADERS.forEach(function (h, i) {
  ACCEPTED_COL[h] = i + 1;
});

// ============================================================
// SHEET: kandidat_blacklist
// Kolom = SHEET_HEADERS + EXTRA_HEADERS + kolom khusus blacklist
// ============================================================
var BLACKLIST_HEADERS = SHEET_HEADERS.concat(EXTRA_HEADERS).concat([
  "Processed Date",
  "Processed By",
]);

var BLACKLIST_COL = {};
BLACKLIST_HEADERS.forEach(function (h, i) {
  BLACKLIST_COL[h] = i + 1;
});

// ============================================================
// SHEET: Offboarding
// Purpose: Employee resignation, termination, retirement, contract end
// ============================================================
var OFFBOARDING_HEADERS = [
  "Offboarding ID", // 1
  "Employee ID", // 2
  "Full Name", // 3
  "Position", // 4
  "Department", // 5
  "Join Date", // 6
  "Last Working Date", // 7
  "Offboarding Type", // 8
  "Reason", // 9
  "Approved By", // 10
  "Notes", // 11
  "Status", // 12
  "Archived", // 13
  "Created By", // 14
  "Created At", // 15
  "Updated At", // 16
];

var OFFBOARD_COL = {};
OFFBOARDING_HEADERS.forEach(function (h, i) {
  OFFBOARD_COL[h] = i + 1;
});

// ============================================================
// SHEET: Evaluasi_Probation
// Purpose: History evaluasi karyawan probation (bisa multiple per karyawan)
// ============================================================
var PROBATION_EVAL_SHEET_NAME = 'Evaluasi_Probation';

var PROBATION_EVAL_HEADERS = [
  'Eval ID',           // 1  — auto-generate
  'Employee ID',       // 2
  'Recruitment ID',    // 3
  'Full Name',         // 4
  'Position',          // 5
  'Department',        // 6
  'Contract Start',    // 7
  'Contract End',      // 8
  'Eval Date',         // 9  — tanggal evaluasi dilakukan
  'Skor Kinerja',      // 10 — 1-10
  'Skor Kedisiplinan', // 11 — 1-10
  'Skor Komunikasi',   // 12 — 1-10
  'Skor Inisiatif',    // 13 — 1-10
  'Skor Teamwork',     // 14 — 1-10
  'Nilai Rata-rata',   // 15 — auto-hitung (avg 5 skor)
  'Keputusan',         // 16 — "Lulus → Karyawan Tetap" | "Tidak Lulus → Perpanjang Probation"
  'Durasi Perpanjang', // 17 — diisi jika Tidak Lulus (e.g. "3 Bulan")
  'Kontrak Baru Start',// 18 — diisi jika perpanjang
  'Kontrak Baru End',  // 19 — diisi jika perpanjang
  'Catatan Evaluator', // 20
  'Evaluator',         // 21 — email HR yang evaluasi
  'Created At',        // 22
  'Status SK',         // 23 — "Pending" | "SK Diterbitkan"
];

var PROBATION_EVAL_COL = {};
PROBATION_EVAL_HEADERS.forEach(function(h, i) {
  PROBATION_EVAL_COL[h] = i + 1;
});
// ============================================================
var MASTER_DATA_HEADERS = [
  "ID",
  "Kategori",
  "Nama",
  "Deskripsi",
  "Urutan",
  "Aktif",
  "Dibuat",
  "Diubah",
];

var MASTERDATA_COL = {};
MASTER_DATA_HEADERS.forEach(function (h, i) {
  MASTERDATA_COL[h] = i + 1;
});

var MASTER_DATA_CATEGORIES = {
  recruitment_source: { label: "Sumber Rekrutmen", icon: "bi-link-45deg" },
  candidate_status: { label: "Status Kandidat", icon: "bi-flag-fill" },
  department: { label: "Departemen", icon: "bi-building" },
  position: { label: "Posisi", icon: "bi-person-workspace" },
  work_location: { label: "Lokasi Kerja", icon: "bi-geo-alt-fill" },
  employee_type: { label: "Tipe Karyawan", icon: "bi-person-badge" },
  education: { label: "Pendidikan", icon: "bi-mortarboard-fill" },
  work_experience: { label: "Pengalaman Kerja", icon: "bi-briefcase-fill" },
  marital_status: { label: "Status Pernikahan", icon: "bi-heart-fill" },
  gender: { label: "Jenis Kelamin", icon: "bi-gender-male" },
  current_employment_status: {
    label: "Status Kerja Saat Ini",
    icon: "bi-person-check-fill",
  },
  available_to_join: {
    label: "Ketersediaan Bergabung",
    icon: "bi-calendar-check-fill",
  },
  employment_status: {
    label: "Status Employment",
    icon: "bi-shield-fill-check",
  },
  contract_duration: { label: "Durasi Kontrak", icon: "bi-clock-fill" },
  salary_type: { label: "Tipe Gaji", icon: "bi-cash-stack" },
  company_entity: { label: "Entitas Perusahaan", icon: "bi-building" },
  interview_result: {
    label: "Hasil Interview",
    icon: "bi-clipboard-check-fill",
  },
  offboarding_type: { label: "Tipe Offboarding", icon: "bi-box-arrow-right" },
};

var DEFAULT_MASTER_DATA = {
  recruitment_source: [
    "JobStreet",
    "LinkedIn",
    "Indeed",
    "Instagram",
    "Website Perusahaan",
    "Referensi Karyawan",
    "Kampus / Career Fair",
    "Loker.id",
    "Karir.com",
    "Glassdoor",
    "Walk In",
    "Other",
  ],
  candidate_status: [
    "Pending",
    "Interview",
    "Accepted",
    "Hold",
    "Blacklist",
    "Rejected",
  ],
  department: [
    "Human Resources",
    "Finance",
    "Marketing",
    "Operations",
    "IT",
    "Sales",
    "Legal",
  ],
  position: [
    "Staff",
    "Supervisor",
    "Manager",
    "Director",
    "Intern",
    "Outsource",
  ],
  work_location: [
    "Jakarta",
    "Bandung",
    "Surabaya",
    "Semarang",
    "Yogyakarta",
    "Medan",
  ],
  employee_type: ["Project", "PKWTT", "PKWT", "Outsource", "Intern"],
  offboarding_type: [
    "Resignation",
    "Termination",
    "Retirement",
    "Contract Finished",
  ],
  gender: ["Laki-laki", "Perempuan"],
  company_entity: [
    "PT Mahakarya Sukses Indonesia",
    "PT Stein Perkasa Internasional",
    "PT Perkasa Injeksi Indonesia",
    "PT Mitra Elektro Perkasa",
  ],
  current_employment_status: [
    "Employed Full Time",
    "Employed Contract",
    "Part Time",
    "Freelance",
    "Unemployed",
    "Resigned",
    "Fresh Graduate",
  ],
  available_to_join: [
    "Segera",
    "1 Minggu",
    "2 Minggu",
    "1 Bulan",
    "2 Bulan",
    "3 Bulan",
    "Negosiasi",
  ],
  education: [
    "SD",
    "SMP",
    "SMA/SMK",
    "D3",
    "S1",
    "S2",
    "S3",
  ],
  work_experience: [
    "Fresh Graduate",
    "1-2 Tahun",
    "3-5 Tahun",
    "5-10 Tahun",
    "Lebih dari 10 Tahun",
  ],
  marital_status: ["Belum Menikah", "Menikah", "Cerai"],
};

// ============================================================
// MASTER DATA → EMPLOYEE SHEET COLUMN MAPPING
// Categories whose values can be derived (distinct) from the
// Employee sheet.  Categories NOT listed here fall back to
// DEFAULT_MASTER_DATA defaults.
// ============================================================
var MASTER_DATA_EMPLOYEE_COL_MAP = {
  recruitment_source: "Recruitment Source",
  department: "Department",
  position: "Position",
  employee_type: "Employee Type",
  education: "Education",
  work_experience: "Work Experience",
  marital_status: "Marital Status",
  employment_status: "Employment Status",
  contract_duration: "Contract Duration",
  salary_type: "Salary Type",
  company_entity: "Company Entity",
  gender: "Gender",
};
