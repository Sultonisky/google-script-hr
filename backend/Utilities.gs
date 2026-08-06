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

// Format string tanggal (yyyy-MM-dd atau yyyy-MM-dd HH:mm:ss) ke dd/MM/yyyy HH:mm
// Dipakai untuk kolom seperti joinDate, contractStart, contractEnd yang
// disimpan sebagai string bukan Date object.
function fmtDateStr_(str) {
  if (!str || String(str).trim() === '') return '-';
  var s = String(str).trim();
  // Sudah dalam format dd/MM/yyyy... — kembalikan apa adanya
  if (/^\d{2}\/\d{2}\/\d{4}/.test(s)) return s;
  // Format yyyy-MM-dd HH:mm:ss atau yyyy-MM-dd
  var m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{2}):(\d{2}))?/);
  if (!m) return s;
  var base = m[3] + '/' + m[2] + '/' + m[1];
  return m[4] ? base + ' ' + m[4] + ':' + m[5] : base;
}
