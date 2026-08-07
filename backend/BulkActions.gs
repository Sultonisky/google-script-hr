// ============================================================
// backend/BulkActions.gs — BULK UPDATE STATUS & BULK DELETE
// ============================================================

function bulkUpdateStatus(recruitmentIds, newStatus) {
  var allowed = ['Pending', 'Accepted', 'Hold', 'Blacklist'];
  if (allowed.indexOf(newStatus) === -1)
    return { success: false, message: 'Status tidak valid: ' + newStatus, updated: 0, failed: 0 };
  if (!recruitmentIds || !Array.isArray(recruitmentIds) || recruitmentIds.length === 0)
    return { success: false, message: 'Tidak ada kandidat yang dipilih.', updated: 0, failed: 0 };

  var lock = LockService.getScriptLock();
  lock.waitLock(30000);
  try {
    var updated = 0, failed = 0, failedIds = [];

    for (var i = 0; i < recruitmentIds.length; i++) {
      try {
        var result = bulkMoveOne_(recruitmentIds[i], newStatus);
        if (result && result.success) { updated++; } else { failed++; failedIds.push(recruitmentIds[i]); }
      } catch (e) {
        failed++;
        failedIds.push(recruitmentIds[i]);
      }
    }

    return {
      success: updated > 0,
      message: updated + ' kandidat berhasil diupdate. ' + failed + ' gagal.',
      updated: updated, failed: failed, failedIds: failedIds,
    };
  } catch (err) {
    return { success: false, message: err.message, updated: 0, failed: recruitmentIds.length };
  } finally {
    lock.releaseLock();
  }
}

// Helper bulk tanpa lock — dipanggil dari dalam bulkUpdateStatus yang sudah memegang lock.
function bulkMoveOne_(recruitmentId, newStatus) {
  var sheet = getDashboardSheet_();
  var found = findCandidateRow_(sheet, recruitmentId);
  if (!found) return { success: false, message: 'Tidak ditemukan' };

  var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
  var user      = Session.getActiveUser().getEmail() || 'HR Dashboard';
  var now       = new Date();
  var nowStr    = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

  if (newStatus === 'Hold') {
    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Hold');
    setCell_(sheet, found, 'Hold Reason', 'Bulk update');
    touchUpdatedAt_(sheet, found);
    var holdSheet = getOrCreateHoldSheet_();
    holdSheet.appendRow(buildStatusRow_(found, HOLD_HEADERS, nowStr, user));
    sheet.deleteRow(found.rowNumber);
    writeAuditLog_(recruitmentId, 'Hold', 'Status', oldStatus, 'Hold (Bulk update)');

  } else if (newStatus === 'Blacklist') {
    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Blacklist');
    setCell_(sheet, found, 'Blacklist Reason', 'Bulk update');
    setCell_(sheet, found, 'Blacklist Date', nowStr);
    setCell_(sheet, found, 'Blacklist Updated By', user);
    touchUpdatedAt_(sheet, found);
    var blSheet = getOrCreateBlacklistSheet_();
    blSheet.appendRow(buildStatusRow_(found, BLACKLIST_HEADERS, nowStr, user));
    sheet.deleteRow(found.rowNumber);
    writeAuditLog_(recruitmentId, 'Blacklist', 'Status', oldStatus, 'Blacklist (Bulk update)');

  } else if (newStatus === 'Accepted') {
    var existingEmpId = found.values[found.colIndex['Employee ID']];
    var employeeId    = existingEmpId ? String(existingEmpId) : generateEmployeeId_(now);
    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Accepted');
    setCell_(sheet, found, 'Employee ID', employeeId);
    touchUpdatedAt_(sheet, found);
    var accSheet = getOrCreateAcceptedSheet_();
    accSheet.appendRow(buildStatusRow_(found, ACCEPTED_HEADERS, nowStr, user));
    sheet.deleteRow(found.rowNumber);
    if (!existingEmpId) {
      var empSheet = getOrCreateEmployeeSheet_();
      var empRow   = new Array(EMPLOYEE_HEADERS.length).fill('');
      empRow[EMPLOYEE_COL['Employee ID'] - 1]     = employeeId;
      empRow[EMPLOYEE_COL['Recruitment ID'] - 1]  = recruitmentId;
      empRow[EMPLOYEE_COL['Full Name'] - 1]        = found.values[found.colIndex['Full Name']];
      empRow[EMPLOYEE_COL['Position'] - 1]         = found.values[found.colIndex['Position Applied']];
      empRow[EMPLOYEE_COL['Email'] - 1]            = found.values[found.colIndex['Email']];
      empRow[EMPLOYEE_COL['Phone'] - 1]            = found.values[found.colIndex['Phone']];
      empRow[EMPLOYEE_COL['Join Date'] - 1]        = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd');
      empRow[EMPLOYEE_COL['Status'] - 1]           = 'Active';
      empRow[EMPLOYEE_COL['Created At'] - 1]       = nowStr;
      empRow[EMPLOYEE_COL['Company Entity'] - 1]   = 'MITO Group';
      empRow[EMPLOYEE_COL['Employee Type'] - 1]    = 'PKWTT';
      empRow[EMPLOYEE_COL['NIK'] - 1]              = found.values[found.colIndex['NIK']];
      empRow[EMPLOYEE_COL['Employment Status'] - 1] = 'Active';
      empRow[EMPLOYEE_COL['Salary'] - 1]           = found.values[found.colIndex['Expected Salary']];
      empRow[EMPLOYEE_COL['Salary Type'] - 1]      = 'Monthly';
      empRow[EMPLOYEE_COL['Created By'] - 1]       = 'System';
      empRow[EMPLOYEE_COL['Updated At'] - 1]       = nowStr;
      empSheet.appendRow(empRow);
    }
    writeAuditLog_(recruitmentId, 'Accepted', 'Status', oldStatus, 'Accepted -> Employee ' + employeeId);

  } else {
    // Pending — update in place
    setCell_(sheet, found, STATUS_COLUMN_NAME, 'Pending');
    touchUpdatedAt_(sheet, found);
    writeAuditLog_(recruitmentId, 'Bulk Update Status', 'Status', oldStatus, 'Pending');
  }

  return { success: true };
}

function bulkDeleteCandidates(recruitmentIds) {
  if (
    !recruitmentIds ||
    !Array.isArray(recruitmentIds) ||
    recruitmentIds.length === 0
  )
    return {
      success: false,
      message: "Tidak ada kandidat yang dipilih.",
      deleted: 0,
      failed: 0,
    };

  var lock = LockService.getScriptLock();
  lock.waitLock(30000);
  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2)
      return {
        success: false,
        message: "Sheet data kandidat tidak ditemukan.",
        deleted: 0,
        failed: 0,
      };

    var rowsToDelete = [],
      failed = 0,
      failedIds = [];

    for (var i = 0; i < recruitmentIds.length; i++) {
      var found = findCandidateRow_(sheet, recruitmentIds[i]);
      if (found)
        rowsToDelete.push({
          rowNumber: found.rowNumber,
          id: recruitmentIds[i],
        });
      else {
        failed++;
        failedIds.push(recruitmentIds[i]);
      }
    }

    // Hapus dari bawah ke atas agar row number tidak bergeser
    rowsToDelete.sort(function (a, b) {
      return b.rowNumber - a.rowNumber;
    });

    var deleted = 0;
    for (var j = 0; j < rowsToDelete.length; j++) {
      try {
        sheet.deleteRow(rowsToDelete[j].rowNumber);
        writeAuditLog_(
          rowsToDelete[j].id,
          "Bulk Deleted",
          "Status",
          "-",
          "Deleted",
        );
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
      failedIds: failedIds,
    };
  } catch (err) {
    return {
      success: false,
      message: err.message,
      deleted: 0,
      failed: recruitmentIds.length,
    };
  } finally {
    lock.releaseLock();
  }
}

