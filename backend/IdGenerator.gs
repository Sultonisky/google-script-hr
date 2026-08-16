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

// OFB-YYYYMMDD-XXXX (counter reset harian).
// NOTE: caller (updateEmployee) sudah memegang LockService,
// jadi fungsi ini TIDAK mengambil lock lagi (nested lock bisa deadlock).
function generateOffboardingId_(timestamp) {
  var datePart = Utilities.formatDate(timestamp, "GMT+7", "yyyyMMdd");
  var props = PropertiesService.getScriptProperties();
  var key = "OFB_COUNTER_" + datePart;
  var counter = Number(props.getProperty(key) || "0") + 1;
  props.setProperty(key, String(counter));
  return "OFB-" + datePart + "-" + ("0000" + counter).slice(-4);
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
