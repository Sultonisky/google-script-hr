// ============================================================
// backend/Recruitment.gs — CRUD KANDIDAT
// ============================================================

// ---- Simpan pelamar baru ----
function simpanDataKandidat(formObject) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var validationError = validateFormData_(formObject);
    if (validationError) return "Error: " + validationError;

    var nikCheck = isNikExists_(formObject.nik);
    if (nikCheck.found) {
      return "Error: NIK ini sudah terdaftar dengan status " + nikCheck.status + ". Tidak dapat mendaftar lagi.";
    }

    var sheet = getOrCreateSheet_();
    var timestamp = new Date();
    var recruitmentId = generateRecruitmentId_(timestamp);
    var createdDate = Utilities.formatDate(
      timestamp,
      "GMT+7",
      "yyyy-MM-dd HH:mm:ss",
    );

    var lastCompany =
      formObject.work_experience === "Fresh Graduate"
        ? "-"
        : formObject.last_company && formObject.last_company.trim() !== ""
          ? formObject.last_company.trim()
          : "-";

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
      "", // CV Link
      "Pending",
      "", // HR Notes
      "System",
      createdDate,
    ]);

    writeAuditLog_(recruitmentId, "Created", "Status", "-", "Pending");
    return "Sukses";
  } catch (error) {
    return "Error: " + error.toString();
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// CEK DUPLIKASI NIK DI SEMUA SHEET
// Mencari NIK di: data_kandidat, kandidat_hold, kandidat_accepted,
// kandidat_blacklist, dan Employee. Return object {found, sheet, status}
// ============================================================
function isNikExists_(nik) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheetsToCheck = [
    { name: SHEET_NAME, label: 'data_kandidat', status: 'Pending' },
    { name: HOLD_SHEET_NAME, label: 'kandidat_hold', status: 'Hold' },
    { name: ACCEPTED_SHEET_NAME, label: 'kandidat_accepted', status: 'Accepted' },
    { name: BLACKLIST_SHEET_NAME, label: 'kandidat_blacklist', status: 'Blacklist' },
    { name: EMPLOYEE_SHEET_NAME, label: 'Employee', status: 'Employee' }
  ];

  var searchNik = "'" + String(nik).trim();
  var bareNik = String(nik).trim();

  for (var s = 0; s < sheetsToCheck.length; s++) {
    var sheetInfo = sheetsToCheck[s];
    var sheet = ss.getSheetByName(sheetInfo.name);
    if (!sheet || sheet.getLastRow() < 2) continue;

    var lastRow = sheet.getLastRow();
    var lastCol = sheet.getLastColumn();
    var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
    var headers = values[0];

    var nikCol = -1;
    for (var c = 0; c < headers.length; c++) {
      if (String(headers[c]).trim() === 'NIK') {
        nikCol = c;
        break;
      }
    }
    if (nikCol === -1) continue;

    for (var r = 1; r < values.length; r++) {
      var rowNik = String(values[r][nikCol] || '').trim();
      if (rowNik === searchNik || rowNik === bareNik) {
        return { found: true, sheet: sheetInfo.label, status: sheetInfo.status };
      }
    }
  }

  return { found: false, sheet: '', status: '' };
}

// ---- Ambil seluruh kandidat (dashboard) ----
function getRecruitmentList() {
  var sheet = getDashboardSheet_();
  if (!sheet || sheet.getLastRow() < 2) return [];

  var lastRow = sheet.getLastRow();
  var lastCol = sheet.getLastColumn();
  var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var headers = values[0];

  var colIndex = {};
  headers.forEach(function (header, i) {
    colIndex[String(header).trim()] = i;
  });

  function cell(row, name) {
    var idx = colIndex[name];
    return idx === undefined ? "" : row[idx];
  }

  var result = [];
  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (!row.join("").toString().trim()) continue;
    result.push({
      recruitmentId: String(cell(row, "Recruitment ID") || ""),
      createdDate: fmtDate_(cell(row, "Created Date"), "dd/MM/yyyy HH:mm"),
      fullName: String(cell(row, "Full Name") || ""),
      nik: String(cell(row, "NIK") || ""),
      birthDate: fmtDate_(cell(row, "Birth Date"), "dd/MM/yyyy"),
      age: cell(row, "Age") === "" ? "" : Number(cell(row, "Age")),
      gender: String(cell(row, "Gender") || ""),
      maritalStatus: String(cell(row, "Marital Status") || ""),
      email: String(cell(row, "Email") || ""),
      phone: String(cell(row, "Phone") || ""),
      address: String(cell(row, "Address") || ""),
      city: String(cell(row, "City") || ""),
      positionApplied: String(cell(row, "Position Applied") || ""),
      education: String(cell(row, "Education") || ""),
      workExperience: String(cell(row, "Work Experience") || ""),
      lastCompany: String(cell(row, "Last Company") || ""),
      currentEmploymentStatus: String(
        cell(row, "Current Employment Status") || "",
      ),
      availableToJoin: String(cell(row, "Available to Join") || ""),
      expectedSalary:
        cell(row, "Expected Salary") === ""
          ? 0
          : Number(cell(row, "Expected Salary")),
      recruitmentSource: String(cell(row, "Recruitment Source") || ""),
      cvLink: String(cell(row, "CV Link") || ""),
      status: String(cell(row, "Status") || "Pending"),
      hrNotes: String(cell(row, "HR Notes") || ""),
      createdBy: String(cell(row, "Created By") || ""),
      updatedAt: fmtDate_(cell(row, "Updated At"), "dd/MM/yyyy HH:mm"),
      holdReason: String(cell(row, "Hold Reason") || ""),
      holdFollowUpDate: fmtDate_(
        cell(row, "Hold Follow Up Date"),
        "dd/MM/yyyy",
      ),
      blacklistReason: String(cell(row, "Blacklist Reason") || ""),
      blacklistDate: fmtDate_(cell(row, "Blacklist Date"), "dd/MM/yyyy HH:mm"),
      blacklistUpdatedBy: String(cell(row, "Blacklist Updated By") || ""),
      employeeId: String(cell(row, "Employee ID") || ""),
    });
  }

  result.sort(function (a, b) {
    function toTs(s) {
      if (!s) return 0;
      var p = s.split(" "),
        d = p[0].split("/"),
        t = p[1] ? p[1].split(":") : ["00", "00"];
      return new Date(d[2], d[1] - 1, d[0], t[0], t[1]).getTime();
    }
    return toTs(b.createdDate) - toTs(a.createdDate);
  });

  return result;
}

// ---- Update status (generik) ----
function updateCandidateStatus(recruitmentId, newStatus, hrNotes) {
  var allowed = ["Pending", "Accepted", "Hold", "Blacklist"];
  if (allowed.indexOf(newStatus) === -1)
    return { success: false, message: "Status tidak valid: " + newStatus };

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2)
      return {
        success: false,
        message: "Sheet data kandidat tidak ditemukan.",
      };

    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return {
        success: false,
        message: "Recruitment ID tidak ditemukan: " + recruitmentId,
      };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    setCell_(sheet, found, STATUS_COLUMN_NAME, newStatus);
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);
    writeAuditLog_(
      recruitmentId,
      "Update Status",
      "Status",
      oldStatus,
      newStatus,
    );

    return {
      success: true,
      recruitmentId: recruitmentId,
      newStatus: newStatus,
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Hold kandidat ----
function holdCandidate(recruitmentId, reason, followUpDate, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var user = Session.getActiveUser().getEmail() || 'HR Dashboard';
    var now  = new Date();
    var nowStr = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    // Update nilai pada found.values sebelum salin ke sheet hold
    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Hold');
    setCell_(sheet, found, 'Hold Reason', reason || '');
    setCell_(sheet, found, 'Hold Follow Up Date', followUpDate || '');
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    // Salin ke sheet kandidat_hold
    var holdSheet = getOrCreateHoldSheet_();
    var newRow = buildStatusRow_(found, HOLD_HEADERS, nowStr, user);
    holdSheet.appendRow(newRow);

    // Hapus dari data_kandidat
    sheet.deleteRow(found.rowNumber);

    writeAuditLog_(recruitmentId, 'Hold', 'Status', oldStatus, 'Hold (' + (reason || '-') + ')');

    return { success: true, recruitmentId: recruitmentId, newStatus: 'Hold' };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Blacklist kandidat ----
function blacklistCandidate(recruitmentId, reason, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var user = Session.getActiveUser().getEmail() || 'HR Dashboard';
    var now  = new Date();
    var nowStr = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    // Update nilai pada found.values sebelum salin ke sheet blacklist
    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Blacklist');
    setCell_(sheet, found, 'Blacklist Reason', reason || '');
    setCell_(sheet, found, 'Blacklist Date', nowStr);
    setCell_(sheet, found, 'Blacklist Updated By', user);
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    // Salin ke sheet kandidat_blacklist
    var blSheet = getOrCreateBlacklistSheet_();
    var newRow  = buildStatusRow_(found, BLACKLIST_HEADERS, nowStr, user);
    blSheet.appendRow(newRow);

    // Hapus dari data_kandidat
    sheet.deleteRow(found.rowNumber);

    writeAuditLog_(recruitmentId, 'Blacklist', 'Status', oldStatus, 'Blacklist (' + (reason || '-') + ')');

    return { success: true, recruitmentId: recruitmentId, newStatus: 'Blacklist' };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Accept kandidat → Employee + kandidat_accepted ----
function acceptCandidateToEmployee(recruitmentId, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var existingEmpId = found.values[found.colIndex['Employee ID']];
    var now = new Date();
    var employeeId = existingEmpId ? String(existingEmpId) : generateEmployeeId_(now);
    var nowStr = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
    var user   = Session.getActiveUser().getEmail() || 'HR Dashboard';

    // Update found.values sebelum salin ke sheet accepted
    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Accepted');
    setCell_(sheet, found, 'Employee ID', employeeId);
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    // Salin ke sheet kandidat_accepted
    var accSheet = getOrCreateAcceptedSheet_();
    var accRow   = buildStatusRow_(found, ACCEPTED_HEADERS, nowStr, user);
    accSheet.appendRow(accRow);

    // Hapus dari data_kandidat
    sheet.deleteRow(found.rowNumber);

    // Buat record di sheet Employee (jika belum ada)
    if (!existingEmpId) {
      var empSheet = getOrCreateEmployeeSheet_();
      var createdAt = nowStr;

      var newRow = new Array(EMPLOYEE_HEADERS.length).fill('');
      newRow[EMPLOYEE_COL['Employee ID'] - 1]       = employeeId;
      newRow[EMPLOYEE_COL['Recruitment ID'] - 1]    = recruitmentId;
      newRow[EMPLOYEE_COL['Full Name'] - 1]         = found.values[found.colIndex['Full Name']];
      newRow[EMPLOYEE_COL['Position'] - 1]          = found.values[found.colIndex['Position Applied']];
      newRow[EMPLOYEE_COL['Email'] - 1]             = found.values[found.colIndex['Email']];
      newRow[EMPLOYEE_COL['Phone'] - 1]             = found.values[found.colIndex['Phone']];
      newRow[EMPLOYEE_COL['Join Date'] - 1]         = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd');
      newRow[EMPLOYEE_COL['Status'] - 1]            = 'Active';
      newRow[EMPLOYEE_COL['Notes'] - 1]             = hrNotes || '';
      newRow[EMPLOYEE_COL['Created At'] - 1]        = createdAt;
      newRow[EMPLOYEE_COL['Company Entity'] - 1]    = 'MITO Group';
      newRow[EMPLOYEE_COL['Employee Type'] - 1]     = 'PKWTT';
      newRow[EMPLOYEE_COL['NIK'] - 1]               = found.values[found.colIndex['NIK']];
      newRow[EMPLOYEE_COL['Birth Date'] - 1]        = found.values[found.colIndex['Birth Date']];
      newRow[EMPLOYEE_COL['Age'] - 1]               = found.values[found.colIndex['Age']];
      newRow[EMPLOYEE_COL['Gender'] - 1]            = found.values[found.colIndex['Gender']];
      newRow[EMPLOYEE_COL['Marital Status'] - 1]    = found.values[found.colIndex['Marital Status']];
      newRow[EMPLOYEE_COL['Address'] - 1]           = found.values[found.colIndex['Address']];
      newRow[EMPLOYEE_COL['City'] - 1]              = found.values[found.colIndex['City']];
      newRow[EMPLOYEE_COL['Education'] - 1]         = found.values[found.colIndex['Education']];
      newRow[EMPLOYEE_COL['Work Experience'] - 1]   = found.values[found.colIndex['Work Experience']];
      newRow[EMPLOYEE_COL['Department'] - 1]        = '';
      newRow[EMPLOYEE_COL['Division'] - 1]          = '';
      newRow[EMPLOYEE_COL['Branch'] - 1]            = '';
      newRow[EMPLOYEE_COL['Contract Start'] - 1]    = '';
      newRow[EMPLOYEE_COL['Contract End'] - 1]      = '';
      newRow[EMPLOYEE_COL['Contract Duration'] - 1] = '';
      newRow[EMPLOYEE_COL['Employment Status'] - 1] = 'Active';
      newRow[EMPLOYEE_COL['Salary'] - 1]            = found.values[found.colIndex['Expected Salary']];
      newRow[EMPLOYEE_COL['Salary Type'] - 1]       = 'Monthly';
      newRow[EMPLOYEE_COL['Outsource Vendor'] - 1]  = '';
      newRow[EMPLOYEE_COL['Contract Number'] - 1]   = '';
      newRow[EMPLOYEE_COL['District'] - 1]          = '';
      newRow[EMPLOYEE_COL['Recruitment Source'] - 1] = found.values[found.colIndex['Recruitment Source']];
      newRow[EMPLOYEE_COL['HR Notes'] - 1]          = hrNotes || '';
      newRow[EMPLOYEE_COL['Created By'] - 1]        = 'System';
      newRow[EMPLOYEE_COL['Updated At'] - 1]        = createdAt;

      empSheet.appendRow(newRow);
    }

    writeAuditLog_(recruitmentId, 'Accepted', 'Status', oldStatus, 'Accepted -> Employee ' + employeeId);
    return { success: true, recruitmentId: recruitmentId, newStatus: 'Accepted', employeeId: employeeId };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Ambil satu kandidat by Recruitment ID (cari di semua sheet status) ----
function getCandidateById(recruitmentId) {
  try {
    if (!recruitmentId) return null;

    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheetsToCheck = [
      { name: SHEET_NAME },
      { name: HOLD_SHEET_NAME },
      { name: ACCEPTED_SHEET_NAME },
      { name: BLACKLIST_SHEET_NAME }
    ];

    for (var s = 0; s < sheetsToCheck.length; s++) {
      var sheet = ss.getSheetByName(sheetsToCheck[s].name);
      if (!sheet || sheet.getLastRow() < 2) continue;

      var lastRow = sheet.getLastRow();
      var lastCol = sheet.getLastColumn();
      var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
      var headers = values[0];

      var colIndex = {};
      headers.forEach(function(h, i) { colIndex[String(h).trim()] = i; });

      var idCol = colIndex[ID_COLUMN_NAME];
      if (idCol === undefined) continue;

      for (var r = 1; r < values.length; r++) {
        if (String(values[r][idCol]) === String(recruitmentId)) {
          var row = values[r];
          function cell(name) {
            var idx = colIndex[name];
            return idx === undefined ? '' : row[idx];
          }
          return {
            recruitmentId:           String(cell('Recruitment ID') || ''),
            createdDate:             fmtDate_(cell('Created Date'), 'dd/MM/yyyy HH:mm'),
            fullName:                String(cell('Full Name') || ''),
            nik:                     String(cell('NIK') || ''),
            birthDate:               fmtDate_(cell('Birth Date'), 'dd/MM/yyyy'),
            age:                     cell('Age') === '' ? '' : Number(cell('Age')),
            gender:                  String(cell('Gender') || ''),
            maritalStatus:           String(cell('Marital Status') || ''),
            email:                   String(cell('Email') || ''),
            phone:                   String(cell('Phone') || ''),
            address:                 String(cell('Address') || ''),
            city:                    String(cell('City') || ''),
            positionApplied:         String(cell('Position Applied') || ''),
            education:               String(cell('Education') || ''),
            workExperience:          String(cell('Work Experience') || ''),
            lastCompany:             String(cell('Last Company') || ''),
            currentEmploymentStatus: String(cell('Current Employment Status') || ''),
            availableToJoin:         String(cell('Available to Join') || ''),
            expectedSalary:          cell('Expected Salary') === '' ? 0 : Number(cell('Expected Salary')),
            recruitmentSource:       String(cell('Recruitment Source') || ''),
            cvLink:                  String(cell('CV Link') || ''),
            status:                  String(cell('Status') || 'Pending'),
            hrNotes:                 String(cell('HR Notes') || ''),
            createdBy:               String(cell('Created By') || ''),
            updatedAt:               fmtDate_(cell('Updated At'), 'dd/MM/yyyy HH:mm'),
            holdReason:              String(cell('Hold Reason') || ''),
            holdFollowUpDate:        fmtDate_(cell('Hold Follow Up Date'), 'dd/MM/yyyy'),
            blacklistReason:         String(cell('Blacklist Reason') || ''),
            blacklistDate:           fmtDate_(cell('Blacklist Date'), 'dd/MM/yyyy HH:mm'),
            blacklistUpdatedBy:      String(cell('Blacklist Updated By') || ''),
            employeeId:              String(cell('Employee ID') || ''),
          };
        }
      }
    }
    return null;
  } catch (err) {
    return null;
  }
}

// ---- Simpan HR Notes (tanpa ubah status) ----
// ============================================================
// HELPER: buildStatusRow_
// Buat row baru untuk sheet status (hold/accepted/blacklist)
// berdasarkan data kandidat yang sudah ter-update di found.values
// ============================================================
function buildStatusRow_(found, targetHeaders, nowStr, user) {
  var srcIdx = found.colIndex;
  var src    = found.values;

  var tgtIdx = {};
  targetHeaders.forEach(function(h, i) { tgtIdx[h] = i; });

  var row = new Array(targetHeaders.length).fill('');

  Object.keys(srcIdx).forEach(function(colName) {
    if (tgtIdx[colName] !== undefined) {
      row[tgtIdx[colName]] = src[srcIdx[colName]] !== undefined ? src[srcIdx[colName]] : '';
    }
  });

  if (tgtIdx['Processed Date'] !== undefined) row[tgtIdx['Processed Date']] = nowStr;
  if (tgtIdx['Processed By']   !== undefined) row[tgtIdx['Processed By']]   = user;

  return row;
}

// ============================================================
// AMBIL DATA DARI SHEET STATUS
// ============================================================
function getStatusSheetList_(sheetName) {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(sheetName);
  if (!sheet || sheet.getLastRow() < 2) return [];

  var lastRow = sheet.getLastRow();
  var lastCol = sheet.getLastColumn();
  var values  = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var headers = values[0];

  var colIndex = {};
  headers.forEach(function(h, i) { colIndex[String(h).trim()] = i; });

  function cell(row, name) {
    var idx = colIndex[name];
    return idx === undefined ? '' : row[idx];
  }

  var result = [];
  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (!row.join('').toString().trim()) continue;
    result.push({
      recruitmentId:           String(cell(row, 'Recruitment ID') || ''),
      createdDate:             fmtDate_(cell(row, 'Created Date'), 'dd/MM/yyyy HH:mm'),
      fullName:                String(cell(row, 'Full Name') || ''),
      nik:                     String(cell(row, 'NIK') || ''),
      birthDate:               fmtDate_(cell(row, 'Birth Date'), 'dd/MM/yyyy'),
      age:                     cell(row, 'Age') === '' ? '' : Number(cell(row, 'Age')),
      gender:                  String(cell(row, 'Gender') || ''),
      maritalStatus:           String(cell(row, 'Marital Status') || ''),
      email:                   String(cell(row, 'Email') || ''),
      phone:                   String(cell(row, 'Phone') || ''),
      address:                 String(cell(row, 'Address') || ''),
      city:                    String(cell(row, 'City') || ''),
      positionApplied:         String(cell(row, 'Position Applied') || ''),
      education:               String(cell(row, 'Education') || ''),
      workExperience:          String(cell(row, 'Work Experience') || ''),
      lastCompany:             String(cell(row, 'Last Company') || ''),
      currentEmploymentStatus: String(cell(row, 'Current Employment Status') || ''),
      availableToJoin:         String(cell(row, 'Available to Join') || ''),
      expectedSalary:          cell(row, 'Expected Salary') === '' ? 0 : Number(cell(row, 'Expected Salary')),
      recruitmentSource:       String(cell(row, 'Recruitment Source') || ''),
      cvLink:                  String(cell(row, 'CV Link') || ''),
      status:                  String(cell(row, 'Status') || ''),
      hrNotes:                 String(cell(row, 'HR Notes') || ''),
      createdBy:               String(cell(row, 'Created By') || ''),
      updatedAt:               fmtDate_(cell(row, 'Updated At'), 'dd/MM/yyyy HH:mm'),
      holdReason:              String(cell(row, 'Hold Reason') || ''),
      holdFollowUpDate:        fmtDate_(cell(row, 'Hold Follow Up Date'), 'dd/MM/yyyy'),
      blacklistReason:         String(cell(row, 'Blacklist Reason') || ''),
      blacklistDate:           fmtDate_(cell(row, 'Blacklist Date'), 'dd/MM/yyyy HH:mm'),
      blacklistUpdatedBy:      String(cell(row, 'Blacklist Updated By') || ''),
      employeeId:              String(cell(row, 'Employee ID') || ''),
      processedDate:           fmtDate_(cell(row, 'Processed Date'), 'dd/MM/yyyy HH:mm'),
      processedBy:             String(cell(row, 'Processed By') || ''),
      offeringCreated:         fmtDate_(cell(row, 'Offering Created'), 'dd/MM/yyyy HH:mm'),
      offeringUpdated:         fmtDate_(cell(row, 'Offering Updated'), 'dd/MM/yyyy HH:mm'),
      offeringCreatedBy:       String(cell(row, 'Offering Created By') || ''),
      offeringUpdatedBy:       String(cell(row, 'Offering Updated By') || ''),
      offeringCompanyEntity:   String(cell(row, 'Offering Company Entity') || ''),
      offeringPosition:        String(cell(row, 'Offering Position') || ''),
      offeringDepartment:      String(cell(row, 'Offering Department') || ''),
      offeringSalary:          String(cell(row, 'Offering Salary') || ''),
      offeringJoinDate:        String(cell(row, 'Offering Join Date') || ''),
      offeringBenefit:         String(cell(row, 'Offering Benefit') || ''),
      offeringNotes:           String(cell(row, 'Offering Notes') || ''),
      offeringResponse:        (function() {
        var v = String(cell(row, 'Offering Response') || '');
        if (!v && cell(row, 'Offering Created')) v = 'Menunggu';
        return v;
      })(),
      offeringResponseNotes:   String(cell(row, 'Offering Response Notes') || ''),
      offeringResponseDate:    fmtDate_(cell(row, 'Offering Response Date'), 'dd/MM/yyyy HH:mm'),
      offeringResponseBy:      String(cell(row, 'Offering Response By') || ''),
      onboardingStatus:        String(cell(row, 'Onboarding Status') || ''),
      onboardingDate:          fmtDate_(cell(row, 'Onboarding Date'), 'dd/MM/yyyy HH:mm'),
      onboardingBy:            String(cell(row, 'Onboarding By') || ''),
    });
  }

  result.sort(function(a, b) {
    function toTs(s) {
      if (!s) return 0;
      var p = s.split(' '), d = p[0].split('/'), t = p[1] ? p[1].split(':') : ['00','00'];
      return new Date(d[2], d[1]-1, d[0], t[0], t[1]).getTime();
    }
    return toTs(b.processedDate || b.createdDate) - toTs(a.processedDate || a.createdDate);
  });

  return result;
}

function getHoldList()      { return getStatusSheetList_(HOLD_SHEET_NAME); }
function getAcceptedList()  { return getStatusSheetList_(ACCEPTED_SHEET_NAME); }
function getBlacklistList() { return getStatusSheetList_(BLACKLIST_SHEET_NAME); }

// ---- Ubah status antar sheet status (hold/accepted/blacklist) ----
function moveStatusCandidate(recruitmentId, fromStatus, toStatus, reason, hrNotes) {
  var allowed = ['Hold', 'Accepted', 'Blacklist'];
  if (allowed.indexOf(fromStatus) === -1 || allowed.indexOf(toStatus) === -1)
    return { success: false, message: 'Status tidak valid.' };
  if (fromStatus === toStatus)
    return { success: false, message: 'Status asal dan tujuan sama.' };

  var sheetNameMap = { 'Hold': HOLD_SHEET_NAME, 'Accepted': ACCEPTED_SHEET_NAME, 'Blacklist': BLACKLIST_SHEET_NAME };
  var headersMap   = { 'Hold': HOLD_HEADERS, 'Accepted': ACCEPTED_HEADERS, 'Blacklist': BLACKLIST_HEADERS };
  var sheetFnMap   = { 'Hold': getOrCreateHoldSheet_, 'Accepted': getOrCreateAcceptedSheet_, 'Blacklist': getOrCreateBlacklistSheet_ };

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var ss       = SpreadsheetApp.getActiveSpreadsheet();
    var srcSheet = ss.getSheetByName(sheetNameMap[fromStatus]);
    if (!srcSheet || srcSheet.getLastRow() < 2)
      return { success: false, message: 'Sheet ' + fromStatus + ' tidak ditemukan.' };

    var found = findCandidateRow_(srcSheet, recruitmentId);
    if (!found)
      return { success: false, message: 'Recruitment ID tidak ditemukan di ' + fromStatus + ': ' + recruitmentId };

    var user   = Session.getActiveUser().getEmail() || 'HR Dashboard';
    var now    = new Date();
    var nowStr = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    setCell_(srcSheet, found, STATUS_COLUMN_NAME, toStatus);
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(srcSheet, found, NOTES_COLUMN_NAME, hrNotes);
    if (toStatus === 'Hold') {
      setCell_(srcSheet, found, 'Hold Reason', reason || '');
    } else if (toStatus === 'Blacklist') {
      setCell_(srcSheet, found, 'Blacklist Reason', reason || '');
      setCell_(srcSheet, found, 'Blacklist Date', nowStr);
      setCell_(srcSheet, found, 'Blacklist Updated By', user);
    }
    touchUpdatedAt_(srcSheet, found);

    var dstSheet = sheetFnMap[toStatus]();
    var newRow   = buildStatusRow_(found, headersMap[toStatus], nowStr, user);
    dstSheet.appendRow(newRow);

    srcSheet.deleteRow(found.rowNumber);

    if (toStatus === 'Accepted') {
      var existingEmpId = found.values[found.colIndex['Employee ID']];
      if (!existingEmpId) {
        var employeeId = generateEmployeeId_(now);
        var accLastRow = dstSheet.getLastRow();
        var empIdCol   = ACCEPTED_COL['Employee ID'];
        if (empIdCol) dstSheet.getRange(accLastRow, empIdCol).setValue(employeeId);

        var empSheet = getOrCreateEmployeeSheet_();
        var empRow   = new Array(EMPLOYEE_HEADERS.length).fill('');
        empRow[EMPLOYEE_COL['Employee ID'] - 1]       = employeeId;
        empRow[EMPLOYEE_COL['Recruitment ID'] - 1]    = recruitmentId;
        empRow[EMPLOYEE_COL['Full Name'] - 1]         = found.values[found.colIndex['Full Name']];
        empRow[EMPLOYEE_COL['Position'] - 1]          = found.values[found.colIndex['Position Applied']];
        empRow[EMPLOYEE_COL['Email'] - 1]             = found.values[found.colIndex['Email']];
        empRow[EMPLOYEE_COL['Phone'] - 1]             = found.values[found.colIndex['Phone']];
        empRow[EMPLOYEE_COL['Join Date'] - 1]         = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd');
        empRow[EMPLOYEE_COL['Status'] - 1]            = 'Active';
        empRow[EMPLOYEE_COL['Notes'] - 1]             = hrNotes || '';
        empRow[EMPLOYEE_COL['Created At'] - 1]        = nowStr;
        empRow[EMPLOYEE_COL['Company Entity'] - 1]    = 'MITO Group';
        empRow[EMPLOYEE_COL['Employee Type'] - 1]     = 'PKWTT';
        empRow[EMPLOYEE_COL['NIK'] - 1]               = found.values[found.colIndex['NIK']];
        empRow[EMPLOYEE_COL['Birth Date'] - 1]        = found.values[found.colIndex['Birth Date']];
        empRow[EMPLOYEE_COL['Age'] - 1]               = found.values[found.colIndex['Age']];
        empRow[EMPLOYEE_COL['Gender'] - 1]            = found.values[found.colIndex['Gender']];
        empRow[EMPLOYEE_COL['Marital Status'] - 1]    = found.values[found.colIndex['Marital Status']];
        empRow[EMPLOYEE_COL['Address'] - 1]           = found.values[found.colIndex['Address']];
        empRow[EMPLOYEE_COL['City'] - 1]              = found.values[found.colIndex['City']];
        empRow[EMPLOYEE_COL['Education'] - 1]         = found.values[found.colIndex['Education']];
        empRow[EMPLOYEE_COL['Work Experience'] - 1]   = found.values[found.colIndex['Work Experience']];
        empRow[EMPLOYEE_COL['Employment Status'] - 1] = 'Active';
        empRow[EMPLOYEE_COL['Salary'] - 1]            = found.values[found.colIndex['Expected Salary']];
        empRow[EMPLOYEE_COL['Salary Type'] - 1]       = 'Monthly';
        empRow[EMPLOYEE_COL['Recruitment Source'] - 1] = found.values[found.colIndex['Recruitment Source']];
        empRow[EMPLOYEE_COL['HR Notes'] - 1]          = hrNotes || '';
        empRow[EMPLOYEE_COL['Created By'] - 1]        = 'System';
        empRow[EMPLOYEE_COL['Updated At'] - 1]        = nowStr;
        empSheet.appendRow(empRow);
      }
    }

    writeAuditLog_(recruitmentId, 'Move Status', 'Status', fromStatus, toStatus + (reason ? ' (' + reason + ')' : ''));
    return { success: true, recruitmentId: recruitmentId, newStatus: toStatus };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// SAVE OFFERING STATUS — simpan/update timestamp, user, dan detail offering
// Jika belum ada offering → isi Created + CreatedBy + detail offering
// Jika sudah ada → update Updated + UpdatedBy + detail offering
// offerData (opsional): { companyEntity, position, department, salary, joinDate, benefit, notes }
// ============================================================
function saveOfferingStatus(recruitmentId, generatedBy, offerData) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(ACCEPTED_SHEET_NAME);
    if (!sheet || sheet.getLastRow() < 2)
      return { success: false, message: 'Sheet kandidat_accepted tidak ditemukan.' };

    // Pastikan kolom offering tersedia
    ensureStatusSheetHeaders_(sheet, ACCEPTED_HEADERS);

    var lastRow = sheet.getLastRow();
    var lastCol = sheet.getLastColumn();
    var headers = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
      .map(function(h) { return String(h).trim(); });

    var colIndex = {};
    headers.forEach(function(h, i) { colIndex[h] = i + 1; }); // 1-based

    var idCol              = colIndex['Recruitment ID'];
    var offeringCreatedCol = colIndex['Offering Created'];
    var offeringUpdatedCol = colIndex['Offering Updated'];
    var offeringCreatedByCol = colIndex['Offering Created By'];
    var offeringUpdatedByCol = colIndex['Offering Updated By'];
    var companyCol = colIndex['Offering Company Entity'];
    var posCol     = colIndex['Offering Position'];
    var deptCol    = colIndex['Offering Department'];
    var salaryCol  = colIndex['Offering Salary'];
    var joinCol    = colIndex['Offering Join Date'];
    var benefitCol = colIndex['Offering Benefit'];
    var notesCol   = colIndex['Offering Notes'];
    var respCol    = colIndex['Offering Response'];

    if (!idCol)
      return { success: false, message: 'Kolom Recruitment ID tidak ditemukan.' };

    var idValues = sheet.getRange(2, idCol, lastRow - 1, 1).getValues();
    var targetRow = -1;
    for (var r = 0; r < idValues.length; r++) {
      if (String(idValues[r][0]) === String(recruitmentId)) {
        targetRow = r + 2;
        break;
      }
    }
    if (targetRow === -1)
      return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var nowStr = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
    var user   = generatedBy || Session.getActiveUser().getEmail() || 'HR Dashboard';

    offerData = offerData || {};
    // Simpan detail offering (selalu ditulis / diperbarui)
    if (companyCol) sheet.getRange(targetRow, companyCol).setValue(offerData.companyEntity || '');
    if (posCol)     sheet.getRange(targetRow, posCol).setValue(offerData.position || '');
    if (deptCol)    sheet.getRange(targetRow, deptCol).setValue(offerData.department || '');
    if (salaryCol)  sheet.getRange(targetRow, salaryCol).setValue(offerData.salary || '');
    if (joinCol)    sheet.getRange(targetRow, joinCol).setValue(offerData.joinDate || '');
    if (benefitCol) sheet.getRange(targetRow, benefitCol).setValue(offerData.benefit || '');
    if (notesCol)   sheet.getRange(targetRow, notesCol).setValue(offerData.notes || '');

    // Cek apakah sudah ada offering sebelumnya (Offering Created sudah terisi)
    var existingCreated = '';
    if (offeringCreatedCol) {
      existingCreated = sheet.getRange(targetRow, offeringCreatedCol).getValue();
    }

    if (!existingCreated || String(existingCreated).trim() === '') {
      // Belum ada offering → isi Created + CreatedBy + default response "Menunggu"
      if (offeringCreatedCol)     sheet.getRange(targetRow, offeringCreatedCol).setValue(nowStr);
      if (offeringCreatedByCol)   sheet.getRange(targetRow, offeringCreatedByCol).setValue(user);
      if (respCol) {
        var curResp = sheet.getRange(targetRow, respCol).getValue();
        if (!curResp || String(curResp).trim() === '')
          sheet.getRange(targetRow, respCol).setValue('Menunggu');
      }
      writeAuditLog_(recruitmentId, 'Offering Letter Created', 'Offering Created', '-', nowStr + ' by ' + user);
      return { success: true, recruitmentId: recruitmentId, action: 'created', created: nowStr, createdBy: user };
    } else {
      // Sudah ada offering → update Updated + UpdatedBy
      if (offeringUpdatedCol)     sheet.getRange(targetRow, offeringUpdatedCol).setValue(nowStr);
      if (offeringUpdatedByCol)   sheet.getRange(targetRow, offeringUpdatedByCol).setValue(user);
      writeAuditLog_(recruitmentId, 'Offering Letter Updated', 'Offering Updated', '-', nowStr + ' by ' + user);
      return { success: true, recruitmentId: recruitmentId, action: 'updated', updated: nowStr, updatedBy: user };
    }
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// SAVE OFFERING RESPONSE — update respons kandidat terhadap
// offering letter. Dipanggil manual oleh HR.
// response: "Menunggu" | "Diterima" | "Ditolak"
// ============================================================
function saveOfferingResponse(recruitmentId, response, notes, updatedBy) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var ss    = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(ACCEPTED_SHEET_NAME);
    if (!sheet || sheet.getLastRow() < 2)
      return { success: false, message: 'Sheet kandidat_accepted tidak ditemukan.' };

    ensureStatusSheetHeaders_(sheet, ACCEPTED_HEADERS);

    var lastRow = sheet.getLastRow();
    var lastCol = sheet.getLastColumn();
    var headers = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
      .map(function(h) { return String(h).trim(); });

    var colIndex = {};
    headers.forEach(function(h, i) { colIndex[h] = i + 1; });

    var idCol          = colIndex['Recruitment ID'];
    var respCol        = colIndex['Offering Response'];
    var respNotesCol   = colIndex['Offering Response Notes'];
    var respDateCol    = colIndex['Offering Response Date'];
    var respByCol      = colIndex['Offering Response By'];

    if (!idCol) return { success: false, message: 'Kolom Recruitment ID tidak ditemukan.' };

    var idValues  = sheet.getRange(2, idCol, lastRow - 1, 1).getValues();
    var targetRow = -1;
    for (var r = 0; r < idValues.length; r++) {
      if (String(idValues[r][0]) === String(recruitmentId)) {
        targetRow = r + 2;
        break;
      }
    }
    if (targetRow === -1)
      return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var nowStr = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
    var user   = updatedBy || Session.getActiveUser().getEmail() || 'HR Dashboard';

    if (respCol)      sheet.getRange(targetRow, respCol).setValue(response || 'Menunggu');
    if (respNotesCol) sheet.getRange(targetRow, respNotesCol).setValue(notes || '');
    if (respDateCol)  sheet.getRange(targetRow, respDateCol).setValue(nowStr);
    if (respByCol)    sheet.getRange(targetRow, respByCol).setValue(user);

    writeAuditLog_(recruitmentId, 'Offering Response', 'Offering Response',
      '-', (response || 'Menunggu') + (notes ? ' — ' + notes : '') + ' by ' + user);

    return { success: true, recruitmentId: recruitmentId, response: response, updatedBy: user };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Simpan HR Notes (tanpa ubah status) ----
function saveHrNotes(recruitmentId, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return {
        success: false,
        message: "Recruitment ID tidak ditemukan: " + recruitmentId,
      };

    setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes || "");
    touchUpdatedAt_(sheet, found);

    return {
      success: true,
      recruitmentId: recruitmentId,
      updatedAt: Utilities.formatDate(new Date(), "GMT+7", "dd/MM/yyyy HH:mm"),
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Hapus kandidat ----
function deleteCandidate(recruitmentId) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2)
      return {
        success: false,
        message: "Sheet data kandidat tidak ditemukan.",
      };

    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return {
        success: false,
        message: "Recruitment ID tidak ditemukan: " + recruitmentId,
      };

    sheet.deleteRow(found.rowNumber);
    writeAuditLog_(recruitmentId, "Deleted", "Status", "-", "Deleted");
    return { success: true, recruitmentId: recruitmentId };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// getDashboardStatusCounts — hitung jumlah record di setiap
// sheet status untuk stat cards di halaman Dashboard utama.
// ============================================================
function getDashboardStatusCounts() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  function count(name) {
    var s = ss.getSheetByName(name);
    if (!s || s.getLastRow() < 2) return 0;
    return s.getLastRow() - 1;
  }
  return {
    pending:   count(SHEET_NAME),
    hold:      count(HOLD_SHEET_NAME),
    accepted:  count(ACCEPTED_SHEET_NAME),
    blacklist: count(BLACKLIST_SHEET_NAME),
    total:     count(SHEET_NAME) + count(HOLD_SHEET_NAME) + count(ACCEPTED_SHEET_NAME) + count(BLACKLIST_SHEET_NAME),
  };
}

// ============================================================
// GET CANDIDATE BY NIK — untuk halaman update data kandidat
// Mencari data kandidat di sheet kandidat_accepted berdasarkan NIK KTP
// ============================================================
function getCandidateByNik(nik) {
  try {
    if (!nik || !/^\d{16}$/.test(String(nik).trim())) {
      return { success: false, message: 'NIK harus tepat 16 digit angka.' };
    }

    var searchNik = "'" + String(nik).trim();
    var sheet = getOrCreateAcceptedSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      return { success: false, message: 'Data tidak ditemukan. Pastikan Anda sudah diterima sebagai karyawan.' };
    }

    var lastRow = sheet.getLastRow();
    var lastCol = sheet.getLastColumn();
    var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
    var headers = values[0];

    var colIndex = {};
    headers.forEach(function(h, i) { colIndex[String(h).trim()] = i; });

    var nikCol = colIndex['NIK'];
    if (nikCol === undefined) {
      return { success: false, message: 'Kolom NIK tidak ditemukan di sheet.' };
    }

    for (var r = 1; r < values.length; r++) {
      var row = values[r];
      var rowNik = String(row[nikCol] || '').trim();
      if (rowNik === searchNik || rowNik === String(nik).trim()) {
        function cell(name) {
          var idx = colIndex[name];
          return idx === undefined ? '' : row[idx];
        }

        return {
          success: true,
          data: {
            recruitmentId: String(cell('Recruitment ID') || ''),
            fullName: String(cell('Full Name') || ''),
            nik: String(cell('NIK') || '').replace(/^'/, ''),
            birthDate: fmtDateStr_(cell('Birth Date')),
            age: cell('Age') === '' ? '' : Number(cell('Age')),
            gender: String(cell('Gender') || ''),
            maritalStatus: String(cell('Marital Status') || ''),
            email: String(cell('Email') || ''),
            phone: String(cell('Phone') || '').replace(/^'/, ''),
            address: String(cell('Address') || ''),
            city: String(cell('City') || ''),
            positionApplied: String(cell('Position Applied') || ''),
            education: String(cell('Education') || ''),
            workExperience: String(cell('Work Experience') || ''),
            lastCompany: String(cell('Last Company') || ''),
            currentEmploymentStatus: String(cell('Current Employment Status') || ''),
            availableToJoin: String(cell('Available to Join') || ''),
            expectedSalary: cell('Expected Salary') === '' ? 0 : Number(cell('Expected Salary')),
            recruitmentSource: String(cell('Recruitment Source') || ''),
            employeeId: String(cell('Employee ID') || ''),
            status: String(cell('Status') || ''),
          }
        };
      }
    }

    return { success: false, message: 'Data dengan NIK tersebut tidak ditemukan. Pastikan Anda sudah diterima sebagai karyawan.' };
  } catch (err) {
    return { success: false, message: err.message };
  }
}

// ============================================================
// UPDATE ACCEPTED CANDIDATE DATA — update data tambahan oleh kandidat
// Hanya field tertentu yang boleh diupdate oleh kandidat sendiri
// ============================================================
function updateAcceptedCandidateData(nik, formData) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    if (!nik || !/^\d{16}$/.test(String(nik).trim())) {
      return { success: false, message: 'NIK harus tepat 16 digit angka.' };
    }

    var searchNik = "'" + String(nik).trim();
    var sheet = getOrCreateAcceptedSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      return { success: false, message: 'Data tidak ditemukan.' };
    }

    var lastRow = sheet.getLastRow();
    var lastCol = sheet.getLastColumn();
    var values = sheet.getRange(1, 1, lastRow, lastCol).getValues();
    var headers = values[0];

    var colIndex = {};
    headers.forEach(function(h, i) { colIndex[String(h).trim()] = i; });

    var nikCol = colIndex['NIK'];
    if (nikCol === undefined) {
      return { success: false, message: 'Kolom NIK tidak ditemukan.' };
    }

    var foundRow = -1;
    for (var r = 1; r < values.length; r++) {
      var rowNik = String(values[r][nikCol] || '').trim();
      if (rowNik === searchNik || rowNik === String(nik).trim()) {
        foundRow = r + 1;
        break;
      }
    }

    if (foundRow === -1) {
      return { success: false, message: 'Data dengan NIK tersebut tidak ditemukan.' };
    }

    var editableFields = [
      'Full Name', 'Birth Date', 'Age', 'Gender', 'Marital Status',
      'Email', 'Phone', 'Address', 'City', 'Education',
      'Work Experience', 'Last Company', 'Current Employment Status',
      'Available to Join', 'Expected Salary', 'Recruitment Source'
    ];

    var fieldMap = {
      full_name: 'Full Name',
      birth_date: 'Birth Date',
      age: 'Age',
      gender: 'Gender',
      marital_status: 'Marital Status',
      email: 'Email',
      phone: 'Phone',
      address: 'Address',
      city: 'City',
      education: 'Education',
      work_experience: 'Work Experience',
      last_company: 'Last Company',
      current_employment_status: 'Current Employment Status',
      available_to_join: 'Available to Join',
      expected_salary: 'Expected Salary',
      recruitment_source: 'Recruitment Source'
    };

    var updatedCount = 0;
    var nowStr = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    Object.keys(formData).forEach(function(key) {
      var headerName = fieldMap[key];
      if (!headerName || editableFields.indexOf(headerName) === -1) return;

      var colIdx = colIndex[headerName];
      if (colIdx === undefined) return;

      var newVal = formData[key];
      if (key === 'phone') newVal = "'" + String(newVal || '');
      if (key === 'age') newVal = Number(newVal) || '';
      if (key === 'expected_salary') newVal = Number(String(newVal || '').replace(/\D/g, '')) || 0;

      var oldVal = values[foundRow - 1][colIdx];
      sheet.getRange(foundRow, colIdx + 1).setValue(newVal);

      if (String(oldVal) !== String(newVal)) {
        var recruitmentId = values[foundRow - 1][colIndex['Recruitment ID']];
        writeAuditLog_(recruitmentId, 'Update Data', headerName, oldVal, newVal);
        updatedCount++;
      }
    });

    var updatedAtCol = colIndex['Updated At'];
    if (updatedAtCol !== undefined) {
      sheet.getRange(foundRow, updatedAtCol + 1).setValue(nowStr);
    }

    return {
      success: true,
      message: 'Data berhasil diperbarui (' + updatedCount + ' field diubah).',
      updatedAt: nowStr
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
