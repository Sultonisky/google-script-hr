// ============================================================
// backend/Outsource.gs — OUTSOURCE EMPLOYEE REGISTRATION
// Data masuk ke sheet raw_outsource (bukan raw_kandidat).
// OS sudah aktif bekerja, registrasi ini untuk onboarding HRIS.
// ============================================================

var OUTSOURCE_SHEET_NAME = 'raw_outsource';

var OUTSOURCE_HEADERS = [
  'Outsource ID',
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
  'Position',
  'Department',
  'Education',
  'Work Experience',
  'Join Date',
  'Salary',
  'Vendor Company',
  'Contract Number',
  'Contract Duration',
  'Recruitment Source',
  'Status',
  'HR Notes',
  'Created By',
  'Updated At'
];

// ---- Buat atau ambil sheet raw_outsource ----
function getOrCreateOutsourceSheet_() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(OUTSOURCE_SHEET_NAME);

  if (!sheet) {
    sheet = ss.insertSheet(OUTSOURCE_SHEET_NAME);
  }

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(OUTSOURCE_HEADERS);
    sheet.getRange(1, 1, 1, OUTSOURCE_HEADERS.length)
         .setFontWeight('bold')
         .setBackground('#005BAC')
         .setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, OUTSOURCE_HEADERS.length);
  }

  return sheet;
}

// ---- Generator ID Outsource: OSC-YYYYMMDD-000001 ----
function generateOutsourceId_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, 'GMT+7', 'yyyyMMdd');
  var props    = PropertiesService.getScriptProperties();
  var key      = 'OSC_COUNTER_' + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var counter = Number(props.getProperty(key) || '0') + 1;
    props.setProperty(key, String(counter));
    return 'OSC-' + datePart + '-' + ('000000' + counter).slice(-6);
  } finally {
    lock.releaseLock();
  }
}

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
  if (!f.join_date)
    return 'Tanggal mulai kerja wajib diisi.';
  if (!f.education)
    return 'Pendidikan terakhir wajib dipilih.';
  if (!f.vendor_company || f.vendor_company.trim() === '')
    return 'Nama perusahaan vendor/agency wajib diisi.';
  if (!f.contract_duration)
    return 'Durasi kontrak wajib dipilih.';
  if (f.salary === undefined || f.salary === '' || isNaN(Number(f.salary)))
    return 'Gaji wajib diisi dengan angka.';
  return null;
}

// ---- Simpan data OS ke raw_outsource ----
function simpanDataOutsource(formObject) {
  try {
    var validationError = validateOutsourceForm_(formObject);
    if (validationError) return 'Error: ' + validationError;

    var sheet     = getOrCreateOutsourceSheet_();
    var timestamp = new Date();
    var outsourceId  = generateOutsourceId_(timestamp);
    var createdDate  = Utilities.formatDate(timestamp, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    sheet.appendRow([
      outsourceId,
      createdDate,
      formObject.full_name,
      "'" + formObject.nik,
      formObject.birth_date,
      Number(formObject.age) || '',
      formObject.gender,
      formObject.marital_status,
      formObject.email,
      "'" + formObject.phone,
      formObject.address,
      formObject.city,
      formObject.position,
      formObject.department || '',
      formObject.education,
      formObject.work_experience || '',
      formObject.join_date,
      Number(formObject.salary) || 0,
      formObject.vendor_company,
      formObject.contract_number || '',
      formObject.contract_duration,
      formObject.recruitment_source || '',
      'Pending Review',
      '',        // HR Notes
      'System',
      createdDate
    ]);

    writeAuditLog_(outsourceId, 'Outsource Created', '-', 'Pending Review');
    return 'Sukses';
  } catch (error) {
    return 'Error: ' + error.toString();
  }
}

// ---- Cek duplikat NIK di raw_outsource ----
function getOutsourceList() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(OUTSOURCE_SHEET_NAME);
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
      outsourceId: String(row[colIndex['Outsource ID']] || ''),
      fullName:    String(row[colIndex['Full Name']]    || ''),
      nik:         String(row[colIndex['NIK']]          || ''),
      email:       String(row[colIndex['Email']]        || ''),
      position:    String(row[colIndex['Position']]     || ''),
      vendorCompany: String(row[colIndex['Vendor Company']] || ''),
      status:      String(row[colIndex['Status']]       || '')
  }
  return result;
}
