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
  'Recruitment ID',
  'Full Name',
  'Position',
  'Email',
  'Phone',
  'Join Date',
  'Status',
  'Notes',
  'Created At'
];
