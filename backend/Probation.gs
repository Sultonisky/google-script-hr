// ============================================================
// backend/Probation.gs — PROBATION MANAGEMENT
// Handles probation records, evaluations, and SK status updates.
// Uses sheet: kandidat_probation (PROBATION_SHEET_NAME)
// ============================================================

// ============= CREATE PROBATION RECORD =============
function createProbationRecord(recruitmentId) {
  try {
    var lock = LockService.getScriptLock();
    lock.waitLock(15000);

    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var probationId = generateProbationId_(now);

    // Ambil data kontrak dari kandidat_accepted untuk Contract Start/End/Join Date
    var acceptedSheet = getOrCreateAcceptedSheet_();
    var acceptedData = acceptedSheet.getDataRange().getValues();
    var headers = acceptedData[0];
    var candidateRow = null;
    for (var i = 1; i < acceptedData.length; i++) {
      if (String(acceptedData[i][0]).trim() === recruitmentId) {
        candidateRow = acceptedData[i];
        break;
      }
    }
    if (!candidateRow) {
      return { success: false, message: "Recruitment ID tidak ditemukan di sheet accepted." };
    }
    var colIdx = {};
    headers.forEach(function (h, idx) { colIdx[h.trim()] = idx; });

    var joinDate        = candidateRow[colIdx["Offering Join Date"]] || "";
    var contractStart   = candidateRow[colIdx["Offering Join Date"]] || "";
    var contractEnd     = "";
    var contractNumber  = "";
    var contractDuration = "";
    var onboardingBy    = Session.getActiveUser().getEmail();

    // Ambil Employee ID yang sudah dibuat saat onboarding
    // (diisi oleh processOnboardingProbation sebelum memanggil createProbationRecord)
    var empId = "";
    var empSheet = getOrCreateEmployeeSheet_();
    var empData  = empSheet.getDataRange().getValues();
    var empHdr   = empData[0];
    var empCI    = {};
    empHdr.forEach(function (h, i) { empCI[String(h).trim()] = i; });
    for (var e = 1; e < empData.length; e++) {
      var empRec = empData[e];
      var recRid = empCI["Recruitment ID (System Link)"] !== undefined
        ? String(empRec[empCI["Recruitment ID (System Link)"]] || "")
        : String(empRec[empCI["Recruitment ID"]] || "");
      if (recRid.trim() === recruitmentId) {
        empId           = String(empRec[empCI["Employee ID"]] || "");
        contractStart   = String(empRec[empCI["Start Date (Contract)"]] || contractStart);
        contractEnd     = String(empRec[empCI["End Date (Contract)"]] || "");
        contractNumber  = String(empRec[empCI["Contract Number"]] || "");
        contractDuration = String(empRec[empCI["Contract Duration"]] || "");
        break;
      }
    }

    var newRow = new Array(PROBATION_HEADERS.length).fill("");
    newRow[PROBATION_COL["Probation ID"] - 1]      = probationId;
    newRow[PROBATION_COL["Employee ID"] - 1]        = empId;
    newRow[PROBATION_COL["Recruitment ID"] - 1]     = recruitmentId;
    newRow[PROBATION_COL["Contract Number"] - 1]    = contractNumber;
    newRow[PROBATION_COL["Contract Duration"] - 1]  = contractDuration;
    newRow[PROBATION_COL["Contract Start"] - 1]     = contractStart;
    newRow[PROBATION_COL["Contract End"] - 1]       = contractEnd;
    newRow[PROBATION_COL["Join Date"] - 1]          = joinDate;
    newRow[PROBATION_COL["Status"] - 1]             = "Probation";
    newRow[PROBATION_COL["Onboarding Date"] - 1]    = nowStr;
    newRow[PROBATION_COL["Onboarding By"] - 1]      = onboardingBy;
    newRow[PROBATION_COL["SK Status"] - 1]          = "Pending";
    newRow[PROBATION_COL["Created At"] - 1]         = nowStr;
    newRow[PROBATION_COL["Updated At"] - 1]         = nowStr;

    var sheet = getOrCreateProbationSheet_();
    sheet.appendRow(newRow);

    writeAuditLog_(recruitmentId, "Create Probation", "Status", "-", "Probation");

    return { success: true, probationId: probationId, message: "Record probation berhasil dibuat." };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
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
// Sumber utama: Employee sheet (filter Status Employee = "Probation").
// Join dengan kandidat_probation untuk data kontrak & eval.
// Return: array langsung (bukan object) -- sesuai ekspektasi frontend.
function getProbationList() {
  try {
    // -- 1. Baca Employee sheet, filter status Probation ---------
    var empSheet = getOrCreateEmployeeSheet_();
    if (!empSheet || empSheet.getLastRow() < 2) return [];

    var empData = empSheet.getDataRange().getValues();
    var empHdr  = empData[0];
    var empCI   = {};
    empHdr.forEach(function (h, i) { empCI[String(h).trim()] = i; });

    function eCell(row, name) {
      var idx = empCI[name];
      if (idx !== undefined) return row[idx];
      var fallback = {
        "Status Employee":          ["Employment Status", "Status"],
        "Employment Status":        ["Status Employee", "Status"],
        "Status":                   ["Status Employee", "Employment Status"],
        "Position":                 ["Job Position (Locaction)", "Job Position"],
        "Branch":                   ["Branch Name"],
        "Email":                    ["Personal Email", "Working Email"],
        "Phone":                    ["Mobile Phone"],
        "NIK":                      ["NIK - NPWP 16 digit"],
        "Contract Start":           ["Start Date (Contract)"],
        "Contract End":             ["End Date (Contract)"],
        "Recruitment ID":           ["Recruitment ID (System Link)"],
        "Company Entity":           ["Company Entity"],
        "Contract Duration":        ["Contract Duration"],
        "Contract Number":          ["Contract Number"],
      };
      if (fallback[name]) {
        for (var f = 0; f < fallback[name].length; f++) {
          var j = empCI[fallback[name][f]];
          if (j !== undefined) return row[j];
        }
      }
      return "";
    }

    var employees = [];
    for (var r = 1; r < empData.length; r++) {
      var row = empData[r];
      if (!row.join("").toString().trim()) continue;
      var st   = String(eCell(row, "Status") || "").trim();
      var est  = String(eCell(row, "Employment Status") || "").trim();
      var semp = String(eCell(row, "Status Employee") || "").trim();
      if (st !== "Probation" && est !== "Probation" && semp !== "Probation") continue;

      employees.push({
        employeeId:       String(eCell(row, "Employee ID")       || ""),
        recruitmentId:    String(eCell(row, "Recruitment ID")    || ""),
        fullName:         String(eCell(row, "Full Name")         || ""),
        nik:              String(eCell(row, "NIK")               || "").replace(/^'/, ""),
        position:         String(eCell(row, "Position")          || ""),
        department:       String(eCell(row, "Department")        || ""),
        division:         String(eCell(row, "Division")          || ""),
        branch:           String(eCell(row, "Branch")            || ""),
        companyEntity:    String(eCell(row, "Company Entity")    || ""),
        email:            String(eCell(row, "Email")             || ""),
        phone:            String(eCell(row, "Phone")             || ""),
        joinDate:         String(eCell(row, "Join Date")         || ""),
        contractStart:    String(eCell(row, "Contract Start")    || ""),
        contractEnd:      String(eCell(row, "Contract End")      || ""),
        contractDuration: String(eCell(row, "Contract Duration") || ""),
        contractNumber:   String(eCell(row, "Contract Number")   || ""),
        status:           semp || st || est || "Probation",
        statusEmployee:   semp || st || est || "Probation",
        hrNotes:          String(eCell(row, "HR Notes")          || ""),
        createdAt:        String(eCell(row, "Created At")        || ""),
        updatedAt:        String(eCell(row, "Updated At")        || ""),
        // probation & eval fields -- diisi dari join di bawah
        probationId:       "",
        onboardingDate:    "",
        onboardingBy:      "",
        evalId:            "",
        evalDate:          "",
        scorePerformance:  "",
        scoreDiscipline:   "",
        scoreCommunication:"",
        scoreInitiative:   "",
        scoreTeamwork:     "",
        averageScore:      "",
        decision:          "",
        extensionDuration: "",
        newContractStart:  "",
        newContractEnd:    "",
        evaluatorNotes:    "",
        evaluator:         "",
        skStatus:          "",
        notes:             "",
        // alias lama
        lastEvalDate:      "",
        lastAvgScore:      "",
        lastKeputusan:     "",
        lastEvaluator:     "",
        lastStatusSK:      "",
      });
    }

    if (employees.length === 0) return [];

    // -- 2. Join dari kandidat_probation -------------------------
    try {
      var probSheet = getOrCreateProbationSheet_();
      if (probSheet && probSheet.getLastRow() >= 2) {
        var probData = probSheet.getDataRange().getValues();

        // Untuk tiap employee ambil row probation terbaru (by Onboarding Date / Created At)
        var probMap = {};
        for (var p = 1; p < probData.length; p++) {
          var pRow   = probData[p];
          var pEmpId = String(pRow[PROBATION_COL["Employee ID"] - 1] || "").trim();
          if (!pEmpId) continue;
          var pDate  = pRow[PROBATION_COL["Created At"] - 1] || "";
          if (!probMap[pEmpId] || String(pDate) > String(probMap[pEmpId]._date)) {
            probMap[pEmpId] = { _date: pDate, row: pRow };
          }
        }

        employees.forEach(function (emp) {
          var entry = probMap[emp.employeeId];
          if (!entry) return;
          var pr = entry.row;
          emp.probationId       = String(pr[PROBATION_COL["Probation ID"] - 1]      || "");
          emp.onboardingDate    = String(pr[PROBATION_COL["Onboarding Date"] - 1]   || "");
          emp.onboardingBy      = String(pr[PROBATION_COL["Onboarding By"] - 1]     || "");
          // Gunakan kontrak dari kandidat_probation jika lebih lengkap
          emp.contractNumber    = String(pr[PROBATION_COL["Contract Number"] - 1]   || "") || emp.contractNumber;
          emp.contractDuration  = String(pr[PROBATION_COL["Contract Duration"] - 1] || "") || emp.contractDuration;
          emp.contractStart     = String(pr[PROBATION_COL["Contract Start"] - 1]    || "") || emp.contractStart;
          emp.contractEnd       = String(pr[PROBATION_COL["Contract End"] - 1]      || "") || emp.contractEnd;
          emp.notes             = String(pr[PROBATION_COL["Notes"] - 1]             || "");
          // Eval fields
          var evalDate = pr[PROBATION_COL["Eval Date"] - 1];
          if (evalDate) {
            emp.evalId            = String(pr[PROBATION_COL["Eval ID"] - 1]           || "");
            emp.evalDate          = String(evalDate);
            emp.scorePerformance  = pr[PROBATION_COL["Score Performance"] - 1]        || "";
            emp.scoreDiscipline   = pr[PROBATION_COL["Score Discipline"] - 1]         || "";
            emp.scoreCommunication= pr[PROBATION_COL["Score Communication"] - 1]      || "";
            emp.scoreInitiative   = pr[PROBATION_COL["Score Initiative"] - 1]         || "";
            emp.scoreTeamwork     = pr[PROBATION_COL["Score Teamwork"] - 1]           || "";
            emp.averageScore      = pr[PROBATION_COL["Average Score"] - 1]            || "";
            emp.decision          = String(pr[PROBATION_COL["Decision"] - 1]          || "");
            emp.extensionDuration = String(pr[PROBATION_COL["Extension Duration"] - 1]|| "");
            emp.newContractStart  = String(pr[PROBATION_COL["New Contract Start"] - 1]|| "");
            emp.newContractEnd    = String(pr[PROBATION_COL["New Contract End"] - 1]  || "");
            emp.evaluatorNotes    = String(pr[PROBATION_COL["Evaluator Notes"] - 1]   || "");
            emp.evaluator         = String(pr[PROBATION_COL["Evaluator"] - 1]         || "");
            emp.skStatus          = String(pr[PROBATION_COL["SK Status"] - 1]         || "");
            // alias lama
            emp.lastEvalDate   = String(evalDate);
            emp.lastAvgScore   = emp.averageScore !== "" ? Number(emp.averageScore) : "";
            emp.lastKeputusan  = emp.decision;
            emp.lastEvaluator  = emp.evaluator;
            emp.lastStatusSK   = emp.skStatus;
          }
        });
      }
    } catch (joinErr) {
      Logger.log("getProbationList join error: " + joinErr);
    }

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
