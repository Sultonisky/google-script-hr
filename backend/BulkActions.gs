// ============================================================
// backend/BulkActions.gs — BULK UPDATE STATUS & BULK DELETE
// ============================================================

function bulkUpdateStatus(recruitmentIds, newStatus) {
  var allowed = ['Pending','Accepted','Hold','Blacklist'];
  if (allowed.indexOf(newStatus) === -1)
    return { success: false, message: 'Status tidak valid: ' + newStatus, updated: 0, failed: 0 };
  if (!recruitmentIds || !Array.isArray(recruitmentIds) || recruitmentIds.length === 0)
    return { success: false, message: 'Tidak ada kandidat yang dipilih.', updated: 0, failed: 0 };

  var lock = LockService.getScriptLock();
  lock.waitLock(30000);
  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2)
      return { success: false, message: 'Sheet data kandidat tidak ditemukan.', updated: 0, failed: 0 };

    var updated = 0, failed = 0, failedIds = [];

    for (var i = 0; i < recruitmentIds.length; i++) {
      try {
        var found = findCandidateRow_(sheet, recruitmentIds[i]);
        if (!found) { failed++; failedIds.push(recruitmentIds[i]); continue; }
        var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];
        setCell_(sheet, found, STATUS_COLUMN_NAME, newStatus);
        touchUpdatedAt_(sheet, found);
        writeAuditLog_(recruitmentIds[i], 'Bulk Update Status', oldStatus, newStatus);
        updated++;
      } catch (e) {
        failed++; failedIds.push(recruitmentIds[i]);
      }
    }

    return {
      success: updated > 0,
      message: updated + ' kandidat berhasil diupdate. ' + failed + ' gagal.',
      updated: updated, failed: failed, failedIds: failedIds
    };
  } catch (err) {
    return { success: false, message: err.message, updated: 0, failed: recruitmentIds.length };
  } finally {
    lock.releaseLock();
  }
}

function bulkDeleteCandidates(recruitmentIds) {
  if (!recruitmentIds || !Array.isArray(recruitmentIds) || recruitmentIds.length === 0)
    return { success: false, message: 'Tidak ada kandidat yang dipilih.', deleted: 0, failed: 0 };

  var lock = LockService.getScriptLock();
  lock.waitLock(30000);
  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2)
      return { success: false, message: 'Sheet data kandidat tidak ditemukan.', deleted: 0, failed: 0 };

    var rowsToDelete = [], failed = 0, failedIds = [];

    for (var i = 0; i < recruitmentIds.length; i++) {
      var found = findCandidateRow_(sheet, recruitmentIds[i]);
      if (found) rowsToDelete.push({ rowNumber: found.rowNumber, id: recruitmentIds[i] });
      else { failed++; failedIds.push(recruitmentIds[i]); }
    }

    // Hapus dari bawah ke atas agar row number tidak bergeser
    rowsToDelete.sort(function(a, b) { return b.rowNumber - a.rowNumber; });

    var deleted = 0;
    for (var j = 0; j < rowsToDelete.length; j++) {
      try {
        sheet.deleteRow(rowsToDelete[j].rowNumber);
        writeAuditLog_(rowsToDelete[j].id, 'Bulk Deleted', '-', 'Deleted');
        deleted++;
      } catch (e) {
        failed++; failedIds.push(rowsToDelete[j].id);
      }
    }

    return {
      success: deleted > 0,
      message: deleted + ' kandidat berhasil dihapus. ' + failed + ' gagal.',
      deleted: deleted, failed: failed, failedIds: failedIds
    };
  } catch (err) {
    return { success: false, message: err.message, deleted: 0, failed: recruitmentIds.length };
  } finally {
    lock.releaseLock();
  }
}
