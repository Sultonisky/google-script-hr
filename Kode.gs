// ============================================================
// CODE.GS — FILE TUNGGAL
// Gabungan: Recruitment Portal + HR Dashboard backend (ATS).
//
// PENTING: Hapus/kosongkan file .gs lain di project ini
// (Kode.gs lama, Dashboard_Backend.gs lama, dsb) supaya TIDAK
// ada dua fungsi doGet() atau nama fungsi yang sama di project
// yang sama — Apps Script akan bingung/menimpa salah satunya.
// Cukup pakai file INI sebagai satu-satunya file .gs.
// ============================================================


// ============================================================
// KONFIGURASI GLOBAL
// ============================================================
var SHEET_NAME           = "raw_kandidat";
var DASHBOARD_SHEET_NAME = "raw_kandidat"; // sama dengan SHEET_NAME
var AUDIT_SHEET_NAME     = "Audit_Log";
var EMPLOYEE_SHEET_NAME  = "Employee";
var STATUS_COLUMN_NAME   = "Status";
var ID_COLUMN_NAME       = "Recruitment ID";
var NOTES_COLUMN_NAME    = "HR Notes";

// Kolom inti (jangan diubah urutannya — data lama bergantung pada ini)
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
  "Updated At"
];

// Kolom tambahan untuk fitur ATS (ditambahkan otomatis di akhir sheet
// tanpa mengganggu kolom/baris lama). Aman untuk sheet yang sudah berisi data.
var EXTRA_HEADERS = [
  "Hold Reason",
  "Hold Follow Up Date",
  "Blacklist Reason",
  "Blacklist Date",
  "Blacklist Updated By",
  "Employee ID"
];

var EMPLOYEE_HEADERS = [
  "Employee ID",
  "Recruitment ID",
  "Full Name",
  "Position",
  "Email",
  "Phone",
  "Join Date",
  "Status",
  "Notes",
  "Created At"
];


// ============================================================
// 1. ROUTER — doGet(e)
//    Ini SATU-SATUNYA doGet di seluruh project.
//    ?page=dashboard       -> HR Dashboard
//    ?type=outsource       -> (siapkan file OutsourceForm.html jika sudah ada)
//    default / ?type=...   -> Recruitment Portal (FormPendaftaran.html)
// ============================================================
function doGet(e) {
  var params = (e && e.parameter) || {};
  var page = params.page || "";
  var type = params.type || "kandidat";

  // --- Halaman Dashboard HR ---
  if (page === "dashboard") {
    return HtmlService.createHtmlOutputFromFile('Dashboard')
        .setTitle("HR Dashboard")
        .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
        .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  }

  // --- Default: Recruitment / Outsource Portal ---
  var template = HtmlService.createTemplateFromFile('FormPendaftaran');
  template.tipePendaftar = type;

  return template.evaluate()
      .setTitle("Form Pendaftaran Karyawan Baru")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}


// ============================================================
// 2. RECRUITMENT PORTAL — simpan data pelamar ke raw_kandidat
// ============================================================
function simpanDataKandidat(formObject) {
  try {
    var validationError = validateFormData_(formObject);
    if (validationError) {
      return "Error: " + validationError;
    }

    var sheet = getOrCreateSheet_();
    var timestamp = new Date();

    var recruitmentId = generateRecruitmentId_(timestamp);
    var createdDate = Utilities.formatDate(timestamp, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var updatedAt = createdDate;
    var status = "Pending";
    var hrNotes = "";
    var createdBy = "System";
    var cvLink = "";

    var lastCompany = formObject.work_experience === "Fresh Graduate"
      ? "-"
      : (formObject.last_company && formObject.last_company.trim() !== "" ? formObject.last_company.trim() : "-");

    sheet.appendRow([
      recruitmentId,
      createdDate,
      formObject.full_name,
      "'" + formObject.nik,
      formObject.birth_date,
      Number(formObject.age) || "",
      formObject.gender,
      formObject.marital_status,
      formObject.email,
      "'" + formObject.phone,
      formObject.address,
      formObject.city,
      formObject.position_applied,
      formObject.education,
      formObject.work_experience || "",
      lastCompany,
      formObject.current_employment_status,
      formObject.available_to_join,
      Number(formObject.expected_salary) || 0,
      formObject.recruitment_source || "",
      cvLink,
      status,
      hrNotes,
      createdBy,
      updatedAt
      // Kolom EXTRA_HEADERS dibiarkan kosong — akan otomatis mendapat header saat sheet dibuat/dibuka
    ]);

    writeAuditLog_(recruitmentId, "Created", "-", "Pending");

    return "Sukses";
  } catch (error) {
    return "Error: " + error.toString();
  }
}


// ============================================================
// 3. VALIDASI SERVER-SIDE
// ============================================================
function validateFormData_(f) {
  if (!f.full_name || f.full_name.trim().length < 3) return "Nama lengkap wajib diisi (minimal 3 karakter).";
  if (!f.nik || !/^[0-9]{16}$/.test(f.nik)) return "NIK harus tepat 16 digit angka.";
  if (!f.birth_date) return "Tanggal lahir wajib diisi.";
  if (!f.age || Number(f.age) < 17) return "Usia minimal 17 tahun untuk mendaftar.";
  if (!f.gender) return "Jenis kelamin wajib dipilih.";
  if (!f.marital_status) return "Status pernikahan wajib dipilih.";
  if (!f.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email)) return "Format alamat email tidak valid.";
  if (!f.phone || !/^62[0-9]{8,12}$/.test(f.phone)) return "Nomor HP tidak valid.";
  if (!f.address || f.address.trim() === "") return "Alamat wajib diisi.";
  if (!f.city || f.city.trim() === "") return "Kota/Kabupaten wajib diisi.";
  if (!f.position_applied) return "Posisi yang dilamar wajib dipilih.";
  if (!f.education) return "Pendidikan terakhir wajib dipilih.";
  if (!f.work_experience) return "Pengalaman kerja wajib dipilih.";
  if (!f.current_employment_status) return "Status bekerja saat ini wajib dipilih.";
  if (!f.available_to_join) return "Kesediaan bergabung wajib dipilih.";
  if (f.expected_salary === undefined || f.expected_salary === "" || isNaN(Number(f.expected_salary))) return "Ekspektasi gaji wajib diisi dengan angka.";
  if (!f.recruitment_source) return "Sumber informasi lowongan wajib dipilih.";
  return null;
}


// ============================================================
// 4. GENERATOR RECRUITMENT ID (REC-YYYYMMDD-000001, reset harian)
// ============================================================
function generateRecruitmentId_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "REC_COUNTER_" + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var counter = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(counter));
    var counterStr = ("000000" + counter).slice(-6);
    return "REC-" + datePart + "-" + counterStr;
  } finally {
    lock.releaseLock();
  }
}

function generateEmployeeId_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyy");
  var props = PropertiesService.getScriptProperties();
  var key = "EMP_COUNTER_" + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var counter = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(counter));
    var counterStr = ("0000" + counter).slice(-4);
    return "EMP-" + datePart + "-" + counterStr;
  } finally {
    lock.releaseLock();
  }
}


// ============================================================
// 5. AMBIL / BUAT SHEET raw_kandidat (dengan header) + kolom tambahan
// ============================================================
function getOrCreateSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(SHEET_NAME);

  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAME);
  }

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(SHEET_HEADERS.concat(EXTRA_HEADERS));
    sheet.getRange(1, 1, 1, SHEET_HEADERS.length + EXTRA_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#005BAC")
      .setFontColor("#FFFFFF");
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, SHEET_HEADERS.length + EXTRA_HEADERS.length);
  } else {
    ensureExtraHeaders_(sheet);
  }

  return sheet;
}

// Menambahkan header EXTRA_HEADERS di akhir sheet jika belum ada.
// Aman dijalankan berkali-kali, tidak menyentuh kolom/baris yang sudah ada.
function ensureExtraHeaders_(sheet) {
  var lastCol = sheet.getLastColumn();
  var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0].map(function(h){ return String(h).trim(); });

  var missing = EXTRA_HEADERS.filter(function(h){ return headerRow.indexOf(h) === -1; });
  if (missing.length === 0) return;

  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet.getRange(1, startCol, 1, missing.length)
    .setFontWeight("bold")
    .setBackground("#005BAC")
    .setFontColor("#FFFFFF");
}

function getDashboardSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(DASHBOARD_SHEET_NAME);
  if (sheet) ensureExtraHeaders_(sheet);
  return sheet;
}

function getOrCreateEmployeeSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);

  if (!sheet) {
    sheet = ss.insertSheet(EMPLOYEE_SHEET_NAME);
    sheet.appendRow(EMPLOYEE_HEADERS);
    sheet.getRange(1, 1, 1, EMPLOYEE_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#005BAC")
      .setFontColor("#FFFFFF");
    sheet.setFrozenRows(1);
  }

  return sheet;
}

// Jalankan manual sekali dari editor untuk memastikan sheet & header siap
function setupSpreadsheet() {
  getOrCreateSheet_();
  getOrCreateEmployeeSheet_();
}


// ============================================================
// 6. DASHBOARD — READ seluruh data kandidat
// ============================================================
function getRecruitmentList() {
  var sheet = getDashboardSheet_();
  if (!sheet || sheet.getLastRow() < 2) return [];

  var lastRow = sheet.getLastRow();
  var lastCol = sheet.getLastColumn();
  var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var headers = values[0];

  var colIndex = {};
  headers.forEach(function(header, i) { colIndex[String(header).trim()] = i; });

  function cell(row, headerName) {
    var idx = colIndex[headerName];
    return idx === undefined ? "" : row[idx];
  }

  function fmtDate(val, pattern) {
    return val instanceof Date ? Utilities.formatDate(val, "GMT+7", pattern) : String(val || "");
  }

  var result = [];

  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (!row.join("").toString().trim()) continue;

    result.push({
      recruitmentId:           String(cell(row, "Recruitment ID") || ""),
      createdDate:             fmtDate(cell(row, "Created Date"), "dd/MM/yyyy HH:mm"),
      fullName:                String(cell(row, "Full Name") || ""),
      nik:                     String(cell(row, "NIK") || ""),
      birthDate:               fmtDate(cell(row, "Birth Date"), "dd/MM/yyyy"),
      age:                     cell(row, "Age") === "" ? "" : Number(cell(row, "Age")),
      gender:                  String(cell(row, "Gender") || ""),
      maritalStatus:           String(cell(row, "Marital Status") || ""),
      email:                   String(cell(row, "Email") || ""),
      phone:                   String(cell(row, "Phone") || ""),
      address:                 String(cell(row, "Address") || ""),
      city:                    String(cell(row, "City") || ""),
      positionApplied:         String(cell(row, "Position Applied") || ""),
      education:               String(cell(row, "Education") || ""),
      workExperience:          String(cell(row, "Work Experience") || ""),
      lastCompany:             String(cell(row, "Last Company") || ""),
      currentEmploymentStatus: String(cell(row, "Current Employment Status") || ""),
      availableToJoin:         String(cell(row, "Available to Join") || ""),
      expectedSalary:          cell(row, "Expected Salary") === "" ? 0 : Number(cell(row, "Expected Salary")),
      recruitmentSource:       String(cell(row, "Recruitment Source") || ""),
      cvLink:                  String(cell(row, "CV Link") || ""),
      status:                  String(cell(row, "Status") || "Pending"),
      hrNotes:                 String(cell(row, "HR Notes") || ""),
      createdBy:               String(cell(row, "Created By") || ""),
      updatedAt:               fmtDate(cell(row, "Updated At"), "dd/MM/yyyy HH:mm"),
      holdReason:              String(cell(row, "Hold Reason") || ""),
      holdFollowUpDate:        fmtDate(cell(row, "Hold Follow Up Date"), "dd/MM/yyyy"),
      blacklistReason:         String(cell(row, "Blacklist Reason") || ""),
      blacklistDate:           fmtDate(cell(row, "Blacklist Date"), "dd/MM/yyyy HH:mm"),
      blacklistUpdatedBy:      String(cell(row, "Blacklist Updated By") || ""),
      employeeId:              String(cell(row, "Employee ID") || "")
    });
  }

  // Sort by date (Created Date) descending to ensure latest data is first
  result.sort(function(a, b) {
    // Parse "dd/MM/yyyy HH:mm" for comparison
    function toTimestamp(str) {
      if (!str) return 0;
      var p = str.split(' ');
      var d = p[0].split('/');
      var t = p[1] ? p[1].split(':') : ['00', '00'];
      return new Date(d[2], d[1]-1, d[0], t[0], t[1]).getTime();
    }
    return toTimestamp(b.createdDate) - toTimestamp(a.createdDate);
  });

  return result;
}


// ============================================================
// 7. DASHBOARD — WRITE status + HR Notes (generik, dipakai Pending/umum)
// ============================================================
function updateCandidateStatus(recruitmentId, newStatus, hrNotes) {
  var allowedStatus = [
    "Pending", "Accepted", "Hold", "Blacklist",
    "Applied", "Screening", "HR Interview", "User Interview",
    "Offering", "Hired", "Rejected"
  ];
  if (allowedStatus.indexOf(newStatus) === -1) {
    return { success: false, message: "Status tidak valid: " + newStatus };
  }

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      return { success: false, message: "Sheet data kandidat tidak ditemukan." };
    }

    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) {
      return { success: false, message: "Recruitment ID tidak ditemukan: " + recruitmentId };
    }

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];

    setCell_(sheet, found, STATUS_COLUMN_NAME, newStatus);
    if (hrNotes !== undefined && hrNotes !== null) {
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    }
    touchUpdatedAt_(sheet, found);

    writeAuditLog_(recruitmentId, "Update Status", oldStatus, newStatus);

    return { success: true, recruitmentId: recruitmentId, newStatus: newStatus };

  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}


// ============================================================
// 7b. HELPERS — cari baris kandidat, set sel by header name
// ============================================================
function findCandidateRow_(sheet, recruitmentId) {
  var lastRow = sheet.getLastRow();
  var lastCol = sheet.getLastColumn();
  var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var headers = values[0];

  var colIndex = {};
  headers.forEach(function(h, i) { colIndex[String(h).trim()] = i; });

  var idCol = colIndex[ID_COLUMN_NAME];
  if (idCol === undefined) return null;

  for (var r = 1; r < values.length; r++) {
    if (String(values[r][idCol]) === String(recruitmentId)) {
      return { rowNumber: r + 1, values: values[r], colIndex: colIndex };
    }
  }
  return null;
}

function setCell_(sheet, found, headerName, value) {
  var idx = found.colIndex[headerName];
  if (idx === undefined) return;
  sheet.getRange(found.rowNumber, idx + 1).setValue(value);
}

function touchUpdatedAt_(sheet, found) {
  var idx = found.colIndex["Updated At"];
  if (idx === undefined) return;
  sheet.getRange(found.rowNumber, idx + 1)
    .setValue(Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss"));
}


// ============================================================
// 8. HOLD — simpan alasan + tanggal follow up
// ============================================================
function holdCandidate(recruitmentId, reason, followUpDate, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) return { success: false, message: "Recruitment ID tidak ditemukan: " + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];

    setCell_(sheet, found, STATUS_COLUMN_NAME, "Hold");
    setCell_(sheet, found, "Hold Reason", reason || "");
    setCell_(sheet, found, "Hold Follow Up Date", followUpDate || "");
    if (hrNotes !== undefined && hrNotes !== null) setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    writeAuditLog_(recruitmentId, "Hold", oldStatus, "Hold (" + (reason || "-") + ")");

    return { success: true, recruitmentId: recruitmentId, newStatus: "Hold" };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}


// ============================================================
// 9. BLACKLIST — simpan alasan + tanggal + updated by
// ============================================================
function blacklistCandidate(recruitmentId, reason, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) return { success: false, message: "Recruitment ID tidak ditemukan: " + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var user = Session.getActiveUser().getEmail() || "HR Dashboard";
    var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");

    setCell_(sheet, found, STATUS_COLUMN_NAME, "Blacklist");
    setCell_(sheet, found, "Blacklist Reason", reason || "");
    setCell_(sheet, found, "Blacklist Date", now);
    setCell_(sheet, found, "Blacklist Updated By", user);
    if (hrNotes !== undefined && hrNotes !== null) setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    writeAuditLog_(recruitmentId, "Blacklist", oldStatus, "Blacklist (" + (reason || "-") + ")");

    return { success: true, recruitmentId: recruitmentId, newStatus: "Blacklist" };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}


// ============================================================
// 10. ACCEPT -> MASTER EMPLOYEE — pindahkan kandidat ke sheet Employee,
//     riwayat tetap disimpan di raw_kandidat, status = Accepted.
// ============================================================
function acceptCandidateToEmployee(recruitmentId, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) return { success: false, message: "Recruitment ID tidak ditemukan: " + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var existingEmpId = found.values[found.colIndex["Employee ID"]];

    var now = new Date();
    var employeeId = existingEmpId ? String(existingEmpId) : generateEmployeeId_(now);

    setCell_(sheet, found, STATUS_COLUMN_NAME, "Accepted");
    setCell_(sheet, found, "Employee ID", employeeId);
    if (hrNotes !== undefined && hrNotes !== null) setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    // Hanya tambahkan ke sheet Employee jika belum ada (hindari duplikat saat re-accept)
    if (!existingEmpId) {
      var empSheet = getOrCreateEmployeeSheet_();
      empSheet.appendRow([
        employeeId,
        recruitmentId,
        found.values[found.colIndex["Full Name"]],
        found.values[found.colIndex["Position Applied"]],
        found.values[found.colIndex["Email"]],
        found.values[found.colIndex["Phone"]],
        Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd"),
        "Active",
        hrNotes || "",
        Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss")
      ]);
    }

    writeAuditLog_(recruitmentId, "Accepted", oldStatus, "Accepted -> Employee " + employeeId);

    return { success: true, recruitmentId: recruitmentId, newStatus: "Accepted", employeeId: employeeId };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}


// ============================================================
// 11. HR NOTES — Auto Save (tanpa mengubah status)
// ============================================================
function saveHrNotes(recruitmentId, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) return { success: false, message: "Recruitment ID tidak ditemukan: " + recruitmentId };

    setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes || "");
    touchUpdatedAt_(sheet, found);

    return {
      success: true,
      recruitmentId: recruitmentId,
      updatedAt: Utilities.formatDate(new Date(), "GMT+7", "dd/MM/yyyy HH:mm")
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}


// ============================================================
// 11b. DELETE CANDIDATE — Hapus permanen dari raw_kandidat
// ============================================================
function deleteCandidate(recruitmentId) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      return { success: false, message: "Sheet data kandidat tidak ditemukan." };
    }

    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) {
      return { success: false, message: "Recruitment ID tidak ditemukan: " + recruitmentId };
    }

    // Delete the row
    sheet.deleteRow(found.rowNumber);

    // Audit log (tetap catat penghapusan di log)
    writeAuditLog_(recruitmentId, "Deleted", "-", "Deleted");

    return { success: true, recruitmentId: recruitmentId };

  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}


// ============================================================
// 12. BULK ACTIONS — Update status / Hapus multiple kandidat
// ============================================================
function bulkUpdateStatus(recruitmentIds, newStatus) {
  var allowedStatus = [
    "Pending", "Accepted", "Hold", "Blacklist",
    "Applied", "Screening", "HR Interview", "User Interview",
    "Offering", "Hired", "Rejected"
  ];
  if (allowedStatus.indexOf(newStatus) === -1) {
    return { success: false, message: "Status tidak valid: " + newStatus, updated: 0, failed: 0 };
  }
  if (!recruitmentIds || !Array.isArray(recruitmentIds) || recruitmentIds.length === 0) {
    return { success: false, message: "Tidak ada kandidat yang dipilih.", updated: 0, failed: 0 };
  }

  var lock = LockService.getScriptLock();
  lock.waitLock(30000);

  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      return { success: false, message: "Sheet data kandidat tidak ditemukan.", updated: 0, failed: 0 };
    }

    var updated = 0;
    var failed = 0;
    var failedIds = [];

    for (var i = 0; i < recruitmentIds.length; i++) {
      try {
        var found = findCandidateRow_(sheet, recruitmentIds[i]);
        if (!found) { failed++; failedIds.push(recruitmentIds[i]); continue; }

        var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
        setCell_(sheet, found, STATUS_COLUMN_NAME, newStatus);
        touchUpdatedAt_(sheet, found);
        writeAuditLog_(recruitmentIds[i], "Bulk Update Status", oldStatus, newStatus);
        updated++;
      } catch (e) {
        failed++;
        failedIds.push(recruitmentIds[i]);
      }
    }

    return {
      success: updated > 0,
      message: updated + " kandidat berhasil diupdate. " + failed + " gagal.",
      updated: updated,
      failed: failed,
      failedIds: failedIds
    };
  } catch (err) {
    return { success: false, message: err.message, updated: 0, failed: recruitmentIds.length };
  } finally {
    lock.releaseLock();
  }
}

function bulkDeleteCandidates(recruitmentIds) {
  if (!recruitmentIds || !Array.isArray(recruitmentIds) || recruitmentIds.length === 0) {
    return { success: false, message: "Tidak ada kandidat yang dipilih.", deleted: 0, failed: 0 };
  }

  var lock = LockService.getScriptLock();
  lock.waitLock(30000);

  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      return { success: false, message: "Sheet data kandidat tidak ditemukan.", deleted: 0, failed: 0 };
    }

    var deleted = 0;
    var failed = 0;
    var failedIds = [];

    // Collect all row numbers first, then delete from bottom to top
    var rowsToDelete = [];
    for (var i = 0; i < recruitmentIds.length; i++) {
      var found = findCandidateRow_(sheet, recruitmentIds[i]);
      if (found) {
        rowsToDelete.push({ rowNumber: found.rowNumber, id: recruitmentIds[i] });
      } else {
        failed++;
        failedIds.push(recruitmentIds[i]);
      }
    }

    // Sort by row number descending so we delete from bottom to top
    rowsToDelete.sort(function(a, b) { return b.rowNumber - a.rowNumber; });

    for (var j = 0; j < rowsToDelete.length; j++) {
      try {
        sheet.deleteRow(rowsToDelete[j].rowNumber);
        writeAuditLog_(rowsToDelete[j].id, "Bulk Deleted", "-", "Deleted");
        deleted++;
      } catch (e) {
        failed++;
        failedIds.push(rowsToDelete[j].id);
      }
    }

    return {
      success: deleted > 0,
      message: deleted + " kandidat berhasil dihapus. " + failed + " gagal.",
      deleted: deleted,
      failed: failed,
      failedIds: failedIds
    };
  } catch (err) {
    return { success: false, message: err.message, deleted: 0, failed: recruitmentIds.length };
  } finally {
    lock.releaseLock();
  }
}


// ============================================================
// 13. AUDIT LOG helper — buat sheet Audit_Log otomatis jika belum ada
// ============================================================
function writeAuditLog_(recruitmentId, action, oldValue, newValue) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_SHEET_NAME);

  if (!sheet) {
    sheet = ss.insertSheet(AUDIT_SHEET_NAME);
    sheet.appendRow(["Timestamp", "User", "Recruitment ID", "Action", "Old Value", "New Value"]);
    sheet.getRange(1, 1, 1, 6).setFontWeight("bold").setBackground("#005BAC").setFontColor("#FFFFFF");
    sheet.setFrozenRows(1);
  }

  var user = Session.getActiveUser().getEmail() || "HR Dashboard";

  sheet.appendRow([
    Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss"),
    user,
    recruitmentId,
    action,
    oldValue,
    newValue
  ]);
}

// Ambil riwayat aktivitas untuk satu kandidat (dipakai Activity Timeline)
function getAuditLogForCandidate(recruitmentId) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(AUDIT_SHEET_NAME);
  if (!sheet || sheet.getLastRow() < 2) return [];

  var values = sheet.getRange(1, 1, sheet.getLastRow(), 6).getValues();
  var result = [];

  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (String(row[2]) !== String(recruitmentId)) continue;

    result.push({
      timestamp: row[0] instanceof Date ? Utilities.formatDate(row[0], "GMT+7", "dd/MM/yyyy HH:mm") : String(row[0] || ""),
      user: String(row[1] || ""),
      action: String(row[3] || ""),
      oldValue: String(row[4] || ""),
      newValue: String(row[5] || "")
    });
  }

  return result;
}
