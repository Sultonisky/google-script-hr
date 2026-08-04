// ============================================================
// backend/Import.gs — BULK IMPORT CANDIDATES FROM CSV
// Validates rows and inserts into raw_kandidat sheet.
// ============================================================

/**
 * Validates and imports an array of candidate objects into raw_kandidat.
 * Each object should have keys matching (or mapped to) the column headers.
 * @param {Array<Object>} rows - Array of candidate data objects
 * @returns {Object} { success, imported, errors, warnings }
 */
function importCandidates(rows) {
  if (!rows || !Array.isArray(rows) || rows.length === 0) {
    return { success: false, imported: 0, errors: ['Tidak ada data untuk diimport.'], warnings: [] };
  }

  var lock = LockService.getScriptLock();
  var warnings = [];
  var errors = [];

  try {
    lock.waitLock(30000);
  } catch (e) {
    return { success: false, imported: 0, errors: ['Server sedang sibuk, coba lagi.'], warnings: [] };
  }

  try {
    var ss    = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(SHEET_NAME);
    if (!sheet) {
      return { success: false, imported: 0, errors: ['Sheet "' + SHEET_NAME + '" tidak ditemukan.'], warnings: [] };
    }

    var existingIds = [];
    var lastRow = sheet.getLastRow();
    if (lastRow > 1) {
      var idData = sheet.getRange(2, 1, lastRow - 1, 1).getValues();
      existingIds = idData.map(function(r) { return String(r[0]).trim().toUpperCase(); });
    }

    var imported = 0;
    var batchData = [];

    for (var i = 0; i < rows.length; i++) {
      var row = rows[i];
      var rowNum = i + 1;

      // Required fields validation
      if (!row.fullName || String(row.fullName).trim() === '') {
        errors.push('Baris ' + rowNum + ': Nama lengkap wajib diisi.');
        continue;
      }
      if (!row.phone || String(row.phone).trim() === '') {
        errors.push('Baris ' + rowNum + ': Nomor telepon wajib diisi.');
        continue;
      }

      // Generate recruitment ID
      var rid = generateRecruitmentId_(new Date());

      // Ensure uniqueness
      if (existingIds.indexOf(rid.toUpperCase()) !== -1) {
        warnings.push('Baris ' + rowNum + ': ID duplikat terdeteksi, ID baru dibuat.');
        rid = generateRecruitmentId_(new Date());
      }
      existingIds.push(rid.toUpperCase());

      // Prepare row data matching SHEET_HEADERS order (25 core columns)
      var now = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
      var rowData = [
        rid,                                                          // 1  Recruitment ID
        now,                                                          // 2  Created Date
        String(row.fullName || '').trim(),                            // 3  Full Name
        String(row.nik || '').trim(),                                 // 4  NIK
        String(row.dateOfBirth || '').trim(),                         // 5  Birth Date
        row.age ? Number(row.age) : '',                               // 6  Age
        String(row.gender || '').trim(),                              // 7  Gender
        String(row.maritalStatus || '').trim(),                       // 8  Marital Status
        String(row.email || '').trim(),                               // 9  Email
        "'" + String(row.phone || '').trim(),                         // 10 Phone (text)
        String(row.address || '').trim(),                             // 11 Address
        String(row.city || '').trim(),                                // 12 City
        String(row.positionApplied || '').trim(),                     // 13 Position Applied
        String(row.education || '').trim(),                           // 14 Education
        String(row.workExperience || '').trim(),                      // 15 Work Experience
        String(row.lastCompany || '').trim(),                         // 16 Last Company
        String(row.currentEmploymentStatus || '').trim(),             // 17 Current Employment Status
        String(row.availableToJoin || row.availableImmediately || '').trim(), // 18 Available to Join
        row.expectedSalary ? Number(row.expectedSalary) : '',         // 19 Expected Salary
        String(row.recruitmentSource || 'CSV Import').trim(),         // 20 Recruitment Source
        String(row.cvLink || '').trim(),                              // 21 CV Link
        'Pending',                                                    // 22 Status
        String(row.notes || '').trim(),                               // 23 HR Notes
        'system',                                                     // 24 Created By
        now                                                           // 25 Updated At
      ];

      batchData.push(rowData);
      imported++;
    }

    // Batch write all valid rows
    if (batchData.length > 0) {
      var startRow = sheet.getLastRow() + 1;
      sheet.getRange(startRow, 1, batchData.length, batchData[0].length).setValues(batchData);

      // Audit log for import
      for (var j = 0; j < batchData.length; j++) {
        writeAuditLog_(batchData[j][0], 'Import', 'CSV Import', '', batchData[j][RAW_KANDIDAT_COL['Full Name'] - 1]);
      }
    }

    return {
      success: imported > 0,
      imported: imported,
      errors: errors,
      warnings: warnings,
      message: imported + ' kandidat berhasil diimport.' + (errors.length > 0 ? ' ' + errors.length + ' baris gagal.' : '')
    };

  } catch (err) {
    return { success: false, imported: 0, errors: ['Kesalahan server: ' + err.message], warnings: [] };
  } finally {
    lock.releaseLock();
  }
}

/**
 * Validates a batch of CSV rows before import (dry-run).
 * Returns row-by-row validation results.
 * @param {Array<Object>} rows
 * @returns {Object} { valid, invalid, details }
 */
function validateImportRows(rows) {
  if (!rows || !Array.isArray(rows) || rows.length === 0) {
    return { valid: 0, invalid: 0, details: [] };
  }

  var details = [];
  var valid = 0;
  var invalid = 0;

  for (var i = 0; i < rows.length; i++) {
    var row = rows[i];
    var rowNum = i + 1;
    var rowErrors = [];

    if (!row.fullName || String(row.fullName).trim() === '') rowErrors.push('Nama wajib diisi');
    if (!row.phone || String(row.phone).trim() === '') rowErrors.push('Telepon wajib diisi');
    if (row.email && String(row.email).trim() !== '' && !isValidEmail_(String(row.email).trim())) {
      rowErrors.push('Format email tidak valid');
    }
    if (row.age && (isNaN(Number(row.age)) || Number(row.age) < 15 || Number(row.age) > 100)) {
      rowErrors.push('Usia harus antara 15-100');
    }

    if (rowErrors.length > 0) {
      invalid++;
      details.push({ row: rowNum, status: 'error', errors: rowErrors, data: row });
    } else {
      valid++;
      details.push({ row: rowNum, status: 'ok', data: row });
    }
  }

  return { valid: valid, invalid: invalid, details: details };
}

// ============================================================
// PRIVATE HELPERS
// ============================================================

function isValidEmail_(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}