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
  'Recruitment ID',      // link ke raw_kandidat jika dari pipeline
  'Recruitment Source',
  'HR Notes',
  'Created By',
  'Updated At'
];
