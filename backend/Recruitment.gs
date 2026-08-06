// ============================================================
// backend/Recruitment.gs — CRUD KANDIDAT
// ============================================================

// ============================================================
// ARCHIVE KANDIDAT (Hold / Blacklist)
// ============================================================

// Generate Archive ID — ARC-YYYYMMDD-XXXX (counter reset harian).
// NOTE: caller (holdCandidate/blacklistCandidate) sudah memegang
// LockService, jadi fungsi ini TIDAK mengambil lock lagi (nested
// lock bisa deadlock di Apps Script).
function generateArchiveId_(date) {
  var datePart = Utilities.formatDate(date, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "ARC_COUNTER_" + datePart;
  var counter = Number(props.getProperty(key) || "0") + 1;
  props.setProperty(key, String(counter));
  return "ARC-" + datePart + "-" + ("0000" + counter).slice(-4);
}

// Simpan salinan data kandidat ke sheet Archive sebagai backup historis.
// Dipanggil dari holdCandidate_ & blacklistCandidate setelah status
// berubah di raw_kandidat. Tidak menghapus data dari raw_kandidat.
function archiveCandidateData_(recruitmentId, archiveReason, notes) {
  try {
    var sheet = getDashboardSheet_();
    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found)
      return { success: false, message: "Kandidat tidak ditemukan: " + recruitmentId };

    var src = found.values;
    var idx = found.colIndex;

    function cell(name) {
      var i = idx[name];
      return i === undefined ? "" : String(src[i] || "");
    }

    var user = Session.getActiveUser().getEmail() || "HR Dashboard";
    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var archiveId = generateArchiveId_(now);

    var payload = {};
    for (var k in idx) payload[k] = src[idx[k]];

    // Build row aligned to ARCHIVE_HEADERS
    var row = new Array(ARCHIVE_HEADERS.length).fill("");
    row[ARCHIVE_COL["Archive ID"] - 1] = archiveId;
    row[ARCHIVE_COL["Original Type"] - 1] = "Recruitment";
    row[ARCHIVE_COL["Original ID"] - 1] = recruitmentId;
    row[ARCHIVE_COL["Full Name"] - 1] = cell("Full Name");
    row[ARCHIVE_COL["NIK"] - 1] = cell("NIK");
    row[ARCHIVE_COL["Email"] - 1] = cell("Email");
    row[ARCHIVE_COL["Phone"] - 1] = cell("Phone");
    row[ARCHIVE_COL["Position"] - 1] = cell("Position Applied");
    row[ARCHIVE_COL["Department"] - 1] = "";
    row[ARCHIVE_COL["Status"] - 1] = cell("Status");
    row[ARCHIVE_COL["Archive Reason"] - 1] = archiveReason || "";
    row[ARCHIVE_COL["Archive Date"] - 1] = nowStr;
    row[ARCHIVE_COL["Archived By"] - 1] = user;
    row[ARCHIVE_COL["Notes"] - 1] =
      notes !== undefined && notes !== null && notes !== "" ? String(notes) : cell("HR Notes");
    row[ARCHIVE_COL["Raw JSON"] - 1] = JSON.stringify(payload);
    row[ARCHIVE_COL["Created At"] - 1] = nowStr;

    var archiveSheet = getOrCreateArchiveSheet_();
    archiveSheet.appendRow(row);

    writeAuditLog_(
      recruitmentId,
      "Archive",
      "Status",
      cell("Status"),
      "Archived -> " + archiveId,
    );

    return { success: true, archiveId: archiveId };
  } catch (err) {
    return { success: false, message: err.toString() };
  }
}

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
      return {
        success: false,
        message: "Recruitment ID tidak ditemukan: " + recruitmentId,
      };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    setCell_(sheet, found, STATUS_COLUMN_NAME, "Hold");
    setCell_(sheet, found, "Hold Reason", reason || "");
    setCell_(sheet, found, "Hold Follow Up Date", followUpDate || "");
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    var archiveResult = archiveCandidateData_(
      recruitmentId,
      "Hold: " + (reason || "No reason"),
      hrNotes,
    );

    writeAuditLog_(
      recruitmentId,
      "Hold",
      "Status",
      oldStatus,
      "Hold (" + (reason || "-") + ")",
    );

    return {
      success: true,
      recruitmentId: recruitmentId,
      newStatus: "Hold",
      archiveId: archiveResult.success ? archiveResult.archiveId : null,
      archiveError: archiveResult.success ? null : archiveResult.message,
    };
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
      return {
        success: false,
        message: "Recruitment ID tidak ditemukan: " + recruitmentId,
      };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var user = Session.getActiveUser().getEmail() || "HR Dashboard";
    var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");

    setCell_(sheet, found, STATUS_COLUMN_NAME, "Blacklist");
    setCell_(sheet, found, "Blacklist Reason", reason || "");
    setCell_(sheet, found, "Blacklist Date", now);
    setCell_(sheet, found, "Blacklist Updated By", user);
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    var archiveResult = archiveCandidateData_(
      recruitmentId,
      "Blacklist: " + (reason || "No reason"),
      hrNotes,
    );

    writeAuditLog_(
      recruitmentId,
      "Blacklist",
      "Status",
      oldStatus,
      "Blacklist (" + (reason || "-") + ")",
    );

    return {
      success: true,
      recruitmentId: recruitmentId,
      newStatus: "Blacklist",
      archiveId: archiveResult.success ? archiveResult.archiveId : null,
      archiveError: archiveResult.success ? null : archiveResult.message,
    };
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
    if (!found)
      return {
        success: false,
        message: "Recruitment ID tidak ditemukan: " + recruitmentId,
      };

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
    var existingEmpId = found.values[found.colIndex["Employee ID"]];
    var now = new Date();
    var employeeId = existingEmpId
      ? String(existingEmpId)
      : generateEmployeeId_(now);

    setCell_(sheet, found, STATUS_COLUMN_NAME, "Accepted");
    setCell_(sheet, found, "Employee ID", employeeId);
    if (hrNotes !== undefined && hrNotes !== null)
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    touchUpdatedAt_(sheet, found);

    if (!existingEmpId) {
      var empSheet = getOrCreateEmployeeSheet_();
      var createdAt = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");

      // Build row using EMPLOYEE_COL for exact column alignment
      var newRow = new Array(EMPLOYEE_HEADERS.length).fill("");
      newRow[EMPLOYEE_COL["Employee ID"] - 1] = employeeId;
      newRow[EMPLOYEE_COL["Recruitment ID"] - 1] = recruitmentId;
      newRow[EMPLOYEE_COL["Full Name"] - 1] =
        found.values[found.colIndex["Full Name"]];
      newRow[EMPLOYEE_COL["Position"] - 1] =
        found.values[found.colIndex["Position Applied"]];
      newRow[EMPLOYEE_COL["Email"] - 1] = found.values[found.colIndex["Email"]];
      newRow[EMPLOYEE_COL["Phone"] - 1] = found.values[found.colIndex["Phone"]];
      newRow[EMPLOYEE_COL["Join Date"] - 1] = Utilities.formatDate(
        now,
        "GMT+7",
        "yyyy-MM-dd",
      );
      newRow[EMPLOYEE_COL["Status"] - 1] = "Active";
      newRow[EMPLOYEE_COL["Notes"] - 1] = hrNotes || "";
      newRow[EMPLOYEE_COL["Created At"] - 1] = createdAt;
      newRow[EMPLOYEE_COL["Company Entity"] - 1] =
        "PT Mahakarya Sukses Indonesia";
      newRow[EMPLOYEE_COL["Employee Type"] - 1] = "PKWTT";
      newRow[EMPLOYEE_COL["NIK"] - 1] = found.values[found.colIndex["NIK"]];
      newRow[EMPLOYEE_COL["Birth Date"] - 1] =
        found.values[found.colIndex["Birth Date"]];
      newRow[EMPLOYEE_COL["Age"] - 1] = found.values[found.colIndex["Age"]];
      newRow[EMPLOYEE_COL["Gender"] - 1] =
        found.values[found.colIndex["Gender"]];
      newRow[EMPLOYEE_COL["Marital Status"] - 1] =
        found.values[found.colIndex["Marital Status"]];
      newRow[EMPLOYEE_COL["Address"] - 1] =
        found.values[found.colIndex["Address"]];
      newRow[EMPLOYEE_COL["City"] - 1] = found.values[found.colIndex["City"]];
      newRow[EMPLOYEE_COL["Education"] - 1] =
        found.values[found.colIndex["Education"]];
      newRow[EMPLOYEE_COL["Work Experience"] - 1] =
        found.values[found.colIndex["Work Experience"]];
      newRow[EMPLOYEE_COL["Department"] - 1] = "";
      newRow[EMPLOYEE_COL["Division"] - 1] = "";
      newRow[EMPLOYEE_COL["Branch"] - 1] = "";
      newRow[EMPLOYEE_COL["Contract Start"] - 1] = "";
      newRow[EMPLOYEE_COL["Contract End"] - 1] = "";
      newRow[EMPLOYEE_COL["Contract Duration"] - 1] = "";
      newRow[EMPLOYEE_COL["Employment Status"] - 1] = "Active";
      newRow[EMPLOYEE_COL["Salary"] - 1] =
        found.values[found.colIndex["Expected Salary"]];
      newRow[EMPLOYEE_COL["Salary Type"] - 1] = "Monthly";
      newRow[EMPLOYEE_COL["Outsource Vendor"] - 1] = "";
      newRow[EMPLOYEE_COL["Contract Number"] - 1] = "";
      newRow[EMPLOYEE_COL["District"] - 1] = "";
      newRow[EMPLOYEE_COL["Recruitment Source"] - 1] =
        found.values[found.colIndex["Recruitment Source"]];
      newRow[EMPLOYEE_COL["HR Notes"] - 1] = hrNotes || "";
      newRow[EMPLOYEE_COL["Created By"] - 1] = "System";
      newRow[EMPLOYEE_COL["Updated At"] - 1] = createdAt;

      empSheet.appendRow(newRow);
    }

    writeAuditLog_(
      recruitmentId,
      "Accepted",
      "Status",
      oldStatus,
      "Accepted -> Employee " + employeeId,
    );
    return {
      success: true,
      recruitmentId: recruitmentId,
      newStatus: "Accepted",
      employeeId: employeeId,
    };
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
