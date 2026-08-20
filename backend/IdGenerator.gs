// ============================================================
// backend/IdGenerator.gs — GENERATOR ID (Recruitment & Employee)
// ============================================================

// REC-YYYYMMDD-000001 (counter reset harian)
function generateRecruitmentId_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "REC_COUNTER_" + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var counter = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(counter));
    return "REC-" + datePart + "-" + ("000000" + counter).slice(-6);
  } finally {
    lock.releaseLock();
  }
}

// YYYYMMDDNN (counter reset harian, 2 digit sequence)
function generateEmployeeId_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "EMP_COUNTER_" + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var counter = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(counter));
    return datePart + ("00" + counter).slice(-2);
  } finally {
    lock.releaseLock();
  }
}

// EVAL-YYYYMMDD-XXXX (counter reset harian, dipanggil dalam lock caller)
function generateEvalId_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "EVAL_COUNTER_" + datePart;
  var counter = Number(props.getProperty(key) || "0") + 1;
  props.setProperty(key, String(counter));
  return "EVAL-" + datePart + "-" + ("0000" + counter).slice(-4);
}

// ============================================================
// PUBLIC WRAPPERS (dipanggil dari frontend via google.script.run)
// ============================================================
function generateEmployeeId() {
  var lock = LockService.getScriptLock();
  lock.waitLock(5000);
  try {
    var id = generateEmployeeId_(new Date());
    return { success: true, employeeId: id };
  } catch (e) {
    return { success: false, message: e.toString() };
  } finally {
    lock.releaseLock();
  }
}

// Nomor urut surat PKWT (counter harian/tahunan — global untuk semua company)
function generatePkwtNumber_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "PKWT_COUNTER_" + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var counter = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(counter));
    return ("000" + counter).slice(-3);
  } finally {
    lock.releaseLock();
  }
}

// Konversi bulan (0-11 atau 1-12) ke angka romawi — untuk format PKWT
function toRomanMonth_(month) {
  var romans = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
  var m = Number(month);
  if (m < 1 || m > 12) return "";
  return romans[m - 1];
}

// Public wrapper untuk generate surat number (BPJS, SK, dll)
function generateSuratNumber(branchCode) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var now = new Date();
    var seq = generateSuratNumber_(now);
    var romanMonth = toRomanMonth_(now.getMonth() + 1);
    var year = now.getFullYear();
    var code = String(branchCode || 'MITO').replace(/[^a-zA-Z0-9]/g, '').substring(0, 6).toUpperCase();
    return { success: true, number: seq + '/HRD-SKK/' + code + '/' + romanMonth + '/' + year };
  } catch (e) {
    return { success: false, message: e.toString() };
  } finally {
    lock.releaseLock();
  }
}

// Public wrapper untuk generate nomor SK Pengangkatan (HRD-PK)
function generatePkNumber(branchCode) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var now = new Date();
    var seq = generateSuratNumber_(now);
    var romanMonth = toRomanMonth_(now.getMonth() + 1);
    var year = now.getFullYear();
    var code = String(branchCode || 'MITO').replace(/[^a-zA-Z0-9]/g, '').substring(0, 6).toUpperCase();
    return { success: true, number: seq + '/HRD-PK/' + code + '/' + romanMonth + '/' + year };
  } catch (e) {
    return { success: false, message: e.toString() };
  } finally {
    lock.releaseLock();
  }
}

// Generate batch Employee IDs in a single lock acquisition
function generateEmployeeIdBatch(count) {
  if (!count || count <= 0) return [];
  var now = new Date();
  var datePart = Utilities.formatDate(now, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "EMP_COUNTER_" + datePart;
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var start = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(start + count - 1));
    var ids = [];
    for (var i = 0; i < count; i++) {
      ids.push(datePart + ("00" + (start + i)).slice(-2));
    }
    return ids;
  } finally {
    lock.releaseLock();
  }
}

// Surat counter per month (global untuk semua company) — untuk SK Offboarding & Surat BPJS
function generateSuratNumber_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMM");
  var props = PropertiesService.getScriptProperties();
  var key = "SURAT_COUNTER_" + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var counter = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(counter));
    return ("000" + counter).slice(-3);
  } finally {
    lock.releaseLock();
  }
}

// SK OFF counter per month (global untuk semua company)
function generateSkOffNumber_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMM");
  var props = PropertiesService.getScriptProperties();
  var key = "SK_OFF_COUNTER_" + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var counter = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(counter));
    return ("000" + counter).slice(-3);
  } finally {
    lock.releaseLock();
  }
}

// PROB-YYYYMMDD-XXXX (counter reset harian)
function generateProbationId_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "PROB_COUNTER_" + datePart;

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var counter = Number(props.getProperty(key) || "0") + 1;
    props.setProperty(key, String(counter));
    return "PROB-" + datePart + "-" + ("0000" + counter).slice(-4);
  } finally {
    lock.releaseLock();
  }
}
