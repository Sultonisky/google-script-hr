// ============================================================
// backend/Outsource.gs — OUTSOURCE EMPLOYEE REGISTRATION
// Data masuk langsung ke sheet Employee (bukan raw_outsource).
// OS adalah karyawan aktif yang belum terdaftar di HRIS.
// ============================================================

// ---- Generator ID Employee untuk OS: EMP-YYYY-XXXX ----
// Reuses generateEmployeeId_ dari IdGenerator.gs

// ---- Validasi form OS ----
function validateOutsourceForm_(f) {
  if (!f.full_name || f.full_name.trim().length < 3)
    return 'Nama lengkap wajib diisi (minimal 3 karakter).';
  if (!f.nik || !/^[0-9]{16}$/.test(f.nik))
    return 'NIK harus tepat 16 digit angka.';
  if (!f.birth_date)
    return 'Tanggal lahir wajib diisi.';
  if (!f.age || Number(f.age) < 17)
    return 'Usia minimal 17 tahun.';
  if (!f.gender)
    return 'Jenis kelamin wajib dipilih.';
  if (!f.marital_status)
    return 'Status pernikahan wajib dipilih.';
  if (!f.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email))
    return 'Format email tidak valid.';
  if (!f.phone || !/^62[0-9]{8,12}$/.test(f.phone))
    return 'Nomor HP tidak valid.';
  if (!f.address || f.address.trim() === '')
    return 'Alamat wajib diisi.';
  if (!f.city || f.city.trim() === '')
    return 'Kota/Kabupaten wajib diisi.';
  if (!f.position || f.position.trim() === '')
    return 'Posisi/jabatan wajib diisi.';
  if (!f.employee_type)
    return 'Tipe karyawan wajib dipilih.';
  if (!f.join_date)
    return 'Tanggal mulai kerja wajib diisi.';
  if (!f.education)
    return 'Pendidikan terakhir wajib dipilih.';
  if (!f.employment_status)
    return 'Status kepegawaian wajib dipilih.';
  if (f.salary === undefined || f.salary === '' || isNaN(Number(f.salary)))
    return 'Gaji wajib diisi dengan angka.';
  // Outsource wajib isi vendor
  if (f.employee_type === 'Outsource' && (!f.outsource_vendor || f.outsource_vendor.trim() === ''))
    return 'Nama outsource vendor wajib diisi untuk tipe Outsource.';
  // Kontrak berbatas waktu (Outsource & PKWT) wajib isi durasi
  if ((f.employee_type === 'Outsource' || f.employee_type === 'PKWT') && !f.contract_duration)
    return 'Durasi kontrak wajib dipilih untuk tipe ' + f.employee_type + '.';
  return null;
}

// ---- Simpan data OS langsung ke sheet Employee ----
function simpanDataOutsource(formObject) {
  try {
    var validationError = validateOutsourceForm_(formObject);
    if (validationError) return 'Error: ' + validationError;

    var now        = new Date();
    var employeeId = generateEmployeeId_(now);
    var createdAt  = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    var empSheet = getOrCreateEmployeeSheet_();

    empSheet.appendRow([
      employeeId,                                    // Employee ID
      formObject.company_entity || 'PT Mahakarya Sukses Indonesia', // Company Entity
      formObject.employee_type,                      // Employee Type
      formObject.full_name,                          // Full Name
      "'" + formObject.nik,                          // NIK
      formObject.birth_date,                         // Birth Date
      Number(formObject.age) || '',                  // Age
      formObject.gender,                             // Gender
      formObject.marital_status,                     // Marital Status
      formObject.email,                              // Email
      "'" + formObject.phone,                        // Phone
      formObject.address,                            // Address
      formObject.city,                               // City
      formObject.education,                          // Education
      formObject.work_experience || '',              // Work Experience
      formObject.department || '',                   // Department
      formObject.position,                           // Position
      formObject.join_date,                          // Join Date
      formObject.contract_start || '',               // Contract Start
      formObject.contract_end   || '',               // Contract End
      formObject.contract_duration || '',            // Contract Duration
      formObject.employment_status || 'Active',      // Employment Status
      Number(formObject.salary) || 0,                // Salary
      formObject.salary_type || 'Monthly',           // Salary Type
      formObject.outsource_vendor || '',             // Outsource Vendor
      formObject.contract_number || '',              // Contract Number (PKS)
      formObject.district || '',                     // District/Kecamatan
      '',                                            // Recruitment ID (kosong — bukan dari pipeline)
      formObject.recruitment_source || '',           // Recruitment Source
      '',                                            // HR Notes
      'System',                                      // Created By
      createdAt                                      // Updated At
    ]);

    writeAuditLog_(employeeId, 'Employee Registered (OS)', '-', formObject.employee_type + ' — ' + formObject.position);
    return 'Sukses';
  } catch (error) {
    return 'Error: ' + error.toString();
  }
}

// ---- Ambil daftar karyawan dari Employee sheet (untuk cek duplikat NIK) ----
function getOutsourceList() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(EMPLOYEE_SHEET_NAME);
  if (!sheet || sheet.getLastRow() < 2) return [];

  var lastRow  = sheet.getLastRow();
  var lastCol  = sheet.getLastColumn();
  var values   = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var headers  = values[0];

  var colIndex = {};
  headers.forEach(function(h, i) { colIndex[String(h).trim()] = i; });

  var result = [];
  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (!row.join('').toString().trim()) continue;
    result.push({
      employeeId:       String(row[colIndex['Employee ID']]        || ''),
      companyEntity:    String(row[colIndex['Company Entity']]     || ''),
      employeeType:     String(row[colIndex['Employee Type']]      || ''),
      createdDate:      row[colIndex['Updated At']] instanceof Date
                          ? Utilities.formatDate(row[colIndex['Updated At']], 'GMT+7', 'dd/MM/yyyy HH:mm')
                          : String(row[colIndex['Updated At']]     || ''),
      fullName:         String(row[colIndex['Full Name']]          || ''),
      nik:              String(row[colIndex['NIK']]                || ''),
      email:            String(row[colIndex['Email']]              || ''),
      position:         String(row[colIndex['Position']]           || ''),
      department:       String(row[colIndex['Department']]         || ''),
      joinDate:         String(row[colIndex['Join Date']]          || ''),
      contractDuration: String(row[colIndex['Contract Duration']]  || ''),
      employmentStatus: String(row[colIndex['Employment Status']]  || ''),
      outsourceVendor:  String(row[colIndex['Outsource Vendor']]   || ''),
      status:           String(row[colIndex['Employment Status']]  || 'Active')
    });
  }

  return result;
}
