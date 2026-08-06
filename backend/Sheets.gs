// ============================================================
// backend/Sheets.gs — MANAJEMEN SEMUA SHEET
// Uses Config.gs as single source of truth for all schemas.
// No hardcoded headers or column indexes anywhere.
// ============================================================

// ============= raw_kandidat =============
function getOrCreateSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(SHEET_HEADERS.concat(EXTRA_HEADERS));
    sheet
      .getRange(1, 1, 1, SHEET_HEADERS.length + EXTRA_HEADERS.length)
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

function ensureExtraHeaders_(sheet) {
  var lastCol = sheet.getLastColumn();
  var headerRow = sheet
    .getRange(1, 1, 1, lastCol)
    .getValues()[0]
    .map(function (h) {
      return String(h).trim();
    });
  var missing = EXTRA_HEADERS.filter(function (h) {
    return headerRow.indexOf(h) === -1;
  });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet
    .getRange(1, startCol, 1, missing.length)
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

// ============= Employee =============
function getOrCreateEmployeeSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(EMPLOYEE_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(EMPLOYEE_HEADERS);
    sheet
      .getRange(1, 1, 1, EMPLOYEE_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#005BAC")
      .setFontColor("#FFFFFF");
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, EMPLOYEE_HEADERS.length);
  } else {
    ensureEmployeeHeaders_(sheet);
  }
  return sheet;
}

function ensureEmployeeHeaders_(sheet) {
  var lastCol = sheet.getLastColumn();
  var headerRow = sheet
    .getRange(1, 1, 1, lastCol)
    .getValues()[0]
    .map(function (h) {
      return String(h).trim();
    });
  var missing = EMPLOYEE_HEADERS.filter(function (h) {
    return headerRow.indexOf(h) === -1;
  });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet
    .getRange(1, startCol, 1, missing.length)
    .setFontWeight("bold")
    .setBackground("#005BAC")
    .setFontColor("#FFFFFF");
}

// ============================================================
// HEADER AUTO-FIX
// Pastikan header sheet persis sesuai schema yang diharapkan.
// Dipanggil setiap kali sheet terbuka, sehingga selisih header
// (mis. sheet dibuat manual / versi lama) otomatis diperbaiki.
// ============================================================
function ensureSheetHeadersMatch_(sheet, expectedHeaders, legacyMap) {
  var lastCol = sheet.getLastColumn();
  var currentHeaders = [];
  if (lastCol > 0) {
    currentHeaders = sheet
      .getRange(1, 1, 1, lastCol)
      .getValues()[0]
      .map(function (h) {
        return String(h).trim();
      });
  }

  // APALAH: jika sheet kosong total tanpa baris, tulis header baru.
  if (sheet.getLastRow() === 0) {
    writeHeaderRow_(sheet, expectedHeaders);
    return;
  }

  // Headers sudah persis sama? Tidak perlu apa-apa.
  var headersMatch = false;
  if (currentHeaders.length >= expectedHeaders.length) {
    headersMatch = true;
    for (var i = 0; i < expectedHeaders.length; i++) {
      if (currentHeaders[i] !== expectedHeaders[i]) {
        headersMatch = false;
        break;
      }
    }
  }
  if (headersMatch) return;

  // Simpan baris data yang sudah ada (jika sheet berisi data lama).
  var existingRows = [];
  var existingHeaders = currentHeaders.slice();
  if (sheet.getLastRow() >= 2) {
    existingRows = sheet
      .getRange(2, 1, sheet.getLastRow() - 1, lastCol)
      .getValues();
  }

  // Kosongkan lalu tulis ulang header baru.
  sheet.clearContents();
  sheet.clearFormats();
  writeHeaderRow_(sheet, expectedHeaders);

  if (existingRows.length > 0) {
    // Petakan data lama ke kolom baru bila memungkinkan.
    // legacyMap: header lama -> header baru. Balikkan jadi header baru -> header lama.
    var newToOld = {};
    if (legacyMap) {
      for (var oldName in legacyMap) {
        var newName = legacyMap[oldName];
        if (newName) newToOld[newName] = oldName;
      }
    }

    var oldColOf = {};
    existingHeaders.forEach(function (h, i) {
      oldColOf[h] = i;
    });

    var migrated = [];
    for (var r = 0; r < existingRows.length; r++) {
      var newRow = new Array(expectedHeaders.length).fill("");
      var hasData = false;
      for (var c = 0; c < expectedHeaders.length; c++) {
        var target = expectedHeaders[c];
        var srcCol = oldColOf[target];
        if (srcCol === undefined && newToOld[target] !== undefined) {
          srcCol = oldColOf[newToOld[target]];
        }
        if (srcCol !== undefined && existingRows[r][srcCol] !== undefined) {
          var val = existingRows[r][srcCol];
          if (val !== "" && val !== null) hasData = true;
          newRow[c] = val;
        }
      }
      // Baris kosong sepenuhnya? Buang.
      if (hasData) migrated.push(newRow);
    }
    if (migrated.length > 0) {
      sheet
        .getRange(2, 1, migrated.length, expectedHeaders.length)
        .setValues(migrated);
    }
  }
}

function writeHeaderRow_(sheet, headers) {
  sheet
    .getRange(1, 1, 1, headers.length)
    .setValues([headers])
    .setFontWeight("bold")
    .setBackground("#005BAC")
    .setFontColor("#FFFFFF");
  sheet.setFrozenRows(1);
  sheet.autoResizeColumns(1, headers.length);
}

// ============= Offboarding =============
function getOrCreateOffboardingSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(OFFBOARDING_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(OFFBOARDING_SHEET_NAME);

  ensureSheetHeadersMatch_(sheet, OFFBOARDING_HEADERS, null);
  return sheet;
}

// ============================================================
// ONETIME FIX — jalankan sekali dari Apps Script editor untuk
// menulis ulang header sheet Offboarding dengan 16 kolom
// (OFFBOARDING_HEADERS). Aman dijalankan ulang.
// ============================================================
function fixOffboardingHeadersNow() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(OFFBOARDING_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(OFFBOARDING_SHEET_NAME);

  var before = {
    lastRow: sheet.getLastRow(),
    lastCol: sheet.getLastColumn(),
    headers:
      sheet.getLastColumn() > 0
        ? sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0].map(function (h) {
            return String(h).trim();
          })
        : [],
  };

  ensureSheetHeadersMatch_(sheet, OFFBOARDING_HEADERS, null);

  var afterHeaders = sheet
    .getRange(1, 1, 1, OFFBOARDING_HEADERS.length)
    .getValues()[0]
    .map(function (h) {
      return String(h).trim();
    });

  return {
    success: afterHeaders.join("|") === OFFBOARDING_HEADERS.join("|"),
    headerCount: afterHeaders.length,
    headers: afterHeaders,
    before: before,
    message:
      afterHeaders.join("|") === OFFBOARDING_HEADERS.join("|")
        ? "Header Offboarding berhasil ditulis ulang menjadi 16 kolom."
        : "Header Offboarding masih tidak cocok, periksa kembali.",
  };
}

function ensureOffboardingHeaders_(sheet) {
  var lastCol = sheet.getLastColumn();
  var headerRow = sheet
    .getRange(1, 1, 1, lastCol)
    .getValues()[0]
    .map(function (h) {
      return String(h).trim();
    });
  var missing = OFFBOARDING_HEADERS.filter(function (h) {
    return headerRow.indexOf(h) === -1;
  });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet
    .getRange(1, startCol, 1, missing.length)
    .setFontWeight("bold")
    .setBackground("#005BAC")
    .setFontColor("#FFFFFF");
}

// ============= Users =============
function getOrCreateUsersSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(USERS_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(USERS_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(USERS_HEADERS);
    sheet
      .getRange(1, 1, 1, USERS_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#005BAC")
      .setFontColor("#FFFFFF");
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, USERS_HEADERS.length);
  }
  return sheet;
}

// ============= kandidat_hold =============
function getOrCreateHoldSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(HOLD_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(HOLD_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(HOLD_HEADERS);
    sheet.getRange(1, 1, 1, HOLD_HEADERS.length)
      .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, HOLD_HEADERS.length);
  } else {
    ensureStatusSheetHeaders_(sheet, HOLD_HEADERS);
  }
  return sheet;
}

// ============= kandidat_accepted =============
function getOrCreateAcceptedSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ACCEPTED_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(ACCEPTED_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(ACCEPTED_HEADERS);
    sheet.getRange(1, 1, 1, ACCEPTED_HEADERS.length)
      .setFontWeight('bold').setBackground('#166534').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, ACCEPTED_HEADERS.length);
  } else {
    ensureStatusSheetHeaders_(sheet, ACCEPTED_HEADERS);
  }
  return sheet;
}

// ============= kandidat_blacklist =============
function getOrCreateBlacklistSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(BLACKLIST_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(BLACKLIST_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(BLACKLIST_HEADERS);
    sheet.getRange(1, 1, 1, BLACKLIST_HEADERS.length)
      .setFontWeight('bold').setBackground('#991b1b').setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, BLACKLIST_HEADERS.length);
  } else {
    ensureStatusSheetHeaders_(sheet, BLACKLIST_HEADERS);
  }
  return sheet;
}

// Helper: pastikan extra headers ada di sheet status
function ensureStatusSheetHeaders_(sheet, expectedHeaders) {
  var lastCol = sheet.getLastColumn();
  if (lastCol === 0) return;
  var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0]
    .map(function(h) { return String(h).trim(); });
  var missing = expectedHeaders.filter(function(h) { return headerRow.indexOf(h) === -1; });
  if (missing.length === 0) return;
  var startCol = lastCol + 1;
  sheet.getRange(1, startCol, 1, missing.length).setValues([missing]);
  sheet.getRange(1, startCol, 1, missing.length)
    .setFontWeight('bold').setBackground('#005BAC').setFontColor('#FFFFFF');
}

// ============= Setup All =============
function setupSpreadsheet() {
  getOrCreateSheet_();
  getOrCreateEmployeeSheet_();
  getOrCreateHoldSheet_();
  getOrCreateAcceptedSheet_();
  getOrCreateBlacklistSheet_();
  getOrCreateOffboardingSheet_();
  getOrCreateAuditLogSheet_();
  getOrCreateUsersSheet_();
}
