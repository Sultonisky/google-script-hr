// ============================================================
// backend/Config.gs — SINGLE SOURCE OF TRUTH FOR ALL SCHEMAS
// ============================================================
// Every backend module MUST reference these constants.
// No module may define its own headers or column indexes.
// ============================================================

// ============= SHEET NAMES =============
var SHEET_NAME = "raw_kandidat";
var DASHBOARD_SHEET_NAME = "raw_kandidat";
var EMPLOYEE_SHEET_NAME = "Employee";
var AUDIT_SHEET_NAME = "Audit_Log";
var USERS_SHEET_NAME = "Users";
var OFFBOARDING_SHEET_NAME = "Offboarding";
var MASTER_DATA_SHEET = "master_data"; // Deprecated: master data now derived from Employee sheet

// Sheet-sheet status kandidat (independen dari raw_kandidat)
var HOLD_SHEET_NAME = "kandidat_hold";
var ACCEPTED_SHEET_NAME = "kandidat_accepted";
var BLACKLIST_SHEET_NAME = "kandidat_blacklist";

// ============= COLUMN LOOKUP KEYS =============
var STATUS_COLUMN_NAME = "Status";
var ID_COLUMN_NAME = "Recruitment ID";
var NOTES_COLUMN_NAME = "HR Notes";

// ============================================================
// SHEET: raw_kandidat
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
  "Processed Date",    // tanggal dipindahkan ke sheet ini
  "Processed By",      // user yang mengubah status
]);

var HOLD_COL = {};
HOLD_HEADERS.forEach(function (h, i) { HOLD_COL[h] = i + 1; });

// ============================================================
// SHEET: kandidat_accepted
// Kolom = SHEET_HEADERS + EXTRA_HEADERS + kolom khusus accepted
// ============================================================
var ACCEPTED_HEADERS = SHEET_HEADERS.concat(EXTRA_HEADERS).concat([
  "Processed Date",
  "Processed By",
]);

var ACCEPTED_COL = {};
ACCEPTED_HEADERS.forEach(function (h, i) { ACCEPTED_COL[h] = i + 1; });

// ============================================================
// SHEET: kandidat_blacklist
// Kolom = SHEET_HEADERS + EXTRA_HEADERS + kolom khusus blacklist
// ============================================================
var BLACKLIST_HEADERS = SHEET_HEADERS.concat(EXTRA_HEADERS).concat([
  "Processed Date",
  "Processed By",
]);

var BLACKLIST_COL = {};
BLACKLIST_HEADERS.forEach(function (h, i) { BLACKLIST_COL[h] = i + 1; });

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
// MASTER DATA CONFIGURATION
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
    "Website",
    "Job Fair",
    "Referral",
    "Social Media",
    "Agency",
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
  employee_type: ["Full Time", "Part Time", "Contract", "Intern", "Outsource"],
  offboarding_type: [
    "Resignation",
    "Termination",
    "Retirement",
    "Contract Finished",
  ],
  gender: ["Male", "Female"],
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
    "Immediately",
    "1 Week",
    "2 Weeks",
    "1 Month",
    "2 Months",
    "3 Months",
    "Negotiable",
  ],
  education: [
    "Junior High School",
    "Senior High School (SMA)",
    "Vocational High School (SMK)",
    "Diploma (D3)",
    "Bachelor's Degree (S1)",
    "Master's Degree (S2)",
    "Doctoral Degree (S3)",
  ],
  work_experience: [
    "Fresh Graduate",
    "Less than 1 Year",
    "1-2 Years",
    "2-3 Years",
    "3-5 Years",
    "5-10 Years",
    "More than 10 Years",
  ],
  marital_status: [
    "Single",
    "Married",
    "Divorced",
    "Widowed",
  ],
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
