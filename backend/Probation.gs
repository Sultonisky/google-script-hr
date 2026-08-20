// ============================================================
// backend/Probation.gs — PROBATION MANAGEMENT
// Handles probation records, evaluations, and SK status updates.
// Uses sheet: kandidat_probation (PROBATION_SHEET_NAME)
// ============================================================

function normEmployeeId_(v) {
  return String(v || "").replace(/^'/, "").trim();
}

function isProbationEmployeeStatus_(status) {
  return String(status || "").trim().toLowerCase() === "probation";
}

function isActiveProbationSheetStatus_(status) {
  var s = String(status || "").trim();
  return s === "Probation" || s === "Probation Extended";
}

// ============= CREATE PROBATION RECORD =============
// options.skipLock — dipakai saat dipanggil dari processOnboardingProbation (lock sudah dipegang)
function createProbationRecord(recruitmentId, extraData, options) {
  var lock = LockService.getScriptLock();
  var lockAcquiredHere = false;
  try {
    options = options || {};
    if (!options.skipLock) {
      lock.waitLock(15000);
      lockAcquiredHere = true;
    }

    extraData = extraData || {};
    recruitmentId = String(recruitmentId || "").trim();
    if (!recruitmentId) {
      return { success: false, message: "Recruitment ID wajib diisi." };
    }

    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var probationId = generateProbationId_(now);

    var acceptedSheet = getOrCreateAcceptedSheet_();
    ensureStatusSheetHeaders_(acceptedSheet, ACCEPTED_HEADERS);
    var acceptedData = acceptedSheet.getDataRange().getValues();
    if (acceptedData.length < 2) {
      return { success: false, message: "Sheet kandidat_accepted kosong." };
    }

    var headers = acceptedData[0];
    var candidateRow = null;
    var colIdx = {};
    headers.forEach(function (h, idx) { colIdx[String(h).trim()] = idx; });

    var ridIdx = colIdx["Recruitment ID"];
    if (ridIdx !== undefined) {
      for (var i = 1; i < acceptedData.length; i++) {
        if (String(acceptedData[i][ridIdx] || "").trim() === recruitmentId) {
          candidateRow = acceptedData[i];
          break;
        }
      }
    }

    var empId = normEmployeeId_(
      extraData.employeeId || (candidateRow ? candidateRow[colIdx["Employee ID"]] : "") || recruitmentId
    );
    if (!empId) {
      return { success: false, message: "Employee ID tidak ditemukan untuk onboarding probation." };
    }

    var joinDate = extraData.joinDate || extraData.contractStart ||
      (candidateRow && colIdx["Offering Join Date"] !== undefined ? String(candidateRow[colIdx["Offering Join Date"]] || "") : "");
    var contractStart = extraData.contractStart || joinDate;
    var contractEnd = extraData.contractEnd || "";
    var contractNumber = extraData.contractNumber || "";
    var contractDuration = extraData.contractDuration || "";
    var onboardingBy =
      extraData.onboardingBy ||
      Session.getActiveUser().getEmail() ||
      "HR Dashboard";

    // Lengkapi kontrak dari sheet Employee
    var empSheet = getOrCreateEmployeeSheet_();
    if (empSheet && empSheet.getLastRow() >= 2) {
      var empData = empSheet.getDataRange().getValues();
      var empHdr = empData[0];
      var empCI = {};
      empHdr.forEach(function (h, i) { empCI[String(h).trim()] = i; });
      var empIdIdx = empCI["Employee ID"];
      if (empIdIdx !== undefined) {
        for (var e = 1; e < empData.length; e++) {
          if (normEmployeeId_(empData[e][empIdIdx]) === empId) {
            var empRec = empData[e];
            if (!contractEnd && empCI["End Date (Contract)"] !== undefined) {
              contractEnd = String(empRec[empCI["End Date (Contract)"]] || "");
            }
            if (!joinDate && empCI["Join Date"] !== undefined) {
              joinDate = String(empRec[empCI["Join Date"]] || "");
            }
            if (!contractStart) contractStart = joinDate;
            break;
          }
        }
      }
    }

    var sheet = getOrCreateProbationSheet_();
    var existingRow = -1;
    if (sheet.getLastRow() >= 2) {
      var probScan = sheet.getDataRange().getValues();
      for (var pr = 1; pr < probScan.length; pr++) {
        var prRid = String(probScan[pr][PROBATION_COL["Recruitment ID"] - 1] || "").trim();
        var prSt = String(probScan[pr][PROBATION_COL["Status"] - 1] || "").trim();
        if (prRid === recruitmentId && isActiveProbationSheetStatus_(prSt)) {
          existingRow = pr + 1;
          var existingPid = String(probScan[pr][PROBATION_COL["Probation ID"] - 1] || "").trim();
          if (existingPid) probationId = existingPid;
          break;
        }
      }
    }

    if (existingRow !== -1) {
      sheet.getRange(existingRow, PROBATION_COL["Employee ID"]).setValue(empId);
      sheet.getRange(existingRow, PROBATION_COL["Contract Number"]).setValue(contractNumber);
      sheet.getRange(existingRow, PROBATION_COL["Contract Duration"]).setValue(contractDuration);
      sheet.getRange(existingRow, PROBATION_COL["Contract Start"]).setValue(contractStart);
      sheet.getRange(existingRow, PROBATION_COL["Contract End"]).setValue(contractEnd);
      sheet.getRange(existingRow, PROBATION_COL["Join Date"]).setValue(joinDate);
      sheet.getRange(existingRow, PROBATION_COL["Status"]).setValue("Probation");
      sheet.getRange(existingRow, PROBATION_COL["Onboarding Date"]).setValue(nowStr);
      sheet.getRange(existingRow, PROBATION_COL["Onboarding By"]).setValue(onboardingBy);
      sheet.getRange(existingRow, PROBATION_COL["Updated At"]).setValue(nowStr);
    } else {
      var newRow = new Array(PROBATION_HEADERS.length).fill("");
      newRow[PROBATION_COL["Probation ID"] - 1] = probationId;
      newRow[PROBATION_COL["Employee ID"] - 1] = empId;
      newRow[PROBATION_COL["Recruitment ID"] - 1] = recruitmentId;
      newRow[PROBATION_COL["Contract Number"] - 1] = contractNumber;
      newRow[PROBATION_COL["Contract Duration"] - 1] = contractDuration;
      newRow[PROBATION_COL["Contract Start"] - 1] = contractStart;
      newRow[PROBATION_COL["Contract End"] - 1] = contractEnd;
      newRow[PROBATION_COL["Join Date"] - 1] = joinDate;
      newRow[PROBATION_COL["Status"] - 1] = "Probation";
      newRow[PROBATION_COL["Onboarding Date"] - 1] = nowStr;
      newRow[PROBATION_COL["Onboarding By"] - 1] = onboardingBy;
      newRow[PROBATION_COL["SK Status"] - 1] = "Pending";
      newRow[PROBATION_COL["Created At"] - 1] = nowStr;
      newRow[PROBATION_COL["Updated At"] - 1] = nowStr;
      sheet.appendRow(newRow);
    }

    writeAuditLog_(recruitmentId, "Create Probation", "Status", "-", "Probation");

    return {
      success: true,
      probationId: probationId,
      employeeId: empId,
      message: "Record probation berhasil dibuat.",
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    if (lockAcquiredHere) lock.releaseLock();
  }
}
// ============= SUBMIT PROBATION EVALUATION =============
function submitProbationEvaluation(data) {
  try {
    if (!data.probationId) return { success: false, message: "Probation ID wajib diisi." };
    if (!data.keputusan)   return { success: false, message: "Keputusan wajib diisi." };

    var lock = LockService.getScriptLock();
    lock.waitLock(15000);

    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var evalId = generateEvalId_(now);

    var sheet   = getOrCreateProbationSheet_();
    var allData = sheet.getDataRange().getValues();
    var rowIndex = -1;
    for (var i = 1; i < allData.length; i++) {
      if (String(allData[i][PROBATION_COL["Probation ID"] - 1]).trim() === data.probationId) {
        rowIndex = i;
        break;
      }
    }
    if (rowIndex === -1) return { success: false, message: "Probation ID tidak ditemukan." };

    var scorePerf  = Number(data.skorkinerja)       || 0;
    var scoreDisc  = Number(data.skorkedisiplinan)   || 0;
    var scoreComm  = Number(data.skorkomunikasi)     || 0;
    var scoreInit  = Number(data.skorinisiatif)      || 0;
    var scoreTeam  = Number(data.skorteamwork)       || 0;
    var avgScore   = ((scorePerf + scoreDisc + scoreComm + scoreInit + scoreTeam) / 5).toFixed(2);

    var isExtend = data.keputusan.indexOf("Perpanjang") !== -1 || data.keputusan.indexOf("Tidak Lulus") !== -1;
    var isPass   = data.keputusan.indexOf("Lulus") !== -1 || data.keputusan.indexOf("Tetap") !== -1;

    sheet.getRange(rowIndex + 1, PROBATION_COL["Eval ID"]).setValue(evalId);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Eval Date"]).setValue(nowStr);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Score Performance"]).setValue(scorePerf);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Score Discipline"]).setValue(scoreDisc);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Score Communication"]).setValue(scoreComm);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Score Initiative"]).setValue(scoreInit);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Score Teamwork"]).setValue(scoreTeam);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Average Score"]).setValue(avgScore);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Decision"]).setValue(data.keputusan);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Evaluator Notes"]).setValue(data.catatanEvaluator || "");
    sheet.getRange(rowIndex + 1, PROBATION_COL["Evaluator"]).setValue(Session.getActiveUser().getEmail());
    sheet.getRange(rowIndex + 1, PROBATION_COL["Updated At"]).setValue(nowStr);

    if (isExtend) {
      sheet.getRange(rowIndex + 1, PROBATION_COL["Extension Duration"]).setValue(data.durasiPerpanjang || "");
      sheet.getRange(rowIndex + 1, PROBATION_COL["New Contract Start"]).setValue(data.kontrakBaruStart || "");
      sheet.getRange(rowIndex + 1, PROBATION_COL["New Contract End"]).setValue(data.kontrakBaruEnd || "");
      sheet.getRange(rowIndex + 1, PROBATION_COL["Status"]).setValue("Probation Extended");
    } else if (isPass) {
      sheet.getRange(rowIndex + 1, PROBATION_COL["Status"]).setValue("Permanent");
      sheet.getRange(rowIndex + 1, PROBATION_COL["SK Status"]).setValue("SK Diterbitkan");
    }

    writeAuditLog_(data.probationId, "Submit Evaluation", "Decision", "-", data.keputusan);

    return { success: true, evalId: evalId, nilaiRataRata: avgScore, message: "Evaluasi probation berhasil disimpan." };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
// ============= GET PROBATION LIST =============
// Sumber utama: kandidat_probation (workflow onboarding).
// Join ke Employee untuk data pekerjaan. Tambahkan Employee Probation tanpa record workflow (legacy).
function getProbationList() {
  try {
    function eCell(row, name, empCI) {
      var idx = empCI[name];
      if (idx !== undefined) return row[idx];
      var fallback = {
        "Status Employee": ["Employment Status", "Status"],
        "Employment Status": ["Status Employee", "Status"],
        "Status": ["Status Employee", "Employment Status"],
        "Position": ["Job Position (Location)", "Job Position"],
        "Branch": ["Branch Name"],
        "Email": ["Personal Email", "Working Email"],
        "Phone": ["Mobile Phone"],
        "NIK": ["NIK - NPWP 16 digit"],
        "Contract Start": ["Start Date (Contract)"],
        "Contract End": ["End Date (Contract)"],
        "Recruitment ID": ["Recruitment ID (System Link)"],
        "Company Entity": ["Branch Name"],
        "Contract Duration": ["Contract Duration"],
        "Contract Number": ["Contract Number"],
      };
      if (fallback[name]) {
        for (var f = 0; f < fallback[name].length; f++) {
          var j = empCI[fallback[name][f]];
          if (j !== undefined) return row[j];
        }
      }
      return "";
    }

    function buildEmpItem(row, empCI) {
      var st = String(eCell(row, "Status", empCI) || "").trim();
      var est = String(eCell(row, "Employment Status", empCI) || "").trim();
      var semp = String(eCell(row, "Status Employee", empCI) || "").trim();
      return {
        employeeId: normEmployeeId_(eCell(row, "Employee ID", empCI)),
        recruitmentId: String(eCell(row, "Recruitment ID", empCI) || ""),
        fullName: String(eCell(row, "Full Name", empCI) || ""),
        nik: String(eCell(row, "NIK - NPWP 16 digit", empCI) || eCell(row, "NIK", empCI) || "").replace(/^'/, ""),
        position: String(eCell(row, "Job Position (Location)", empCI) || eCell(row, "Job Position", empCI) || eCell(row, "Position", empCI) || ""),
        department: String(eCell(row, "Department", empCI) || ""),
        division: String(eCell(row, "Division", empCI) || ""),
        branch: String(eCell(row, "Branch Name", empCI) || eCell(row, "Branch", empCI) || ""),
        companyEntity: String(eCell(row, "Branch Name", empCI) || eCell(row, "Company Entity", empCI) || ""),
        email: String(eCell(row, "Personal Email", empCI) || eCell(row, "Email", empCI) || ""),
        phone: String(eCell(row, "Mobile Phone", empCI) || eCell(row, "Phone", empCI) || ""),
        joinDate: String(eCell(row, "Join Date", empCI) || ""),
        contractStart: String(eCell(row, "Join Date", empCI) || eCell(row, "Start Date (Contract)", empCI) || eCell(row, "Contract Start", empCI) || ""),
        contractEnd: String(eCell(row, "End Date (Contract)", empCI) || eCell(row, "Contract End", empCI) || ""),
        contractDuration: String(eCell(row, "Contract Duration", empCI) || ""),
        contractNumber: String(eCell(row, "Contract Number", empCI) || ""),
        status: semp || st || est || "Probation",
        statusEmployee: semp || st || est || "Probation",
        hrNotes: String(eCell(row, "HR Notes", empCI) || ""),
        createdAt: String(eCell(row, "Created At", empCI) || ""),
        updatedAt: String(eCell(row, "Updated At", empCI) || ""),
        probationId: "",
        onboardingDate: "",
        onboardingBy: "",
        evalId: "",
        evalDate: "",
        scorePerformance: "",
        scoreDiscipline: "",
        scoreCommunication: "",
        scoreInitiative: "",
        scoreTeamwork: "",
        averageScore: "",
        decision: "",
        extensionDuration: "",
        newContractStart: "",
        newContractEnd: "",
        evaluatorNotes: "",
        evaluator: "",
        skStatus: "",
        notes: "",
        lastEvalDate: "",
        lastAvgScore: "",
        lastKeputusan: "",
        lastEvaluator: "",
        lastStatusSK: "",
        fromOnboardingWorkflow: false,
      };
    }

    function mergeProbRow(emp, pr) {
      emp.probationId = String(pr[PROBATION_COL["Probation ID"] - 1] || "");
      emp.recruitmentId = String(pr[PROBATION_COL["Recruitment ID"] - 1] || "") || emp.recruitmentId;
      emp.onboardingDate = String(pr[PROBATION_COL["Onboarding Date"] - 1] || "");
      emp.onboardingBy = String(pr[PROBATION_COL["Onboarding By"] - 1] || "");
      emp.contractNumber = String(pr[PROBATION_COL["Contract Number"] - 1] || "") || emp.contractNumber;
      emp.contractDuration = String(pr[PROBATION_COL["Contract Duration"] - 1] || "") || emp.contractDuration;
      emp.contractStart = String(pr[PROBATION_COL["Contract Start"] - 1] || "") || emp.contractStart;
      emp.contractEnd = String(pr[PROBATION_COL["Contract End"] - 1] || "") || emp.contractEnd;
      emp.joinDate = String(pr[PROBATION_COL["Join Date"] - 1] || "") || emp.joinDate;
      emp.notes = String(pr[PROBATION_COL["Notes"] - 1] || "");
      emp.fromOnboardingWorkflow = true;

      var evalDate = pr[PROBATION_COL["Eval Date"] - 1];
      if (evalDate) {
        emp.evalId = String(pr[PROBATION_COL["Eval ID"] - 1] || "");
        emp.evalDate = String(evalDate);
        emp.scorePerformance = pr[PROBATION_COL["Score Performance"] - 1] || "";
        emp.scoreDiscipline = pr[PROBATION_COL["Score Discipline"] - 1] || "";
        emp.scoreCommunication = pr[PROBATION_COL["Score Communication"] - 1] || "";
        emp.scoreInitiative = pr[PROBATION_COL["Score Initiative"] - 1] || "";
        emp.scoreTeamwork = pr[PROBATION_COL["Score Teamwork"] - 1] || "";
        emp.averageScore = pr[PROBATION_COL["Average Score"] - 1] || "";
        emp.decision = String(pr[PROBATION_COL["Decision"] - 1] || "");
        emp.extensionDuration = String(pr[PROBATION_COL["Extension Duration"] - 1] || "");
        emp.newContractStart = String(pr[PROBATION_COL["New Contract Start"] - 1] || "");
        emp.newContractEnd = String(pr[PROBATION_COL["New Contract End"] - 1] || "");
        emp.evaluatorNotes = String(pr[PROBATION_COL["Evaluator Notes"] - 1] || "");
        emp.evaluator = String(pr[PROBATION_COL["Evaluator"] - 1] || "");
        emp.skStatus = String(pr[PROBATION_COL["SK Status"] - 1] || "");
        emp.lastEvalDate = String(evalDate);
        emp.lastAvgScore = emp.averageScore !== "" ? Number(emp.averageScore) : "";
        emp.lastKeputusan = emp.decision;
        emp.lastEvaluator = emp.evaluator;
        emp.lastStatusSK = emp.skStatus;
      }
      return emp;
    }

    // Map Employee sheet
    var empMap = {};
    var empSheet = getOrCreateEmployeeSheet_();
    if (empSheet && empSheet.getLastRow() >= 2) {
      var empData = empSheet.getDataRange().getValues();
      var empHdr = empData[0];
      var empCI = {};
      empHdr.forEach(function (h, i) { empCI[String(h).trim()] = i; });

      for (var r = 1; r < empData.length; r++) {
        var row = empData[r];
        if (!row.join("").toString().trim()) continue;
        var item = buildEmpItem(row, empCI);
        if (!item.employeeId) continue;
        empMap[item.employeeId] = item;
      }
    }

    var employees = [];
    var seen = {};

    // 1) Workflow onboarding — kandidat_probation aktif (sumber utama)
    try {
      var probSheet = getOrCreateProbationSheet_();
      if (probSheet && probSheet.getLastRow() >= 2) {
        var probData = probSheet.getDataRange().getValues();
        var probMap = {};
        for (var p = 1; p < probData.length; p++) {
          var pRow = probData[p];
          var pStatus = String(pRow[PROBATION_COL["Status"] - 1] || "").trim();
          if (!isActiveProbationSheetStatus_(pStatus)) continue;

          var pEmpId = normEmployeeId_(pRow[PROBATION_COL["Employee ID"] - 1]);
          if (!pEmpId) continue;

          var pDate = pRow[PROBATION_COL["Created At"] - 1] || "";
          if (!probMap[pEmpId] || String(pDate) > String(probMap[pEmpId]._date)) {
            probMap[pEmpId] = { _date: pDate, row: pRow };
          }
        }

        Object.keys(probMap).forEach(function (eid) {
          var base = empMap[eid] || {
            employeeId: eid,
            recruitmentId: String(probMap[eid].row[PROBATION_COL["Recruitment ID"] - 1] || ""),
            fullName: "",
            status: "Probation",
            statusEmployee: "Probation",
            fromOnboardingWorkflow: true,
          };
          employees.push(mergeProbRow(base, probMap[eid].row));
          seen[eid] = true;
        });
      }
    } catch (joinErr) {
      Logger.log("getProbationList probation join error: " + joinErr);
    }

    // 2) Legacy — Employee Probation tanpa record workflow aktif
    Object.keys(empMap).forEach(function (eid) {
      if (seen[eid]) return;
      var emp = empMap[eid];
      if (!isProbationEmployeeStatus_(emp.statusEmployee || emp.status)) return;
      emp.fromOnboardingWorkflow = false;
      employees.push(emp);
      seen[eid] = true;
    });

    return employees;
  } catch (err) {
    Logger.log("getProbationList error: " + err);
    return [];
  }
}
// ============= UPDATE PROBATION STATUS SK =============
function updateProbationSkStatus(probationId, statusSk) {
  try {
    var lock = LockService.getScriptLock();
    lock.waitLock(15000);

    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");

    var sheet = getOrCreateProbationSheet_();
    var allData = sheet.getDataRange().getValues();
    var rowIndex = -1;

    for (var i = 1; i < allData.length; i++) {
      if (
        String(allData[i][PROBATION_COL["Probation ID"] - 1]).trim() ===
        probationId
      ) {
        rowIndex = i;
        break;
      }
    }

    if (rowIndex === -1) {
      return { success: false, message: "Probation ID tidak ditemukan." };
    }

    sheet.getRange(rowIndex + 1, PROBATION_COL["SK Status"]).setValue(statusSk);
    sheet.getRange(rowIndex + 1, PROBATION_COL["Updated At"]).setValue(nowStr);

    if (statusSk === "SK Diterbitkan") {
      var employeeId = String(
        allData[rowIndex][PROBATION_COL["Employee ID"] - 1] || "",
      ).trim();
      if (employeeId) {
        var empSheet = getOrCreateEmployeeSheet_();
        var empData = empSheet.getDataRange().getValues();
        for (var j = 1; j < empData.length; j++) {
          if (
            String(empData[j][EMPLOYEE_COL["Employee ID"] - 1]).trim() ===
            employeeId
          ) {
            empSheet
              .getRange(j + 1, EMPLOYEE_COL["Status Employee"])
              .setValue("Permanent");
            empSheet
              .getRange(j + 1, EMPLOYEE_COL["Updated At"])
              .setValue(nowStr);
            break;
          }
        }
      }
    }

    writeAuditLog_(probationId, "Update Status SK", "Status SK", "-", statusSk);

    return { success: true, message: "Status SK berhasil diupdate." };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
