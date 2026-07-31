// ============================================================
// backend/Utilities.gs — HELPER FUNGSI UMUM
// ============================================================

// Cari baris kandidat berdasarkan Recruitment ID
function findCandidateRow_(sheet, recruitmentId) {
  var lastRow  = sheet.getLastRow();
  var lastCol  = sheet.getLastColumn();
  var values   = sheet.getRange(1, 1, lastRow, lastCol).getValues();
  var headers  = values[0];

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

// Set nilai satu sel berdasarkan nama header
function setCell_(sheet, found, headerName, value) {
  var idx = found.colIndex[headerName];
  if (idx === undefined) return;
  sheet.getRange(found.rowNumber, idx + 1).setValue(value);
}

// Update kolom Updated At ke timestamp sekarang
function touchUpdatedAt_(sheet, found) {
  var idx = found.colIndex['Updated At'];
  if (idx === undefined) return;
  sheet.getRange(found.rowNumber, idx + 1)
       .setValue(Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss'));
}

// Format tanggal dari nilai sel spreadsheet
function fmtDate_(val, pattern) {
  return val instanceof Date
    ? Utilities.formatDate(val, 'GMT+7', pattern)
    : String(val || '');
}
