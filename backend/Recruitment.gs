// ============================================================
// backend/Recruitment.gs — CRUD KANDIDAT
// ============================================================

// ---- Simpan pelamar baru ----
function simpanDataKandidat(formObject) {
  try {
    var validationError = validateFormData_(formObject);
    if (validationError) return 'Error: ' + validationError;

    var sheet      = getOrCreateSheet_();
    var timestamp  = new Date();
    var recruitmentId = generateRecruitmentId_(timestamp);
    var createdDate   = Utilities.formatDate(timestamp, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    var lastCompany = formObject.work_experience === 'Fresh Graduate'
      ? '-'
      : (formObject.last_company && formObject.last_company.trim() !== ''
          ? formObject.last_company.trim()
          : '-');

    sheet.appendRow([
      recruitmentId,
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
      formObject.position_applied,
      formObject.education,
      formObject.work_experience || '',
      lastCompany,
      formObject.current_employment_status,
      formObject.available_to_join,
      Number(formObject.expected_salary) || 0,
      formObject.recruitment_source || '',
      '',       // CV Link
      'Pending',
      '',       // HR Notes
      'System',
      createdDate
    ]);

    writeAuditLog_(recruitmentId, 'Created', '-', 'Pending');
    return 'Sukses';
  } catch (error) {
    return 'Error: ' + error.toString();
  }
}

// ---- Ambil seluruh kandidat (dashboard) ----
function getRecruitmentList() {
  var sheet = getDashboardSheet_();
  if (!sheet || sheet.getLastRow() < 2) return [];

  var lastRow  = sheet.getLastRow();
  var lastCol  = sheet.getLastColumn();
  var values   = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var headers  = values[0];

  var colIndex = {};
  headers.forEach(function(header, i) { colIndex[String(header).trim()] = i; });

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
      createdDate:             fmtDate_(cell(row, 'Created Date'),    'dd/MM/yyyy HH:mm'),
      fullName:                String(cell(row, 'Full Name')    || ''),
      nik:                     String(cell(row, 'NIK')          || ''),
      birthDate:               fmtDate_(cell(row, 'Birth Date'),      'dd/MM/yyyy'),
      age:                     cell(row, 'Age') === '' ? '' : Number(cell(row, 'Age')),
      gender:                  String(cell(row, 'Gender')        || ''),
      maritalStatus:           String(cell(row, 'Marital Status') || ''),
      email:                   String(cell(row, 'Email')         || ''),
      phone:                   String(cell(row, 'Phone')         || ''),
      address:                 String(cell(row, 'Address')       || ''),
      city:                    String(cell(row, 'City')          || ''),
      positionApplied:         String(cell(row, 'Position Applied') || ''),
      education:               String(cell(row, 'Education')     || ''),
      workExperience:          String(cell(row, 'Work Experience') || ''),
      lastCompany:             String(cell(row, 'Last Company')  || ''),
      currentEmploymentStatus: String(cell(row, 'Current Employment Status') || ''),
      availableToJoin:         String(cell(row, 'Available to Join') || ''),
      expectedSalary:          cell(row, 'Expected Salary') === '' ? 0 : Number(cell(row, 'Expected Salary')),
      recruitmentSource:       String(cell(row, 'Recruitment Source') || ''),
      cvLink:                  String(cell(row, 'CV Link')       || ''),
      status:                  String(cell(row, 'Status')        || 'Pending'),
      hrNotes:                 String(cell(row, 'HR Notes')      || ''),
      createdBy:               String(cell(row, 'Created By')    || ''),
      updatedAt:               fmtDate_(cell(row, 'Updated At'),      'dd/MM/yyyy HH:mm'),
      holdReason:              String(cell(row, 'Hold Reason')   || ''),
      holdFollowUpDate:        fmtDate_(cell(row, 'Hold Follow Up Date'), 'dd/MM/yyyy'),
      blacklistReason:         String(cell(row, 'Blacklist Reason') || ''),
      blacklistDate:           fmtDate_(cell(row, 'Blacklist Date'), 'dd/MM/yyyy HH:mm'),
      blacklistUpdatedBy:      String(cell(row, 'Blacklist Updated By') || ''),
      employeeId:              String(cell(row, 'Employee ID')   || '')
    });
  }

  result.sort(function(a, b) {
    function toTs(s) {
      if (!s) return 0;
      var p = s.split(' '), d = p[0].split('/'), t = p[1] ? p[1].split(':') : ['00','00'];
      return new Date(d[2], d[1]-1, d[0], t[0], t[1]).getTime();
    }
    return toTs(b.createdDate) - toTs(a.createdDate);
  });

  return result;
}

// ---- Update status (generik) ----
function updateCandidateStatus(recruitmentId, newStatus, hrNotes) {
  var allowed = ['Pending','Accepted','Hold','Blacklist'];
  if (allowed.indexOf(newStatus) === -1)
    return { success: false, message: 'Status tidak valid: ' + newStatus };

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2)
      return { success: false, message: 'Sheet data kandidat tidak ditemukan.' };

    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    setCell_(sheet, found, STATUS_COLUMN_NAME, newStatus);
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);
    writeAuditLog_(recruitmentId, 'Update Status', oldStatus, newStatus);

    return { success: true, recruitmentId: recruitmentId, newStatus: newStatus };
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
    if (!found) return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Hold');
    setCell_(sheet, found, 'Hold Reason', reason || '');
    setCell_(sheet, found, 'Hold Follow Up Date', followUpDate || '');
    if (hrNotes !== undefined && hrNotes !== null) setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);
    writeAuditLog_(recruitmentId, 'Hold', oldStatus, 'Hold (' + (reason || '-') + ')');

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
    if (!found) return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var user      = Session.getActiveUser().getEmail() || 'HR Dashboard';
    var now       = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Blacklist');
    setCell_(sheet, found, 'Blacklist Reason', reason || '');
    setCell_(sheet, found, 'Blacklist Date', now);
    setCell_(sheet, found, 'Blacklist Updated By', user);
    if (hrNotes !== undefined && hrNotes !== null) setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);
    writeAuditLog_(recruitmentId, 'Blacklist', oldStatus, 'Blacklist (' + (reason || '-') + ')');

    return { success: true, recruitmentId: recruitmentId, newStatus: 'Blacklist' };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Accept kandidat → Employee ----
function acceptCandidateToEmployee(recruitmentId, hrNotes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    var oldStatus    = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var existingEmpId = found.values[found.colIndex['Employee ID']];
    var now           = new Date();
    var employeeId    = existingEmpId ? String(existingEmpId) : generateEmployeeId_(now);

    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Accepted');
    setCell_(sheet, found, 'Employee ID', employeeId);
    if (hrNotes !== undefined && hrNotes !== null) setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    if (!existingEmpId) {
      var empSheet = getOrCreateEmployeeSheet_();
      var createdAt = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
      empSheet.appendRow([
        employeeId,                                              // Employee ID
        'PT Mahakarya Sukses Indonesia',                         // Company Entity
        'PKWTT',                                                 // Employee Type (default organik, HR dapat edit)
        found.values[found.colIndex['Full Name']],               // Full Name
        found.values[found.colIndex['NIK']],                     // NIK
        found.values[found.colIndex['Birth Date']],              // Birth Date
        found.values[found.colIndex['Age']],                     // Age
        found.values[found.colIndex['Gender']],                  // Gender
        found.values[found.colIndex['Marital Status']],          // Marital Status
        found.values[found.colIndex['Email']],                   // Email
        found.values[found.colIndex['Phone']],                   // Phone
        found.values[found.colIndex['Address']],                 // Address
        found.values[found.colIndex['City']],                    // City
        found.values[found.colIndex['Education']],               // Education
        found.values[found.colIndex['Work Experience']],         // Work Experience
        '',                                                      // Department (HR isi manual)
        found.values[found.colIndex['Position Applied']],        // Position
        Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd'),        // Join Date
        '',                                                      // Contract Start
        '',                                                      // Contract End
        '',                                                      // Contract Duration
        'Active',                                                // Employment Status
        found.values[found.colIndex['Expected Salary']],         // Salary
        'Monthly',                                               // Salary Type
        '',                                                      // Outsource Vendor
        recruitmentId,                                           // Recruitment ID
        found.values[found.colIndex['Recruitment Source']],      // Recruitment Source
        hrNotes || '',                                           // HR Notes
        'System',                                                // Created By
        createdAt                                                // Updated At
      ]);
    }

    writeAuditLog_(recruitmentId, 'Accepted', oldStatus, 'Accepted -> Employee ' + employeeId);
    return { success: true, recruitmentId: recruitmentId, newStatus: 'Accepted', employeeId: employeeId };
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
    if (!found) return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes || '');
    touchUpdatedAt_(sheet, found);

    return {
      success: true,
      recruitmentId: recruitmentId,
      updatedAt: Utilities.formatDate(new Date(), 'GMT+7', 'dd/MM/yyyy HH:mm')
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
      return { success: false, message: 'Sheet data kandidat tidak ditemukan.' };

    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return { success: false, message: 'Recruitment ID tidak ditemukan: ' + recruitmentId };

    sheet.deleteRow(found.rowNumber);
    writeAuditLog_(recruitmentId, 'Deleted', '-', 'Deleted');
    return { success: true, recruitmentId: recruitmentId };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
