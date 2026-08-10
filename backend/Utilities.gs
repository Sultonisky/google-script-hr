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

// Format tanggal dari Date object atau string ke dd/MM/yyyy (atau
// dd/MM/yyyy HH:mm bila ada komponen jam). Menerima Date object, string
// yyyy-MM-dd / yyyy-MM-dd HH:mm:ss, maupun string hasil Date.toString()
// (mis. "Tue Oct 07 2025 00:00:00 GMT+0700 (Waktu Indonesia Barat)").
function fmtDateStr_(val) {
  if (val instanceof Date) {
    var hasTime = val.getHours() !== 0 || val.getMinutes() !== 0 || val.getSeconds() !== 0;
    return Utilities.formatDate(val, 'GMT+7', hasTime ? 'dd/MM/yyyy HH:mm' : 'dd/MM/yyyy');
  }
  if (!val || String(val).trim() === '') return '-';
  var s = String(val).trim();
  // Sudah dalam format dd/MM/yyyy... — kembalikan apa adanya
  if (/^\d{2}\/\d{2}\/\d{4}/.test(s)) return s;
  // Format yyyy-MM-dd HH:mm:ss atau yyyy-MM-dd
  var m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{2}):(\d{2}))?/);
  if (!m) {
    // Fallback: string hasil Date.toString() dari sel spreadsheet
    var t = new Date(s);
    if (!isNaN(t.getTime())) {
      var hasTime2 = t.getHours() !== 0 || t.getMinutes() !== 0 || t.getSeconds() !== 0;
      return Utilities.formatDate(t, 'GMT+7', hasTime2 ? 'dd/MM/yyyy HH:mm' : 'dd/MM/yyyy');
    }
    return s;
  }
  var base = m[3] + '/' + m[2] + '/' + m[1];
  return m[4] ? base + ' ' + m[4] + ':' + m[5] : base;
}

// ============================================================
// Fetch gambar eksternal & return sebagai data URL base64
// Digunakan untuk bypass CSP sandbox di HTML Service.
// Contoh: getHeroImageBase64() -> "data:image/jpeg;base64,/9j/..."
// ============================================================
function getHeroImageBase64() {
  try {
    var url = 'https://media.giphy.com/avatars/mitoofficial/GtRq8wJjQCjJ.jpg';
    var response = UrlFetchApp.fetch(url, { muteHttpExceptions: true });
    if (response.getResponseCode() !== 200) return '';
    var blob = response.getBlob();
    var mimeType = blob.getContentType() || 'image/jpeg';
    var base64 = Utilities.base64Encode(blob.getBytes());
    return 'data:' + mimeType + ';base64,' + base64;
  } catch (e) {
    Logger.log('getHeroImageBase64 error: ' + e.message);
    return '';
  }
}
