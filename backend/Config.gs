// ============================================================
// backend/Config.gs — KONSTANTA & KONFIGURASI GLOBAL
// ============================================================

var SHEET_NAME           = 'raw_kandidat';
var DASHBOARD_SHEET_NAME = 'raw_kandidat';
var AUDIT_SHEET_NAME     = 'Audit_Log';
var EMPLOYEE_SHEET_NAME  = 'Employee';
var STATUS_COLUMN_NAME   = 'Status';
var ID_COLUMN_NAME       = 'Recruitment ID';
var NOTES_COLUMN_NAME    = 'HR Notes';

// Kolom inti (jangan diubah urutannya — data lama bergantung pada ini)
var SHEET_HEADERS = [
  'Recruitment ID',
  'Created Date',
  'Full Name',
  'NIK',
  'Birth Date',
  'Age',
  'Gender',
  'Marital Status',
  'Email',
  'Phone',
  'Address',
  'City',
  'Position Applied',
  'Education',
  'Work Experience',
  'Last Company',
  'Current Employment Status',
  'Available to Join',
  'Expected Salary',
  'Recruitment Source',
  'CV Link',
  'Status',
  'HR Notes',
  'Created By',
  'Updated At'
];

// Kolom tambahan ATS (ditambahkan otomatis di akhir tanpa mengganggu data lama)
var EXTRA_HEADERS = [
  'Hold Reason',
  'Hold Follow Up Date',
  'Blacklist Reason',
  'Blacklist Date',
  'Blacklist Updated By',
  'Employee ID'
];

var EMPLOYEE_HEADERS = [
  'Employee ID',
  'Company Entity',
  'Employee Type',       // Outsource | PKWT | PKWTT | Intern
  'Full Name',
  'NIK',
  'Birth Date',
  'Age',
  'Gender',
  'Marital Status',
  'Email',
  'Phone',
  'Address',
  'City',
  'Education',
  'Work Experience',     // durasi, misal "3 Tahun"
  'Department',
  'Position',
  'Join Date',
  'Contract Start',
  'Contract End',
  'Contract Duration',   // "6 Bulan", "1 Tahun", dll — kosong jika PKWTT
  'Employment Status',   // Active | Resigned | Terminated | On Leave
  'Salary',
  'Salary Type',         // Monthly | Daily | Project-Based
  'Outsource Vendor',    // kosong jika bukan outsource
  'Contract Number',     // Nomor Kontrak/PKS vendor
  'District',            // Kecamatan
  'Recruitment ID',      // link ke raw_kandidat jika dari pipeline
  'Recruitment Source',
  'HR Notes',
  'Created By',
  'Updated At'
];

// ============================================================
// MASTER DATA CONFIGURATION
// ============================================================
var MASTER_DATA_SHEET   = 'master_data';
var MASTER_DATA_HEADERS = ['ID', 'Kategori', 'Nama', 'Deskripsi', 'Urutan', 'Aktif', 'Dibuat', 'Diubah'];

var MASTER_DATA_CATEGORIES = {
  recruitment_source:        { label: 'Sumber Rekrutmen',        icon: 'bi-link-45deg' },
  candidate_status:          { label: 'Status Kandidat',         icon: 'bi-flag-fill' },
  department:                { label: 'Departemen',              icon: 'bi-building' },
  position:                  { label: 'Posisi',                  icon: 'bi-person-workspace' },
  work_location:             { label: 'Lokasi Kerja',            icon: 'bi-geo-alt-fill' },
  employee_type:             { label: 'Tipe Karyawan',           icon: 'bi-person-badge' },
  education:                 { label: 'Pendidikan',              icon: 'bi-mortarboard-fill' },
  work_experience:           { label: 'Pengalaman Kerja',        icon: 'bi-briefcase-fill' },
  marital_status:            { label: 'Status Pernikahan',       icon: 'bi-heart-fill' },
  gender:                    { label: 'Jenis Kelamin',           icon: 'bi-gender-male' },
  current_employment_status: { label: 'Status Kerja Saat Ini',   icon: 'bi-person-check-fill' },
  available_to_join:         { label: 'Ketersediaan Bergabung',  icon: 'bi-calendar-check-fill' },
  employment_status:         { label: 'Status Employment',       icon: 'bi-shield-fill-check' },
  contract_duration:         { label: 'Durasi Kontrak',          icon: 'bi-clock-fill' },
  salary_type:               { label: 'Tipe Gaji',              icon: 'bi-cash-stack' },
  company_entity:            { label: 'Entitas Perusahaan',      icon: 'bi-building' },
  interview_result:          { label: 'Hasil Interview',         icon: 'bi-clipboard-check-fill' }
};

var DEFAULT_MASTER_DATA = {
  recruitment_source: ['Website', 'Job Fair', 'Referral', 'Social Media', 'Agency', 'Walk In', 'Other'],
  candidate_status:   ['Pending', 'Interview', 'Accepted', 'Hold', 'Blacklist', 'Rejected'],
  department:         ['Human Resources', 'Finance', 'Marketing', 'Operations', 'IT', 'Sales', 'Legal'],
  position:           ['Staff', 'Supervisor', 'Manager', 'Director', 'Intern', 'Outsource'],
  work_location:      ['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Medan'],
  employee_type:      ['Full Time', 'Part Time', 'Contract', 'Intern', 'Outsource']
};

// ============================================================
// AUDIT LOG CONFIGURATION
// ============================================================
var AUDIT_LOG_SHEET_NAME = 'Audit_Log';
var AUDIT_LOG_HEADERS = ['Recruitment ID', 'Action', 'Field', 'Old Value', 'New Value', 'User', 'Timestamp'];

// ============================================================
// OUTSOURCE CONFIGURATION
// ============================================================
var OUTSOURCE_SHEET_NAME = 'Outsource';
var OUTSOURCE_HEADERS = [
  'Outsource ID',
  'Vendor',
  'Employee Name',
  'NIK',
  'Position',
  'Department',
  'Work Location',
  'Company Entity',
  'Contract Start',
  'Contract End',
  'Contract Duration',
  'Salary',
  'Salary Type',
  'Status',
  'Notes',
  'Created By',
  'Created At',
  'Updated At'
];
