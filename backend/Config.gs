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
var MASTER_DATA_SHEET = "master_data";
// Sheet-sheet status kandidat
var HOLD_SHEET_NAME = "kandidat_hold";
var ACCEPTED_SHEET_NAME = "kandidat_accepted";
var BLACKLIST_SHEET_NAME = "kandidat_blacklist";
var PROBATION_SHEET_NAME = "kandidat_probation";

// ============= COLUMN LOOKUP KEYS =============
var STATUS_COLUMN_NAME = "Status";
var ID_COLUMN_NAME = "Recruitment ID";
var NOTES_COLUMN_NAME = "HR Notes";

// ============================================================
// SHEET: data_kandidat
// ============================================================
var SHEET_HEADERS = [
  "Recruitment ID",
  "Created Date",
  "Full Name",
  "NIK",
  "Birth Date",
  "Age",
  "Gender",
  "Marital Status",
  "Email",
  "Phone",
  "Address",
  "City",
  "Position Applied",
  "Education",
  "Work Experience",
  "Last Company",
  "Current Employment Status",
  "Available to Join",
  "Expected Salary",
  "Recruitment Source",
  "CV Link",
  "Status",
  "HR Notes",
  "Created By",
  "Updated At",
];

var EXTRA_HEADERS = [
  "Hold Reason",
  "Hold Follow Up Date",
  "Blacklist Reason",
  "Blacklist Date",
  "Blacklist Updated By",
  "Employee ID",
];

var RAW_KANDIDAT_COL = {};
SHEET_HEADERS.concat(EXTRA_HEADERS).forEach(function (h, i) {
  RAW_KANDIDAT_COL[h] = i + 1;
});

// ============================================================
// SHEET: Employee
// ============================================================
var EMPLOYEE_HEADERS = [
  "Employee ID",
  "Full Name",
  "Branch Name",
  "Division",
  "Department",
  "Job Position (Locaction)",
  "Job Position",
  "Area Kerja",
  "Lokasi Kerja",
  "Job Level",
  "Grade",
  "Join Date",
  "Status Employee",
  "Direct Superior",
  "Indirect Superior",
  "Personal Email",
  "Working Email",
  "End Date (Contract)",
  "Birth Place",
  "Birth Date",
  "Citizen ID Address",
  "Residential Address",
  "NIK - NPWP 16 digit",
  "NPWP",
  "PTKP Status",
  "Bank Name",
  "Bank Account",
  "Bank Account Holder",
  "BPJS Ketenagakerjaan",
  "BPJS Kesehatan",
  "Mobile Phone",
  "Religion",
  "Gender",
  "Marital Status",
  "Blood Type",
  "Cost Center",
  "Job Position (Former)",
  "Type of Rotation",
  "Tanggal Mutasi/Demosi/Promosi",
  "Nomor SK",
  "Resign Date",
  "HR Notes",
  // "Contract Number",
  // "Contract Duration",
  // "Start Date (Contract)",
  "Offboarding Type",
  "Offboarding Reason",
  "Offboarding Approved By",
  "Offboarding Documents Folder",
  "Offboarding Document Links",
  "Outsource Vendor",
  "Created By",
  "Created At",
  "Updated At",
];

var EMPLOYEE_COL = {};
EMPLOYEE_HEADERS.forEach(function (h, i) {
  EMPLOYEE_COL[h] = i + 1;
});

// ============================================================
// SHEET: kandidat_probation
// Transaksi evaluasi probation — referensi ke Employee sheet via Employee ID.
// Data karyawan (nama, posisi, dept, dll.) dibaca dari Employee sheet saat runtime,
// tidak disimpan ulang di sini untuk menghindari duplikasi.
// ============================================================
var PROBATION_HEADERS = [
  // -- Identitas
  "Probation ID",
  "Employee ID",
  "Recruitment ID",
  // -- Kontrak Probation
  "Contract Number",
  "Contract Duration",
  "Contract Start",
  "Contract End",
  "Join Date",
  // -- Status & Onboarding
  "Status",
  "Onboarding Date",
  "Onboarding By",
  // -- Evaluasi
  "Eval ID",
  "Eval Date",
  "Score Performance",
  "Score Discipline",
  "Score Communication",
  "Score Initiative",
  "Score Teamwork",
  "Average Score",
  "Decision",
  // -- Perpanjangan (diisi jika Decision = perpanjang)
  "Extension Duration",
  "New Contract Start",
  "New Contract End",
  // -- Catatan & SK
  "Evaluator Notes",
  "Evaluator",
  "SK Status",
  "Notes",
  // -- Audit
  "Created At",
  "Updated At",
];

var PROBATION_COL = {};
PROBATION_HEADERS.forEach(function (h, i) {
  PROBATION_COL[h] = i + 1;
});

// ============================================================
// SHEET: Audit_Log
// ============================================================
var AUDIT_LOG_HEADERS = [
  "Recruitment ID",
  "Action",
  "Field",
  "Old Value",
  "New Value",
  "User",
  "Timestamp",
];

var AUDIT_COL = {};
AUDIT_LOG_HEADERS.forEach(function (h, i) {
  AUDIT_COL[h] = i + 1;
});

var AUDIT_LOG_SHEET_NAME = AUDIT_SHEET_NAME;

// ============================================================
// SHEET: Users
// ============================================================
var USERS_HEADERS = [
  "Email",
  "Username",
  "Full Name",
  "Role",
  "Status",
  "Password Hash",
  "Last Login",
  "Created At",
  "Updated At",
  "Created By",
];

var USERS_COL = {};
USERS_HEADERS.forEach(function (h, i) {
  USERS_COL[h] = i + 1;
});

// ============================================================
// SHEET: kandidat_hold
// ============================================================
var HOLD_HEADERS = SHEET_HEADERS.concat(EXTRA_HEADERS).concat([
  "Processed Date",
  "Processed By",
]);

var HOLD_COL = {};
HOLD_HEADERS.forEach(function (h, i) {
  HOLD_COL[h] = i + 1;
});

// ============================================================
// SHEET: kandidat_accepted
// ============================================================
var ACCEPTED_HEADERS = SHEET_HEADERS.concat(EXTRA_HEADERS).concat([
  "Processed Date",
  "Processed By",
  "Offering Created",
  "Offering Updated",
  "Offering Created By",
  "Offering Updated By",
  "Offering Company Entity",
  "Offering Position",
  "Offering Department",
  "Offering Salary",
  "Offering Join Date",
  "Offering Benefit",
  "Offering Notes",
  "Offering Response",
  "Offering Response Notes",
  "Offering Response Date",
  "Offering Response By",
  "Onboarding Status",
  "Onboarding Date",
  "Onboarding By",
  // Extra job-offer fields — appended so existing sheets stay compatible
  "Offering Division",
  "Offering Job Level",
  "Offering Area Kerja",
  "Offering Lokasi Kerja",
  "Offering Direct Superior",
  "Offering Grade",
]);

var ACCEPTED_COL = {};
ACCEPTED_HEADERS.forEach(function (h, i) {
  ACCEPTED_COL[h] = i + 1;
});

// ============================================================
// SHEET: kandidat_blacklist
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
// MASTER DATA
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
  offboarding_type: [
    "Resignation",
    "Termination",
    "Retirement",
    "Contract Finished",
  ],
  gender: ["Laki-laki", "Perempuan"],
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
  education: ["SD", "SMP", "SMA/SMK", "D3", "S1", "S2", "S3"],
  work_experience: [
    "Fresh Graduate",
    "1-2 Tahun",
    "3-5 Tahun",
    "5-10 Tahun",
    "Lebih dari 10 Tahun",
  ],
  marital_status: ["Belum Menikah", "Menikah", "Cerai"],
};

var MASTER_DATA_EMPLOYEE_COL_MAP = {
  department: "Department",
  position: "Job Position (Locaction)",
  marital_status: "Marital Status",
  gender: "Gender",
};
