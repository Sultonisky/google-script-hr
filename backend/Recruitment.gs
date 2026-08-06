// ============================================================
// backend/Recruitment.gs — CRUD KANDIDAT
// ============================================================

// ---- Simpan pelamar baru ----
function simpanDataKandidat(formObject) {
  try {
    var validationError = validateFormData_(formObject);
    if (validationError) return "Error: " + validationError;

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
  }
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

    // Hapus dari raw_kandidat
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

    // Hapus dari raw_kandidat
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

    // Hapus dari raw_kandidat
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
      newRow[EMPLOYEE_COL['Company Entity'] - 1]    = 'PT Mahakarya Sukses Indonesia';
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
        empRow[EMPLOYEE_COL['Company Entity'] - 1]    = 'PT Mahakarya Sukses Indonesia';
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
