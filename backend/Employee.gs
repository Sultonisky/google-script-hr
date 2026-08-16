// ============================================================
// backend/Employee.gs � MASTER DATA KARYAWAN
// Schema: defined in EMPLOYEE_HEADERS (Config.gs)
// Column access: use EMPLOYEE_COL[headerName] � NEVER hardcoded indexes
// ============================================================

// ============= GENERATE EMPLOYEE ID =============
// Reuses generateEmployeeId_() from IdGenerator.gs

// ============= GET EMPLOYEE LIST =============
function getEmployeeList() {
  try {
    var sheet = getOrCreateEmployeeSheet_();
    if (!sheet || sheet.getLastRow() < 2) return { success: true, data: [] };

    var data = sheet.getDataRange().getValues();
    var headers = data[0];
    var colIndex = {};
    headers.forEach(function (h, i) {
      colIndex[String(h).trim()] = i;
    });

    function col(name) {
      return colIndex[name] !== undefined ? colIndex[name] : -1;
    }
    function sval(row, name) {
      var i = col(name);
      return i === -1 ? "" : String(row[i] || "");
    }
    function dval(row, name) {
      var i = col(name);
      return i === -1 ? "" : fmtDateStr_(row[i]);
    }

    var items = [];
    for (var r = 1; r < data.length; r++) {
      var row = data[r];
      if (!row.join("").toString().trim()) continue;

      var statusEmployee = sval(row, "Status Employee");
      var statusLegacy = sval(row, "Status");
      var empStatusLegacy = sval(row, "Employment Status");
      var finalStatus =
        statusEmployee || empStatusLegacy || statusLegacy || "Active";

      var createdAtRaw = col("Created At") === -1 ? "" : row[col("Created At")];
      var updatedAtRaw = col("Updated At") === -1 ? "" : row[col("Updated At")];

      items.push({
        employeeId: sval(row, "Employee ID"),
        fullName: sval(row, "Full Name"),
        nik: sval(row, "NIK - NPWP 16 digit") || sval(row, "NIK"),
        npwp: sval(row, "NPWP"),
        birthPlace: sval(row, "Birth Place"),
        birthDate: dval(row, "Birth Date"),
        gender: sval(row, "Gender"),
        religion: sval(row, "Religion"),
        maritalStatus: sval(row, "Marital Status"),
        bloodType: sval(row, "Blood Type"),
        ptkpStatus: sval(row, "PTKP Status"),
        citizenIdAddress:
          sval(row, "Citizen ID Address") || sval(row, "Address"),
        residentialAddress: sval(row, "Residential Address"),
        mobilePhone: sval(row, "Mobile Phone") || sval(row, "Phone"),
        personalEmail: sval(row, "Personal Email") || sval(row, "Email"),
        workingEmail: sval(row, "Working Email"),
        email: sval(row, "Personal Email") || sval(row, "Email"),
        phone: sval(row, "Mobile Phone") || sval(row, "Phone"),
        address: sval(row, "Citizen ID Address") || sval(row, "Address"),
        city: sval(row, "Lokasi Kerja") || sval(row, "City"),
        bankName: sval(row, "Bank Name"),
        bankAccount: sval(row, "Bank Account"),
        bankAccountHolder: sval(row, "Bank Account Holder"),
        bpjsKetenagakerjaan: sval(row, "BPJS Ketenagakerjaan"),
        bpjsKesehatan: sval(row, "BPJS Kesehatan"),
        branchName: sval(row, "Branch Name") || sval(row, "Branch"),
        branch: sval(row, "Branch Name") || sval(row, "Branch"),
        division: sval(row, "Division"),
        department: sval(row, "Department"),
        position:
          sval(row, "Job Position (Locaction)") || sval(row, "Position"),
        positionCurrent:
          sval(row, "Job Position (Locaction)") || sval(row, "Position"),
        positionNoLocCurrent: sval(row, "Job Position"),
        jobLevel: sval(row, "Job Level"),
        grade: sval(row, "Grade"),
        areaKerja: sval(row, "Area Kerja"),
        lokasiKerja: sval(row, "Lokasi Kerja") || sval(row, "City"),
        costCenter: sval(row, "Cost Center"),
        directSuperior: sval(row, "Direct Superior"),
        indirectSuperior: sval(row, "Indirect Superior"),
        statusEmployee: finalStatus,
        status: finalStatus,
        employmentStatus: finalStatus,
        joinDate: dval(row, "Join Date"),
        contractEnd:
          dval(row, "End Date (Contract)") || dval(row, "Contract End"),
        positionFormer: sval(row, "Job Position (Former)"),
        typeOfRotation: sval(row, "Type of Rotation"),
        mutasiDate: dval(row, "Tanggal Mutasi/Demosi/Promosi"),
        nomorSk: sval(row, "Nomor SK"),
        resignDate: dval(row, "Resign Date"),
        createdBy: sval(row, "Created By"),
        createdAt:
          createdAtRaw instanceof Date
            ? Utilities.formatDate(createdAtRaw, "GMT+7", "dd/MM/yyyy HH:mm")
            : fmtDateStr_(String(createdAtRaw || "")),
        _rawCreatedAt:
          createdAtRaw instanceof Date
            ? createdAtRaw.getTime()
            : new Date(String(createdAtRaw || "")).getTime() || 0,
        updatedAt:
          updatedAtRaw instanceof Date
            ? Utilities.formatDate(updatedAtRaw, "GMT+7", "dd/MM/yyyy HH:mm")
            : fmtDateStr_(String(updatedAtRaw || "")),
      });
    }

    items.sort(function (a, b) {
      return (b._rawCreatedAt || 0) - (a._rawCreatedAt || 0);
    });

    items.forEach(function (item) {
      delete item._rawCreatedAt;
    });

    return { success: true, data: items };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= ADD EMPLOYEE =============
function addEmployee(empData) {
  try {
    if (!empData.fullName)
      return { success: false, message: "Nama karyawan wajib diisi." };

    var lock = LockService.getScriptLock();
    lock.waitLock(10000);

    var now = new Date();
    var newId = generateEmployeeId_(now);
    var createdAt = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");

    var sheet = getOrCreateEmployeeSheet_();

    var newRow = new Array(EMPLOYEE_HEADERS.length).fill("");
    newRow[EMPLOYEE_COL["Employee ID"] - 1] = newId;
    newRow[EMPLOYEE_COL["Full Name"] - 1] = empData.fullName || "";
    newRow[EMPLOYEE_COL["NIK - NPWP 16 digit"] - 1] = empData.nik || "";
    newRow[EMPLOYEE_COL["NPWP"] - 1] = empData.npwp || "";
    newRow[EMPLOYEE_COL["Birth Place"] - 1] = empData.birthPlace || "";
    newRow[EMPLOYEE_COL["Birth Date"] - 1] = empData.birthDate || "";
    newRow[EMPLOYEE_COL["Gender"] - 1] = empData.gender || "";
    newRow[EMPLOYEE_COL["Religion"] - 1] = empData.religion || "";
    newRow[EMPLOYEE_COL["Marital Status"] - 1] = empData.maritalStatus || "";
    newRow[EMPLOYEE_COL["Blood Type"] - 1] = empData.bloodType || "";
    newRow[EMPLOYEE_COL["PTKP Status"] - 1] = empData.ptkpStatus || "";
    newRow[EMPLOYEE_COL["Citizen ID Address"] - 1] =
      empData.citizenIdAddress || empData.address || "";
    newRow[EMPLOYEE_COL["Residential Address"] - 1] =
      empData.residentialAddress || empData.address || "";
    newRow[EMPLOYEE_COL["Mobile Phone"] - 1] =
      empData.mobilePhone || empData.phone || "";
    newRow[EMPLOYEE_COL["Personal Email"] - 1] =
      empData.personalEmail || empData.email || "";
    newRow[EMPLOYEE_COL["Working Email"] - 1] = empData.workingEmail || "";
    newRow[EMPLOYEE_COL["Bank Name"] - 1] = empData.bankName || "";
    newRow[EMPLOYEE_COL["Bank Account"] - 1] = empData.bankAccount || "";
    newRow[EMPLOYEE_COL["Bank Account Holder"] - 1] =
      empData.bankAccountHolder || empData.fullName || "";
    newRow[EMPLOYEE_COL["BPJS Ketenagakerjaan"] - 1] =
      empData.bpjsKetenagakerjaan || "";
    newRow[EMPLOYEE_COL["BPJS Kesehatan"] - 1] = empData.bpjsKesehatan || "";
    newRow[EMPLOYEE_COL["Branch Name"] - 1] =
      empData.branchName || empData.branch || "";
    newRow[EMPLOYEE_COL["Division"] - 1] = empData.division || "";
    newRow[EMPLOYEE_COL["Department"] - 1] = empData.department || "";
    newRow[EMPLOYEE_COL["Job Position (Locaction)"] - 1] =
      empData.positionCurrent || empData.position || "";
    newRow[EMPLOYEE_COL["Job Position"] - 1] =
      empData.positionNoLocCurrent || empData.position || "";
    newRow[EMPLOYEE_COL["Job Level"] - 1] = empData.jobLevel || "";
    newRow[EMPLOYEE_COL["Grade"] - 1] = empData.grade || "";
    newRow[EMPLOYEE_COL["Area Kerja"] - 1] =
      empData.areaKerja || empData.district || "";
    newRow[EMPLOYEE_COL["Lokasi Kerja"] - 1] =
      empData.lokasiKerja || empData.city || empData.workLocation || "";
    newRow[EMPLOYEE_COL["Cost Center"] - 1] = empData.costCenter || "";
    newRow[EMPLOYEE_COL["Direct Superior"] - 1] = empData.directSuperior || "";
    newRow[EMPLOYEE_COL["Indirect Superior"] - 1] =
      empData.indirectSuperior || "";
    newRow[EMPLOYEE_COL["Status Employee"] - 1] =
      empData.statusEmployee ||
      empData.status ||
      empData.employmentStatus ||
      "Active";
    newRow[EMPLOYEE_COL["Join Date"] - 1] = empData.joinDate || createdAt;
    newRow[EMPLOYEE_COL["End Date (Contract)"] - 1] = empData.contractEnd || "";
    newRow[EMPLOYEE_COL["Job Position (Former)"] - 1] =
      empData.positionFormer || "";
    newRow[EMPLOYEE_COL["Type of Rotation"] - 1] = empData.typeOfRotation || "";
    newRow[EMPLOYEE_COL["Tanggal Mutasi/Demosi/Promosi"] - 1] =
      empData.mutasiDate || "";
    newRow[EMPLOYEE_COL["Nomor SK"] - 1] = empData.nomorSk || "";
    newRow[EMPLOYEE_COL["Resign Date"] - 1] = empData.resignDate || "";
    newRow[EMPLOYEE_COL["Created By"] - 1] = empData.createdBy || "";
    newRow[EMPLOYEE_COL["Created At"] - 1] = createdAt;
    newRow[EMPLOYEE_COL["Updated At"] - 1] = createdAt;

    sheet.appendRow(newRow);
    lock.releaseLock();

    return {
      success: true,
      message: 'Karyawan "' + empData.fullName + '" berhasil ditambahkan.',
      id: newId,
    };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= OFFBOARDING =============
// Status karyawan yang memicu offboarding otomatis
var OFFBOARDING_TRIGGER_STATUSES = ["Resigned", "Terminated", "On Leave"];

// Pemetaan status employee -> Offboarding Type
var OFFBOARDING_TYPE_MAP = {
  Resigned: "Resignation",
  Terminated: "Termination",
  "On Leave": "On Leave",
};

// Kembalikan nama offboarding type untuk sebuah status, atau null jika
// status tersebut tidak memicu offboarding.
function resolveOffboardingType_(status) {
  status = String(status || "").trim();
  return OFFBOARDING_TYPE_MAP[status] || null;
}

// Simpan catatan offboarding ke sheet Offboarding (buat jika belum ada).
// Dipanggil dari updateEmployee saat status berubah ke Resigned / Terminated /
// On Leave. Mengirim lock dari caller (updateEmployee), jadi tidak mengambil
// lock ulang.
function offboardEmployee_(employeeId, offboardingType, reason, notes) {
  try {
    var sheet = getOrCreateEmployeeSheet_();
    var data = sheet.getDataRange().getValues();
    var headers = data[0];
    var colIndex = {};
    headers.forEach(function (h, i) {
      colIndex[String(h).trim()] = i;
    });

    var row = null;
    for (var r = 1; r < data.length; r++) {
      if (
        String(data[r][colIndex["Employee ID"]] || "") === String(employeeId)
      ) {
        row = data[r];
        break;
      }
    }
    if (!row)
      return {
        success: false,
        message: "Karyawan tidak ditemukan: " + employeeId,
      };

    // Cegah duplikat: sudah ada offboarding bertipe sama untuk employee ini
    var offSheet = getOrCreateOffboardingSheet_();
    if (offSheet.getLastRow() > 1) {
      var offData = offSheet
        .getRange(2, 1, offSheet.getLastRow() - 1, OFFBOARDING_HEADERS.length)
        .getValues();
      for (var i = 0; i < offData.length; i++) {
        if (
          String(offData[i][OFFBOARD_COL["Employee ID"] - 1] || "") ===
            String(employeeId) &&
          String(offData[i][OFFBOARD_COL["Offboarding Type"] - 1] || "") ===
            String(offboardingType)
        ) {
          return {
            success: false,
            duplicate: true,
            offboardingId: String(
              offData[i][OFFBOARD_COL["Offboarding ID"] - 1] || "",
            ),
          };
        }
      }
    }

    var user = Session.getActiveUser().getEmail() || "HR Dashboard";
    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var offboardingId = generateOffboardingId_(now);

    function val(name) {
      var i = colIndex[name];
      if (i !== undefined) return String(row[i] || "");
      // Fallback mapping for renamed headers during migration
      var fallbackMap = {
        Position: "Job Position (Locaction)",
        Address: "Citizen ID Address",
        Email: "Personal Email",
        Phone: "Mobile Phone",
        NIK: "NIK - NPWP 16 digit",
        Branch: "Branch Name",
        City: "Lokasi Kerja",
        "Contract Start": "Start Date (Contract)",
        "Contract End": "End Date (Contract)",
        "Recruitment ID": "Recruitment ID (System Link)",
      };
      if (fallbackMap[name]) {
        var j = colIndex[fallbackMap[name]];
        return j === undefined ? "" : String(row[j] || "");
      }
      return "";
    }

    var newRow = new Array(OFFBOARDING_HEADERS.length).fill("");
    newRow[OFFBOARD_COL["Offboarding ID"] - 1] = offboardingId;
    newRow[OFFBOARD_COL["Employee ID"] - 1] = employeeId;
    newRow[OFFBOARD_COL["Full Name"] - 1] = val("Full Name");
    newRow[OFFBOARD_COL["Position"] - 1] = val("Position");
    newRow[OFFBOARD_COL["Department"] - 1] = val("Department");
    newRow[OFFBOARD_COL["Join Date"] - 1] = val("Join Date");
    newRow[OFFBOARD_COL["Last Working Date"] - 1] = nowStr;
    newRow[OFFBOARD_COL["Offboarding Type"] - 1] = offboardingType;
    newRow[OFFBOARD_COL["Reason"] - 1] = reason || "";
    newRow[OFFBOARD_COL["Approved By"] - 1] = user;
    newRow[OFFBOARD_COL["Notes"] - 1] = notes || "";
    newRow[OFFBOARD_COL["Status"] - 1] = "Active";
    newRow[OFFBOARD_COL["Archived"] - 1] = "No";
    newRow[OFFBOARD_COL["Created By"] - 1] = user;
    newRow[OFFBOARD_COL["Created At"] - 1] = nowStr;
    newRow[OFFBOARD_COL["Updated At"] - 1] = nowStr;

    offSheet.appendRow(newRow);

    writeAuditLog_(
      employeeId,
      "Offboarding",
      "Status Employee",
      offboardingType,
      offboardingType + " -> " + offboardingId,
    );

    return { success: true, offboardingId: offboardingId };
  } catch (err) {
    Logger.log("offboardEmployee_ ERROR for " + employeeId + ": " + err);
    return { success: false, message: err.toString() };
  }
}

// ============= UPDATE EMPLOYEE =============
function updateEmployee(id, updates) {
  try {
    if (!id || !updates)
      return { success: false, message: "ID dan data harus diisi." };

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateEmployeeSheet_();
    var data = sheet.getDataRange().getValues();

    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(id)) {
        var now = Utilities.formatDate(
          new Date(),
          "GMT+7",
          "yyyy-MM-dd HH:mm:ss",
        );

        // ---- Tangkap status lama untuk deteksi offboarding ----
        // Strategy: try new Status Employee header, fallback to old dual statuses
        var hIdx = {};
        data[0].forEach(function (h, idx) {
          hIdx[String(h).trim()] = idx;
        });
        function gcol(name) {
          return hIdx[name] !== undefined ? hIdx[name] : -1;
        }

        var oldStatusEmployee =
          gcol("Status Employee") !== -1
            ? String(data[i][gcol("Status Employee")] || "")
            : "";
        var oldEmploymentStatus =
          gcol("Employment Status") !== -1
            ? String(data[i][gcol("Employment Status")] || "")
            : "";
        var oldStatus =
          gcol("Status") !== -1 ? String(data[i][gcol("Status")] || "") : "";
        var finalOldStatus =
          oldStatusEmployee || oldEmploymentStatus || oldStatus || "Active";

        // Map camelCase update keys to header names (Schema v2)
        // Legacy keys preserved for backward compat
        var keyMap = {
          employeeId: "Employee ID",
          fullName: "Full Name",
          nik: "NIK - NPWP 16 digit",
          npwp: "NPWP",
          birthPlace: "Birth Place",
          birthDate: "Birth Date",
          gender: "Gender",
          religion: "Religion",
          maritalStatus: "Marital Status",
          bloodType: "Blood Type",
          ptkpStatus: "PTKP Status",
          citizenIdAddress: "Citizen ID Address",
          address: "Citizen ID Address", // legacy alias
          residentialAddress: "Residential Address",
          mobilePhone: "Mobile Phone",
          phone: "Mobile Phone", // legacy alias
          personalEmail: "Personal Email",
          email: "Personal Email", // legacy alias
          workingEmail: "Working Email",
          bankName: "Bank Name",
          bankAccount: "Bank Account",
          bankAccountHolder: "Bank Account Holder",
          bpjsKetenagakerjaan: "BPJS Ketenagakerjaan",
          bpjsKesehatan: "BPJS Kesehatan",
          branchName: "Branch Name",
          branch: "Branch Name", // legacy alias
          division: "Division",
          department: "Department",
          positionCurrent: "Job Position (Locaction)",
          position: "Job Position (Locaction)", // legacy alias
          positionNoLocCurrent: "Job Position",
          jobLevel: "Job Level",
          grade: "Grade",
          areaKerja: "Area Kerja",
          district: "Area Kerja", // legacy alias
          lokasiKerja: "Lokasi Kerja",
          city: "Lokasi Kerja", // legacy alias
          workLocation: "Lokasi Kerja", // legacy alias
          costCenter: "Cost Center",
          directSuperior: "Direct Superior",
          indirectSuperior: "Indirect Superior",
          statusEmployee: "Status Employee",
          employmentStatus: "Status Employee", // legacy merge ? single column
          status: "Status Employee", // legacy merge ? single column
          joinDate: "Join Date",
          contractEnd: "End Date (Contract)",
          positionFormer: "Job Position (Former)",
          typeOfRotation: "Type of Rotation",
          mutasiDate: "Tanggal Mutasi/Demosi/Promosi",
          nomorSk: "Nomor SK",
          resignDate: "Resign Date",
          createdBy: "Created By",
        };

        var col = -1;
        for (var key in updates) {
          if (!updates.hasOwnProperty(key)) continue;
          var header = keyMap[key];
          if (header && EMPLOYEE_COL[header]) {
            sheet.getRange(i + 1, EMPLOYEE_COL[header]).setValue(updates[key]);
          }
        }
        // Always update "Updated At"
        sheet.getRange(i + 1, EMPLOYEE_COL["Updated At"]).setValue(now);

        // ---- Trigger offboarding otomatis ----
        // Jika status baru masuk daftar pemicu (Resigned / Terminated / On Leave)
        // dan berbeda dari status lama, catat ke sheet Offboarding.
        // Strategy: merge Status Employee (all sources) � perubahan apapun ke
        // field statusEmployee / employmentStatus / status semuanya write ke
        // kolom Status Employee (single source of truth v2).
        var incomingStatusEmployee =
          updates.statusEmployee !== undefined
            ? String(updates.statusEmployee)
            : updates.employmentStatus !== undefined
              ? String(updates.employmentStatus)
              : updates.status !== undefined
                ? String(updates.status)
                : finalOldStatus;

        var changedStatus = incomingStatusEmployee !== finalOldStatus;
        var offboardingType = null;
        if (changedStatus)
          offboardingType = resolveOffboardingType_(incomingStatusEmployee);

        // Audit perubahan status (wajib sesuai AGENTS.md).
        if (changedStatus) {
          writeAuditLog_(
            id,
            "Update Status",
            "Status Employee",
            finalOldStatus,
            incomingStatusEmployee,
          );
        }

        var offboardResult = null;
        if (offboardingType) {
          var reason = updates.reason || "";
          var note = updates.notes || updates.hrNotes || "";
          if (note === reason) note = "";
          offboardResult = offboardEmployee_(id, offboardingType, reason, note);
        }

        lock.releaseLock();

        return {
          success: true,
          message: "Data karyawan berhasil diperbarui.",
          offboarding: offboardResult
            ? {
                triggered: true,
                type: offboardingType,
                id: offboardResult.offboardingId || null,
                duplicate: !!offboardResult.duplicate,
                offboardId: offboardResult.offboardingId,
              }
            : { triggered: false },
        };
      }
    }

    lock.releaseLock();
    return { success: false, message: "Karyawan tidak ditemukan." };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= DELETE EMPLOYEE =============
function deleteEmployee(id) {
  try {
    if (!id) return { success: false, message: "ID harus diisi." };

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateEmployeeSheet_();
    var data = sheet.getDataRange().getValues();
    var colNameIdx = 0; // Employee ID is column 1

    for (var i = 1; i < data.length; i++) {
      if (String(data[i][colNameIdx]) === String(id)) {
        var name = data[i][EMPLOYEE_COL["Full Name"] - 1];
        sheet.deleteRow(i + 1);
        lock.releaseLock();
        return {
          success: true,
          message: 'Karyawan "' + name + '" berhasil dihapus.',
        };
      }
    }

    lock.releaseLock();
    return { success: false, message: "Karyawan tidak ditemukan." };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= EMPLOYEE STATISTICS =============
function getEmployeeStats() {
  try {
    var result = getEmployeeList();
    if (!result.success) return result;

    var items = result.data;
    var stats = {
      total: items.length,
      active: 0,
      inactive: 0,
      byDepartment: {},
      byType: {},
      byLocation: {},
    };

    items.forEach(function (emp) {
      var s = (emp.statusEmployee || "").toLowerCase();
      if (s === "active" || s === "probation") stats.active++;
      else stats.inactive++;

      var dept = emp.department || "Belum Ditentukan";
      stats.byDepartment[dept] = (stats.byDepartment[dept] || 0) + 1;

      var type = emp.employeeType || "Belum Ditentukan";
      stats.byType[type] = (stats.byType[type] || 0) + 1;

      var city = emp.lokasiKerja || emp.city || "Belum Ditentukan";
      stats.byLocation[city] = (stats.byLocation[city] || 0) + 1;
    });

    return { success: true, stats: stats };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============================================================
// PROCESS ONBOARDING PROBATION
// Dipanggil ketika HR memproses kandidat Accepted (offeringResponse
// = "Diterima") menjadi Employee Probation.
//
// contractData: {
//   companyEntity, department, division, branch, position,
//   employeeType, contractNumber, contractDuration,
//   contractStart, contractEnd, salary, salaryType, notes
// }
// ============================================================
function processOnboardingProbation(
  recruitmentId,
  employeeId,
  contractData,
  processedBy,
) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var user =
      processedBy || Session.getActiveUser().getEmail() || "HR Dashboard";

    // -- 1. Update Employee sheet ------------------------------
    var empSheet = getOrCreateEmployeeSheet_();
    if (!empSheet || empSheet.getLastRow() < 2)
      return { success: false, message: "Sheet Employee tidak ditemukan." };

    var empData = empSheet.getDataRange().getValues();
    var empHdr = empData[0];
    var empColIdx = {};
    empHdr.forEach(function (h, i) {
      empColIdx[String(h).trim()] = i;
    });

    var empRow = -1;
    for (var r = 1; r < empData.length; r++) {
      if (
        String(empData[r][empColIdx["Employee ID"]] || "") ===
        String(employeeId)
      ) {
        empRow = r + 1; // 1-based
        break;
      }
    }
    if (empRow === -1)
      return {
        success: false,
        message: "Employee ID tidak ditemukan: " + employeeId,
      };

    // Field yang di-update di Employee sheet (Schema v2)
    // Catatan: Salary/SalaryType TIDAK ADA lagi di sheet Employee v2 (keputusan user hapus)
    var empUpdates = {
      "Company Entity": contractData.companyEntity || "",
      Department: contractData.department || "",
      Division: contractData.division || "",
      "Branch Name": contractData.branch || "",
      "Job Position (Locaction)": contractData.position || "",
      "Job Position": contractData.position || "",
      "Employee Type": contractData.employeeType || "PKWT",
      "Contract Number": contractData.contractNumber || "",
      "Contract Duration": contractData.contractDuration || "",
      "Start Date (Contract)": contractData.contractStart || "",
      "End Date (Contract)": contractData.contractEnd || "",
      "Status Employee": "Probation",
      "HR Notes": contractData.notes || "",
      "Updated At": nowStr,
    };

    Object.keys(empUpdates).forEach(function (colName) {
      var colNum = EMPLOYEE_COL[colName];
      if (colNum)
        empSheet.getRange(empRow, colNum).setValue(empUpdates[colName]);
    });

    // -- 2. Update kandidat_accepted sheet --------------------
    var accSheet = ss.getSheetByName(ACCEPTED_SHEET_NAME);
    if (accSheet && accSheet.getLastRow() > 1) {
      ensureStatusSheetHeaders_(accSheet, ACCEPTED_HEADERS);

      var accData = accSheet.getDataRange().getValues();
      var accHdr = accData[0];
      var accColIdx = {};
      accHdr.forEach(function (h, i) {
        accColIdx[String(h).trim()] = i;
      });

      var accRow = -1;
      var idCol = accColIdx["Recruitment ID"];
      if (idCol !== undefined) {
        for (var ra = 1; ra < accData.length; ra++) {
          if (String(accData[ra][idCol] || "") === String(recruitmentId)) {
            accRow = ra + 1;
            break;
          }
        }
      }

      if (accRow !== -1) {
        var onboardStatusCol = accColIdx["Onboarding Status"];
        var onboardDateCol = accColIdx["Onboarding Date"];
        var onboardByCol = accColIdx["Onboarding By"];
        if (onboardStatusCol !== undefined)
          accSheet.getRange(accRow, onboardStatusCol + 1).setValue("Probation");
        if (onboardDateCol !== undefined)
          accSheet.getRange(accRow, onboardDateCol + 1).setValue(nowStr);
        if (onboardByCol !== undefined)
          accSheet.getRange(accRow, onboardByCol + 1).setValue(user);
      }
    }

    // -- 3. Audit log -----------------------------------------
    writeAuditLog_(
      recruitmentId,
      "Onboarding Probation",
      "Employment Status",
      "Accepted",
      "Probation � Employee " + employeeId + " by " + user,
    );

    createProbationRecord(recruitmentId);

    return {
      success: true,
      employeeId: employeeId,
      recruitmentId: recruitmentId,
      status: "Probation",
      onboardingDate: nowStr,
      onboardingBy: user,
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// GET PROBATION EVAL HISTORY -- ambil semua riwayat evaluasi
// untuk satu employee dari kandidat_probation (untuk drawer / detail)
// ============================================================
function getProbationEvalHistory(employeeId) {
  try {
    var sheet = getOrCreateProbationSheet_();
    if (sheet.getLastRow() < 2) return [];

    var data = sheet.getDataRange().getValues();
    var result = [];
    for (var r = 1; r < data.length; r++) {
      var row = data[r];
      if (String(row[PROBATION_COL["Employee ID"] - 1] || "") !== String(employeeId))
        continue;
      var evalDate = row[PROBATION_COL["Eval Date"] - 1];
      if (!evalDate) continue; // skip record yang belum dievaluasi
      result.push({
        evalId: String(row[PROBATION_COL["Eval ID"] - 1] || ""),
        probationId: String(row[PROBATION_COL["Probation ID"] - 1] || ""),
        evalDate: fmtDateStr_(evalDate),
        scorePerformance:    row[PROBATION_COL["Score Performance"] - 1]    !== "" ? Number(row[PROBATION_COL["Score Performance"] - 1])    : "",
        scoreDiscipline:     row[PROBATION_COL["Score Discipline"] - 1]     !== "" ? Number(row[PROBATION_COL["Score Discipline"] - 1])     : "",
        scoreCommunication:  row[PROBATION_COL["Score Communication"] - 1]  !== "" ? Number(row[PROBATION_COL["Score Communication"] - 1])  : "",
        scoreInitiative:     row[PROBATION_COL["Score Initiative"] - 1]     !== "" ? Number(row[PROBATION_COL["Score Initiative"] - 1])     : "",
        scoreTeamwork:       row[PROBATION_COL["Score Teamwork"] - 1]       !== "" ? Number(row[PROBATION_COL["Score Teamwork"] - 1])       : "",
        averageScore:        row[PROBATION_COL["Average Score"] - 1]        !== "" ? Number(row[PROBATION_COL["Average Score"] - 1])        : "",
        decision:            String(row[PROBATION_COL["Decision"] - 1]             || ""),
        extensionDuration:   String(row[PROBATION_COL["Extension Duration"] - 1]   || ""),
        newContractStart:    fmtDateStr_(row[PROBATION_COL["New Contract Start"] - 1]),
        newContractEnd:      fmtDateStr_(row[PROBATION_COL["New Contract End"] - 1]),
        evaluatorNotes:      String(row[PROBATION_COL["Evaluator Notes"] - 1]       || ""),
        evaluator:           String(row[PROBATION_COL["Evaluator"] - 1]             || ""),
        createdAt:           fmtDateStr_(row[PROBATION_COL["Created At"] - 1]),
        skStatus:            String(row[PROBATION_COL["SK Status"] - 1]             || ""),
        // Alias lama agar frontend tidak perlu diubah sekaligus
        skorKinerja:         row[PROBATION_COL["Score Performance"] - 1]    !== "" ? Number(row[PROBATION_COL["Score Performance"] - 1])    : "",
        skorKedisiplinan:    row[PROBATION_COL["Score Discipline"] - 1]     !== "" ? Number(row[PROBATION_COL["Score Discipline"] - 1])     : "",
        skorKomunikasi:      row[PROBATION_COL["Score Communication"] - 1]  !== "" ? Number(row[PROBATION_COL["Score Communication"] - 1])  : "",
        skorInisiatif:       row[PROBATION_COL["Score Initiative"] - 1]     !== "" ? Number(row[PROBATION_COL["Score Initiative"] - 1])     : "",
        nilaiRataRata:       row[PROBATION_COL["Average Score"] - 1]        !== "" ? Number(row[PROBATION_COL["Average Score"] - 1])        : "",
        keputusan:           String(row[PROBATION_COL["Decision"] - 1]             || ""),
        durasiPerpanjang:    String(row[PROBATION_COL["Extension Duration"] - 1]   || ""),
        kontrakBaruStart:    fmtDateStr_(row[PROBATION_COL["New Contract Start"] - 1]),
        kontrakBaruEnd:      fmtDateStr_(row[PROBATION_COL["New Contract End"] - 1]),
        catatan:             String(row[PROBATION_COL["Evaluator Notes"] - 1]       || ""),
        statusSK:            String(row[PROBATION_COL["SK Status"] - 1]             || ""),
      });
    }
    // Urutkan terbaru dulu
    result.sort(function (a, b) {
      return (b.evalDate || "").localeCompare(a.evalDate || "");
    });
    return result;
  } catch (e) {
    return [];
  }
}
// SAVE PROBATION EVAL � simpan hasil evaluasi probation
//
// evalData: {
//   employeeId, recruitmentId,
//   skorKinerja, skorKedisiplinan, skorKomunikasi,
//   skorInisiatif, skorTeamwork,
//   keputusan,           // "Lulus ? Karyawan Tetap" | "Tidak Lulus ? Perpanjang Probation"
//   durasiPerpanjang,    // diisi jika Tidak Lulus (e.g. "3 Bulan")
//   kontrakBaruStart,    // YYYY-MM-DD jika perpanjang
//   kontrakBaruEnd,      // YYYY-MM-DD jika perpanjang
//   catatan
// }
// evaluatedBy: email HR
// ============================================================
function saveProbationEval(evalData, evaluatedBy) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var user =
      evaluatedBy || Session.getActiveUser().getEmail() || "HR Dashboard";
    var evalId = generateEvalId_(now);

    // -- 1. Ambil data employee dari Employee sheet ------------
    var empSheet = getOrCreateEmployeeSheet_();
    var empData = empSheet.getDataRange().getValues();
    var empHdr = empData[0];
    var empCI = {};
    empHdr.forEach(function (h, i) {
      empCI[String(h).trim()] = i;
    });

    var empRow = -1;
    for (var r = 1; r < empData.length; r++) {
      if (
        String(empData[r][empCI["Employee ID"]] || "") ===
        String(evalData.employeeId)
      ) {
        empRow = r;
        break;
      }
    }
    if (empRow === -1)
      return {
        success: false,
        message: "Employee ID tidak ditemukan: " + evalData.employeeId,
      };

    var emp = empData[empRow];
    function empVal(col) {
      if (empCI[col] !== undefined) return emp[empCI[col]];
      var fallback = {
        Position: "Job Position (Locaction)",
        "Job Position": "Position",
        Branch: "Branch Name",
        "Branch Name": "Branch",
        "Contract Start": "Start Date (Contract)",
        "Start Date (Contract)": "Contract Start",
        "Contract End": "End Date (Contract)",
        "End Date (Contract)": "Contract End",
      };
      if (fallback[col] && empCI[fallback[col]] !== undefined)
        return emp[empCI[fallback[col]]];
      return "";
    }

    // -- 2. Hitung nilai rata-rata -----------------------------
    var scores = [
      Number(evalData.skorKinerja || 0),
      Number(evalData.skorKedisiplinan || 0),
      Number(evalData.skorKomunikasi || 0),
      Number(evalData.skorInisiatif || 0),
      Number(evalData.skorTeamwork || 0),
    ];
    var avg =
      Math.round(
        (scores.reduce(function (s, v) {
          return s + v;
        }, 0) /
          5) *
          10,
      ) / 10;

    var isLulus = evalData.keputusan === "Lulus ? Karyawan Tetap";
    var isPerpanjang =
      evalData.keputusan === "Tidak Lulus ? Perpanjang Probation";

    // -- 3. Update row di kandidat_probation dengan hasil evaluasi -----
    // Cari row berdasarkan probationId (dari evalData.probationId)
    // atau fallback ke recruitmentId jika probationId tidak ada
    var probSheet = getOrCreateProbationSheet_();
    var probData = probSheet.getDataRange().getValues();
    var probRowIndex = -1;
    if (evalData.probationId) {
      for (var pi = 1; pi < probData.length; pi++) {
        if (String(probData[pi][PROBATION_COL["Probation ID"] - 1]).trim() === String(evalData.probationId).trim()) {
          probRowIndex = pi;
          break;
        }
      }
    }
    if (probRowIndex === -1 && evalData.employeeId) {
      // fallback: ambil row probation aktif (Status == "Probation") milik employee ini
      for (var pi = 1; pi < probData.length; pi++) {
        var rowEmpId = String(probData[pi][PROBATION_COL["Employee ID"] - 1] || "").trim();
        var rowStatus = String(probData[pi][PROBATION_COL["Status"] - 1] || "").trim();
        if (rowEmpId === String(evalData.employeeId).trim() && rowStatus === "Probation") {
          probRowIndex = pi;
        }
      }
    }
    if (probRowIndex !== -1) {
      var pRow = probRowIndex + 1; // 1-based
      probSheet.getRange(pRow, PROBATION_COL["Eval ID"]).setValue(evalId);
      probSheet.getRange(pRow, PROBATION_COL["Eval Date"]).setValue(nowStr);
      probSheet.getRange(pRow, PROBATION_COL["Score Performance"]).setValue(Number(evalData.skorKinerja || 0));
      probSheet.getRange(pRow, PROBATION_COL["Score Discipline"]).setValue(Number(evalData.skorKedisiplinan || 0));
      probSheet.getRange(pRow, PROBATION_COL["Score Communication"]).setValue(Number(evalData.skorKomunikasi || 0));
      probSheet.getRange(pRow, PROBATION_COL["Score Initiative"]).setValue(Number(evalData.skorInisiatif || 0));
      probSheet.getRange(pRow, PROBATION_COL["Score Teamwork"]).setValue(Number(evalData.skorTeamwork || 0));
      probSheet.getRange(pRow, PROBATION_COL["Average Score"]).setValue(avg);
      probSheet.getRange(pRow, PROBATION_COL["Decision"]).setValue(evalData.keputusan || "");
      probSheet.getRange(pRow, PROBATION_COL["Extension Duration"]).setValue(evalData.durasiPerpanjang || "");
      probSheet.getRange(pRow, PROBATION_COL["New Contract Start"]).setValue(evalData.kontrakBaruStart || "");
      probSheet.getRange(pRow, PROBATION_COL["New Contract End"]).setValue(evalData.kontrakBaruEnd || "");
      probSheet.getRange(pRow, PROBATION_COL["Evaluator Notes"]).setValue(evalData.catatan || "");
      probSheet.getRange(pRow, PROBATION_COL["Evaluator"]).setValue(user);
      probSheet.getRange(pRow, PROBATION_COL["Updated At"]).setValue(nowStr);
      probSheet.getRange(pRow, PROBATION_COL["SK Status"]).setValue("Pending");
    }

    // -- 4. Update Employee sheet berdasarkan keputusan --------
    // Strategy: Write ke Status Employee (kolom utama v2). Tetap tulis ke
    // Status + Employment Status JIKA kolom legacy masih ada di sheet, via
    // direct hIdx-based update (backward-compat sampai sheet fully migrated).
    var empRowNum = empRow + 1; // 1-based
    function safeSetEmp(colName, value) {
      var cNum = EMPLOYEE_COL[colName];
      if (cNum) empSheet.getRange(empRowNum, cNum).setValue(value);
    }
    if (isLulus) {
      safeSetEmp("Status Employee", "Active");
      safeSetEmp("Status", "Active");
      safeSetEmp("Employment Status", "Active");
      safeSetEmp("Updated At", nowStr);
      writeAuditLog_(
        evalData.employeeId,
        "Probation Lulus",
        "Status Employee",
        "Probation",
        "Active (Karyawan Tetap) - Eval " + evalId,
      );
    } else if (isPerpanjang) {
      if (evalData.kontrakBaruEnd)
        safeSetEmp("End Date (Contract)", evalData.kontrakBaruEnd);
      if (evalData.kontrakBaruEnd)
        safeSetEmp("Contract End", evalData.kontrakBaruEnd);
      if (evalData.kontrakBaruStart)
        safeSetEmp("Start Date (Contract)", evalData.kontrakBaruStart);
      if (evalData.kontrakBaruStart)
        safeSetEmp("Contract Start", evalData.kontrakBaruStart);
      if (evalData.durasiPerpanjang)
        safeSetEmp("Contract Duration", evalData.durasiPerpanjang);
      safeSetEmp("Updated At", nowStr);
      writeAuditLog_(
        evalData.employeeId,
        "Probation Diperpanjang",
        "End Date (Contract)",
        String(empVal("Contract End")),
        evalData.kontrakBaruEnd + " - Eval " + evalId,
      );
    }

    return {
      success: true,
      evalId: evalId,
      employeeId: evalData.employeeId,
      keputusan: evalData.keputusan,
      nilaiRataRata: avg,
      isLulus: isLulus,
      isPerpanjang: isPerpanjang,
      nowStr: nowStr,
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
