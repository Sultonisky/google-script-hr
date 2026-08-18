// ============================================================
// backend/Sheets.gs — MANAJEMEN SEMUA SHEET
// Uses Config.gs as single source of truth for all schemas.
// No hardcoded headers or column indexes anywhere.
// ============================================================

// ============= data_kandidat =============
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
    ensureSheetHeadersMatch_(sheet, EMPLOYEE_HEADERS, null);
  }
  return sheet;
}

function fixEmployeeHeadersNow() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);
  if (!sheet) {
    sheet = ss.insertSheet(EMPLOYEE_SHEET_NAME);
  }

  var before = {
    lastRow: sheet.getLastRow(),
    lastCol: sheet.getLastColumn(),
    headers:
      sheet.getLastColumn() > 0
        ? sheet
            .getRange(1, 1, 1, sheet.getLastColumn())
            .getValues()[0]
            .map(function (h) {
              return String(h).trim();
            })
        : [],
  };

  ensureSheetHeadersMatch_(sheet, EMPLOYEE_HEADERS, null);

  var afterHeaders = sheet
    .getRange(1, 1, 1, EMPLOYEE_HEADERS.length)
    .getValues()[0]
    .map(function (h) {
      return String(h).trim();
    });

  var match = afterHeaders.join("|") === EMPLOYEE_HEADERS.join("|");
  return {
    success: match,
    headerCount: afterHeaders.length,
    headers: afterHeaders,
    before: before,
    message: match
      ? "Header Employee berhasil diselaraskan menjadi " +
        EMPLOYEE_HEADERS.length +
        " kolom."
      : "Header Employee masih tidak cocok, periksa kembali.",
  };
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

  if (sheet.getLastRow() === 0) {
    writeHeaderRow_(sheet, expectedHeaders);
    return;
  }

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

  var existingRows = [];
  var existingHeaders = currentHeaders.slice();
  if (sheet.getLastRow() >= 2) {
    existingRows = sheet
      .getRange(2, 1, sheet.getLastRow() - 1, lastCol)
      .getValues();
  }

  sheet.clearContents();
  sheet.clearFormats();
  writeHeaderRow_(sheet, expectedHeaders);

  if (existingRows.length > 0) {
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
    sheet
      .getRange(1, 1, 1, HOLD_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#005BAC")
      .setFontColor("#FFFFFF");
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
    sheet
      .getRange(1, 1, 1, ACCEPTED_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#166534")
      .setFontColor("#FFFFFF");
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
    sheet
      .getRange(1, 1, 1, BLACKLIST_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#991b1b")
      .setFontColor("#FFFFFF");
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
  var headerRow = sheet
    .getRange(1, 1, 1, lastCol)
    .getValues()[0]
    .map(function (h) {
      return String(h).trim();
    });
  var missing = expectedHeaders.filter(function (h) {
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

// ============= Setup All =============
function setupSpreadsheet() {
  getOrCreateSheet_();
  getOrCreateEmployeeSheet_();
  getOrCreateHoldSheet_();
  getOrCreateAcceptedSheet_();
  getOrCreateBlacklistSheet_();
  getOrCreateAuditLogSheet_();
  getOrCreateUsersSheet_();
  getOrCreateProbationSheet_();
}

// ============= kandidat_probation =============
function getOrCreateProbationSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(PROBATION_SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(PROBATION_SHEET_NAME);

  if (sheet.getLastRow() === 0) {
    sheet.appendRow(PROBATION_HEADERS);
    sheet
      .getRange(1, 1, 1, PROBATION_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#0e7490")
      .setFontColor("#FFFFFF");
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, PROBATION_HEADERS.length);
  } else {
    ensureStatusSheetHeaders_(sheet, PROBATION_HEADERS);
  }
  return sheet;
}


// ============================================================
// UTILITY: Hard-reset header kandidat_accepted ke ACCEPTED_HEADERS
// Jalankan SEKALI: resetAcceptedSheetHeaders()
// Data di baris 2+ TIDAK disentuh.
// Kolom lama yang tidak ada di ACCEPTED_HEADERS diberi header kosong
// dan background abu-abu sebagai penanda kolom orphan.
// ============================================================
function resetAcceptedSheetHeaders() {
  var ss    = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ACCEPTED_SHEET_NAME);
  if (!sheet) {
    Browser.msgBox('Sheet "' + ACCEPTED_SHEET_NAME + '" tidak ditemukan.');
    return;
  }

  var actualCols   = sheet.getLastColumn();
  var expectedCols = ACCEPTED_HEADERS.length;
  var totalCols    = Math.max(actualCols, expectedCols);

  // Buat array header baru: isi dengan ACCEPTED_HEADERS, sisanya kosong
  var newHeaders = [];
  for (var i = 0; i < totalCols; i++) {
    newHeaders.push(i < expectedCols ? ACCEPTED_HEADERS[i] : '');
  }

  // Tulis baris header
  sheet.getRange(1, 1, 1, totalCols).setValues([newHeaders]);

  // Styling: kolom aktif (1..expectedCols)
  sheet.getRange(1, 1, 1, expectedCols)
    .setFontWeight('bold')
    .setBackground('#166534')
    .setFontColor('#FFFFFF');

  // Styling: kolom orphan (expectedCols+1..totalCols)
  if (totalCols > expectedCols) {
    sheet.getRange(1, expectedCols + 1, 1, totalCols - expectedCols)
      .setFontWeight('normal')
      .setBackground('#cccccc')
      .setFontColor('#666666');
  }

  sheet.setFrozenRows(1);
  sheet.autoResizeColumns(1, expectedCols);

  var msg = 'Header "' + ACCEPTED_SHEET_NAME + '" berhasil direset.\n' +
    expectedCols + ' kolom aktif.\n' +
    (totalCols > expectedCols
      ? (totalCols - expectedCols) + ' kolom lama diberi header kosong (abu-abu).'
      : 'Tidak ada kolom orphan.');
  Browser.msgBox(msg);
}

// ============================================================
// UTILITY: Reset semua header sheet status sekaligus
// (kandidat_accepted, kandidat_hold, kandidat_blacklist, kandidat_probation)
// Jalankan: resetAllStatusSheetHeaders()
// ============================================================
function resetAllStatusSheetHeaders() {
  resetAcceptedSheetHeaders();

  var sheets = [
    { name: HOLD_SHEET_NAME,      headers: HOLD_HEADERS,      color: '#005BAC' },
    { name: BLACKLIST_SHEET_NAME, headers: BLACKLIST_HEADERS,  color: '#991b1b' },
    { name: PROBATION_SHEET_NAME, headers: PROBATION_HEADERS,  color: '#0e7490' },
  ];

  var ss = SpreadsheetApp.getActiveSpreadsheet();
  sheets.forEach(function(cfg) {
    var sheet = ss.getSheetByName(cfg.name);
    if (!sheet) return;

    var actualCols   = sheet.getLastColumn();
    var expectedCols = cfg.headers.length;
    var totalCols    = Math.max(actualCols, expectedCols);

    var newHeaders = [];
    for (var i = 0; i < totalCols; i++) {
      newHeaders.push(i < expectedCols ? cfg.headers[i] : '');
    }

    sheet.getRange(1, 1, 1, totalCols).setValues([newHeaders]);
    sheet.getRange(1, 1, 1, expectedCols)
      .setFontWeight('bold')
      .setBackground(cfg.color)
      .setFontColor('#FFFFFF');

    if (totalCols > expectedCols) {
      sheet.getRange(1, expectedCols + 1, 1, totalCols - expectedCols)
        .setFontWeight('normal')
        .setBackground('#cccccc')
        .setFontColor('#666666');
    }

    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, expectedCols);
  });

  Browser.msgBox('Semua header sheet status berhasil direset.');
}
