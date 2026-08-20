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
        statusEmployee || empStatusLegacy || statusLegacy || "Contract";

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
          sval(row, "Job Position (Location)") || sval(row, "Position"),
        positionCurrent:
          sval(row, "Job Position (Location)") || sval(row, "Position"),
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
        contractStart:    sval(row, "Start Date (Contract)"),
        contractEnd:      dval(row, "End Date (Contract)") || dval(row, "Contract End"),
        contractDuration: sval(row, "Contract Duration"),
        contractNumber:   sval(row, "Contract Number"),
        positionFormer: sval(row, "Job Position (Former)"),
        typeOfRotation: sval(row, "Type of Rotation"),
        mutasiDate: dval(row, "Tanggal Mutasi/Demosi/Promosi"),
        nomorSk: sval(row, "Nomor SK"),
        resignDate: dval(row, "Resign Date"),
        hrNotes:          sval(row, "HR Notes"),
        outsourceVendor:  sval(row, "Outsource Vendor"),
        offboardingType:      sval(row, "Offboarding Type"),
        offboardingReason:    sval(row, "Offboarding Reason"),
        offboardingApprovedBy: sval(row, "Offboarding Approved By"),
        offboardingDocumentsFolder: sval(row, "Offboarding Documents Folder"),
        offboardingDocuments: parseOffboardingDocuments_(
          sval(row, "Offboarding Document Links"),
        ),
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
    newRow[EMPLOYEE_COL["Job Position (Location)"] - 1] =
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
      "Contract";
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
          oldStatusEmployee || oldEmploymentStatus || oldStatus || "Contract";

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
          positionCurrent: "Job Position (Location)",
          position: "Job Position (Location)", // legacy alias
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
          employmentStatus: "Status Employee", // legacy merge → single column
          status: "Status Employee",           // legacy merge → single column
          joinDate: "Join Date",
          contractStart:    "Start Date (Contract)",
          startDateContract:"Start Date (Contract)", // legacy alias
          contractEnd:      "End Date (Contract)",
          contractDuration: "Contract Duration",
          contractNumber:   "Contract Number",
          positionFormer:   "Job Position (Former)",
          typeOfRotation:   "Type of Rotation",
          mutasiDate:       "Tanggal Mutasi/Demosi/Promosi",
          nomorSk:          "Nomor SK",
          resignDate:       "Resign Date",
          hrNotes:          "HR Notes",
          notes:            "HR Notes",          // alias → same column
          outsourceVendor:  "Outsource Vendor",  // kolom opsional di sheet
          offboardingType:      "Offboarding Type",
          offboardingReason:    "Offboarding Reason",
          offboardingApprovedBy: "Offboarding Approved By",
          offboardingDocumentsFolder: "Offboarding Documents Folder",
          offboardingDocumentLinks: "Offboarding Document Links",
          createdBy:        "Created By",
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
        lock.releaseLock();

        return {
          success: true,
          message: "Data karyawan berhasil diperbarui.",
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
      if (s === "permanent" || s === "contract" || s === "probation" || s === "active") stats.active++;
      else stats.inactive++;

      var dept = emp.department || "Belum Ditentukan";
      stats.byDepartment[dept] = (stats.byDepartment[dept] || 0) + 1;

      var isOutsource = Boolean(
        (emp.outsourceVendor && emp.outsourceVendor.trim()) ||
        emp.createdBy === "System (Outsource Form)" ||
        s === "outsource"
      );
      var type = isOutsource ? "Outsource" : (emp.statusEmployee || "Belum Ditentukan");
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
// CREATE EMPLOYEE FROM ACCEPTED — fallback saat record Employee
// belum ada di sheet Employee ketika onboarding diproses.
// Membuat record Employee minimal dari data kandidat_accepted.
// ============================================================
function composeEmployeeJobTitles_(position, jobLevel, lokasiKerja) {
  var title = String(position || "").trim();
  var level = String(jobLevel || "").trim();
  var loc = String(lokasiKerja || "").trim();
  var noLoc = title;
  if (level && title && title.toLowerCase().indexOf(level.toLowerCase()) === -1) {
    noLoc = title + " " + level;
  } else if (!title && level) {
    noLoc = level;
  }
  var withLoc = noLoc;
  if (loc && noLoc && noLoc.indexOf("(" + loc + ")") === -1) {
    withLoc = noLoc + " (" + loc + ")";
  } else if (!noLoc && loc) {
    withLoc = loc;
  }
  return { jobPosition: noLoc, jobPositionLocation: withLoc };
}

function _createEmployeeFromAccepted_(recruitmentId, employeeId, now, nowStr, user) {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var accSheet = ss.getSheetByName(ACCEPTED_SHEET_NAME);
    if (!accSheet || accSheet.getLastRow() < 2) return -1;

    var accData = accSheet.getDataRange().getValues();
    var accHdr = accData[0];
    var accCI = {};
    accHdr.forEach(function (h, i) { accCI[String(h).trim()] = i; });

    var ridIdx = accCI["Recruitment ID"];
    if (ridIdx === undefined) return -1;

    var accRow = null;
    for (var r = 1; r < accData.length; r++) {
      if (String(accData[r][ridIdx] || "") === String(recruitmentId)) {
        accRow = accData[r];
        break;
      }
    }
    if (!accRow) return -1;

    function cv(name) {
      var i = accCI[name];
      if (i === undefined) return "";
      return String(accRow[i] || "");
    }

    var position = cv("Offering Position") || cv("Position Applied");
    var jobLevel = cv("Offering Job Level");
    var lokasiKerja = cv("Offering Lokasi Kerja") || cv("City");
    var titles = composeEmployeeJobTitles_(position, jobLevel, lokasiKerja);

    var empSheet = getOrCreateEmployeeSheet_();
    var newRow = new Array(EMPLOYEE_HEADERS.length).fill("");
    newRow[EMPLOYEE_COL["Employee ID"] - 1]       = employeeId;
    newRow[EMPLOYEE_COL["Full Name"] - 1]         = cv("Full Name");
    newRow[EMPLOYEE_COL["NIK - NPWP 16 digit"] - 1] = cv("NIK");
    newRow[EMPLOYEE_COL["NPWP"] - 1]              = "";
    newRow[EMPLOYEE_COL["Birth Place"] - 1]       = cv("City");
    newRow[EMPLOYEE_COL["Birth Date"] - 1]        = cv("Birth Date");
    newRow[EMPLOYEE_COL["Gender"] - 1]            = cv("Gender");
    newRow[EMPLOYEE_COL["Marital Status"] - 1]    = cv("Marital Status");
    newRow[EMPLOYEE_COL["Personal Email"] - 1]    = cv("Email");
    newRow[EMPLOYEE_COL["Working Email"] - 1]     = "";
    newRow[EMPLOYEE_COL["Mobile Phone"] - 1]      = cv("Phone");
    newRow[EMPLOYEE_COL["Religion"] - 1]          = "";
    newRow[EMPLOYEE_COL["Branch Name"] - 1]       = cv("Offering Company Entity");
    newRow[EMPLOYEE_COL["Division"] - 1]          = cv("Offering Division");
    newRow[EMPLOYEE_COL["Department"] - 1]        = cv("Offering Department");
    newRow[EMPLOYEE_COL["Job Position"] - 1]      = titles.jobPosition;
    newRow[EMPLOYEE_COL["Job Position (Location)"] - 1] = titles.jobPositionLocation;
    newRow[EMPLOYEE_COL["Job Level"] - 1]         = jobLevel;
    newRow[EMPLOYEE_COL["Grade"] - 1]             = cv("Offering Grade");
    newRow[EMPLOYEE_COL["Area Kerja"] - 1]        = cv("Offering Area Kerja");
    newRow[EMPLOYEE_COL["Lokasi Kerja"] - 1]      = lokasiKerja;
    newRow[EMPLOYEE_COL["Direct Superior"] - 1]   = "";
    newRow[EMPLOYEE_COL["Citizen ID Address"] - 1] = cv("Address");
    newRow[EMPLOYEE_COL["Residential Address"] - 1] = cv("Address");
    newRow[EMPLOYEE_COL["Join Date"] - 1]         = cv("Offering Join Date") ||
      Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd");
    newRow[EMPLOYEE_COL["Status Employee"] - 1]   = "Probation";
    newRow[EMPLOYEE_COL["Created By"] - 1]        = user;
    newRow[EMPLOYEE_COL["Created At"] - 1]        = nowStr;
    newRow[EMPLOYEE_COL["Updated At"] - 1]        = nowStr;
    empSheet.appendRow(newRow);
    return empSheet.getLastRow(); // 1-based row number
  } catch (e) {
    Logger.log("_createEmployeeFromAccepted_ ERROR: " + e);
    return -1;
  }
}

// ============================================================
// PROSES KONTRAK PKWT & ONBOARDING KARYAWAN
// Dipanggil dari modal di AcceptedPage (menggantikan onboarding probation langsung).
// Kandidat Accepted -> Membuat Karyawan aktif berstatus "Contract" & mengembalikan
// payload untuk download PDF Kontrak PKWT.
// ============================================================
function processContractOnboarding(
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

    // -- 0. Generate Employee ID jika belum ada (berdasarkan join date) ----
    var joinDateStr = (contractData && contractData.joinDate) ? contractData.joinDate : nowStr.substring(0, 10);
    if (!employeeId) {
      employeeId = generateEmployeeId_(new Date(joinDateStr));
    }

    // -- 1. Update / Create Employee sheet ---------------------
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
    if (empRow === -1) {
      // Fallback: buat record Employee minimal dari data kandidat_accepted
      empRow = _createEmployeeFromAccepted_(
        recruitmentId,
        employeeId,
        now,
        nowStr,
        user,
      );
      if (empRow === -1) {
        return {
          success: false,
          message: "Gagal membuat record employee untuk: " + recruitmentId,
        };
      }
    }

    contractData = contractData || {};
    var branchName =
      contractData.branchName ||
      contractData.branch ||
      contractData.companyEntity ||
      "";
    var lokasiKerja = contractData.lokasiKerja || "";
    var titles = composeEmployeeJobTitles_(
      contractData.position,
      contractData.jobLevel,
      lokasiKerja,
    );
    var joinDate =
      contractData.joinDate || contractData.contractStart || "";

    var branchCode = "MSI";
    var bLower = String(branchName || "").toLowerCase();
    if (bLower.indexOf("stein") !== -1) branchCode = "SPI";
    else if (bLower.indexOf("injeksi") !== -1) branchCode = "PII";
    else if (bLower.indexOf("mitra") !== -1 || bLower.indexOf("elektro") !== -1) branchCode = "MEP";

    // Format: NNN/Company-HR/PKWT/BulanRomawi/YYYY
    var monthOfContract = contractData.docDate ? new Date(contractData.docDate) : now;
    var contractNo = contractData.contractNumber;
    if (!contractNo) {
      var seq = generatePkwtNumber_(now);
      var romanMonth = toRomanMonth_(monthOfContract.getMonth() + 1);
      contractNo = seq + "/" + branchCode + "-HR/PKWT/" + romanMonth + "/" + monthOfContract.getFullYear();
    }
    contractData.contractNumber = contractNo;

    var empUpdates = {
      "Branch Name": branchName,
      Division: contractData.division || "",
      Department: contractData.department || "",
      "Job Position": titles.jobPosition,
      "Job Position (Location)": titles.jobPositionLocation,
      "Job Level": contractData.jobLevel || "",
      Grade: contractData.grade || "",
      "Area Kerja": contractData.areaKerja || "",
      "Lokasi Kerja": lokasiKerja,
      "Direct Superior": contractData.directSuperior || "",
      "Indirect Superior": contractData.indirectSuperior || "",
      "Cost Center": contractData.costCenter || "",
      "Working Email": contractData.workingEmail || "",
      "Join Date": joinDate,
      "End Date (Contract)": contractData.contractEnd || "",
      "Status Employee": "Contract",
      "HR Notes": contractData.notes || "",
      "Updated At": nowStr,
    };

    Object.keys(empUpdates).forEach(function (colName) {
      var colNum = EMPLOYEE_COL[colName];
      if (colNum && String(empUpdates[colName] || "").trim() !== "")
        empSheet.getRange(empRow, colNum).setValue(empUpdates[colName]);
    });

    // -- 2. Update kandidat_accepted sheet --------------------
    var accSheet = ss.getSheetByName(ACCEPTED_SHEET_NAME);
    var candidateObj = {
      fullName: "",
      nik: "",
      birthPlace: "",
      birthDate: "",
      citizenIdAddress: "",
      mobilePhone: "",
      email: "",
      recruitmentId: recruitmentId,
      employeeId: employeeId,
      branchName: branchName,
      position: titles.jobPositionLocation,
      department: contractData.department || "",
      division: contractData.division || "",
      city: lokasiKerja,
    };

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
            var rData = accData[ra];
            candidateObj.fullName = String(rData[accColIdx["Full Name"]] || "");
            candidateObj.nik = String(rData[accColIdx["NIK"]] || "");
            candidateObj.birthDate = fmtDateStr_(rData[accColIdx["Birth Date"]]);
            candidateObj.birthPlace = String(rData[accColIdx["City"]] || "");
            candidateObj.citizenIdAddress = String(rData[accColIdx["Address"]] || "");
            candidateObj.mobilePhone = String(rData[accColIdx["Phone"]] || "");
            candidateObj.email = String(rData[accColIdx["Email"]] || "");
            break;
          }
        }
      }

      if (accRow !== -1) {
        var onboardStatusCol = accColIdx["Onboarding Status"];
        var onboardDateCol = accColIdx["Onboarding Date"];
        var onboardByCol = accColIdx["Onboarding By"];
        if (onboardStatusCol !== undefined)
          accSheet.getRange(accRow, onboardStatusCol + 1).setValue("Contract");
        if (onboardDateCol !== undefined)
          accSheet.getRange(accRow, onboardDateCol + 1).setValue(nowStr);
        if (onboardByCol !== undefined)
          accSheet.getRange(accRow, onboardByCol + 1).setValue(user);
      }
    }

    // -- 3. Audit log -----------------------------------------
    writeAuditLog_(
      recruitmentId,
      "Kontrak PKWT",
      "Employment Status",
      "Accepted",
      "Contract — Employee " + employeeId + " (" + contractNo + ") by " + user,
    );

    return {
      success: true,
      employeeId: employeeId,
      recruitmentId: recruitmentId,
      contractNumber: contractNo,
      status: "Contract",
      onboardingDate: nowStr,
      onboardingBy: user,
      contractData: contractData,
      candidateData: candidateObj,
      message: "Kontrak PKWT berhasil diproses. Karyawan kini aktif berstatus Contract.",
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// Alias for backward compatibility
function processOnboardingProbation(recruitmentId, employeeId, contractData, processedBy) {
  return processContractOnboarding(recruitmentId, employeeId, contractData, processedBy);
}

// ============================================================
// AJUKAN ONBOARDING PROBATION DARI MASTER DATA EMPLOYEE
// Mengubah karyawan berstatus "Contract" menjadi "Probation"
// dan mendaftarkannya ke sheet kandidat_probation untuk evaluasi tetap.
// ============================================================
function promoteEmployeeToProbation(employeeId, probationData, processedBy) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var user = processedBy || Session.getActiveUser().getEmail() || "HR Dashboard";

    var empSheet = getOrCreateEmployeeSheet_();
    if (!empSheet || empSheet.getLastRow() < 2) {
      return { success: false, message: "Sheet Employee tidak ditemukan." };
    }

    var empData = empSheet.getDataRange().getValues();
    var empHdr = empData[0];
    var empColIdx = {};
    empHdr.forEach(function (h, i) { empColIdx[String(h).trim()] = i; });

    var empRow = -1;
    var empRec = null;
    for (var r = 1; r < empData.length; r++) {
      if (String(empData[r][empColIdx["Employee ID"]] || "").trim() === String(employeeId).trim()) {
        empRow = r + 1;
        empRec = empData[r];
        break;
      }
    }

    if (empRow === -1 || !empRec) {
      return { success: false, message: "Karyawan tidak ditemukan: " + employeeId };
    }

    probationData = probationData || {};
    var probStart = probationData.probationStart || Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd");
    var probDuration = probationData.probationDuration || "3 Bulan";
    var probEnd = probationData.probationEnd || "";
    var probNotes = probationData.notes || "";

    // Update status employee to Probation
    var statusCol = EMPLOYEE_COL["Status Employee"];
    if (statusCol) empSheet.getRange(empRow, statusCol).setValue("Probation");

    var updateCol = EMPLOYEE_COL["Updated At"];
    if (updateCol) empSheet.getRange(empRow, updateCol).setValue(nowStr);

    var notesCol = EMPLOYEE_COL["HR Notes"];
    if (notesCol && probNotes) {
      var oldNotes = String(empRec[empColIdx["HR Notes"]] || "");
      var newNotes = oldNotes ? oldNotes + " | [Ajukan Probation " + nowStr.substring(0, 10) + "] " + probNotes : "[Ajukan Probation " + nowStr.substring(0, 10) + "] " + probNotes;
      empSheet.getRange(empRow, notesCol).setValue(newNotes);
    }

    // Buat entri di sheet kandidat_probation
    var joinDate = String(empRec[empColIdx["Join Date"]] || probStart);
    var probResult = createProbationRecord(
      employeeId,
      {
        employeeId: employeeId,
        contractNumber: probationData.contractNumber || ('PRB/HRD/' + now.getFullYear() + '/' + employeeId),
        contractDuration: probDuration,
        contractStart: probStart,
        contractEnd: probEnd,
        joinDate: joinDate,
        onboardingBy: user,
      },
      { skipLock: true }
    );

    writeAuditLog_(
      employeeId,
      "Ajukan Probation",
      "Status Employee",
      "Contract",
      "Probation — Diajukan oleh " + user + (probNotes ? ' (' + probNotes + ')' : '')
    );

    return {
      success: true,
      employeeId: employeeId,
      probationId: probResult ? probResult.probationId : "",
      status: "Probation",
      message: "Karyawan " + String(empRec[empColIdx["Full Name"]] || employeeId) + " berhasil didaftarkan ke Onboarding Probation.",
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
        Position: "Job Position (Location)",
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

    var isLulus = evalData.keputusan.indexOf("Lulus") !== -1 || evalData.keputusan.indexOf("Tetap") !== -1;
    var isPutusKontrak = evalData.keputusan.indexOf("Putus Kontrak") !== -1 || evalData.keputusan.indexOf("Paklaring") !== -1;
    var isPerpanjang = !isLulus && !isPutusKontrak && (evalData.keputusan.indexOf("Perpanjang") !== -1 || evalData.keputusan.indexOf("Evaluasi Ulang") !== -1);

    // -- 3. Update row di kandidat_probation dengan hasil evaluasi -----
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
      // fallback: ambil row probation aktif milik employee ini
      for (var pi = 1; pi < probData.length; pi++) {
        var rowEmpId = String(probData[pi][PROBATION_COL["Employee ID"] - 1] || "").trim();
        var rowStatus = String(probData[pi][PROBATION_COL["Status"] - 1] || "").trim();
        if (rowEmpId === String(evalData.employeeId).trim() && (rowStatus === "Probation" || rowStatus === "Probation Extended")) {
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
    }

    // -- 4. Update Employee sheet berdasarkan keputusan --------
    var empRowNum = empRow + 1; // 1-based
    function safeSetEmp(colName, value) {
      var cNum = EMPLOYEE_COL[colName];
      if (cNum) empSheet.getRange(empRowNum, cNum).setValue(value);
    }

    var branchPrefix = String(empVal("Branch Name") || "MITO").replace(/[^a-zA-Z0-9]/g, "").substring(0, 6).toUpperCase();
    var yearStr = nowStr.substring(0, 4);

    if (isLulus) {
      var seq = generateSuratNumber_(now);
      var romanMonth = toRomanMonth_(now.getMonth() + 1);
      var skTetapNo = seq + "/HRD-PK/" + branchPrefix + "/" + romanMonth + "/" + now.getFullYear();
      safeSetEmp("Status Employee", "Permanent");
      safeSetEmp("Status", "Permanent");
      safeSetEmp("Employment Status", "Permanent");
      safeSetEmp("Nomor SK", skTetapNo);

      var prevNotes = String(empVal("HR Notes") || "");
      var newNoteLine = "[Evaluasi Probation: Lolos Karyawan Tetap] Skor: " + avg + (evalData.catatan ? " | " + evalData.catatan : "");
      safeSetEmp("HR Notes", prevNotes ? prevNotes + "\n" + newNoteLine : newNoteLine);
      safeSetEmp("Updated At", nowStr);

      writeAuditLog_(
        evalData.employeeId,
        "Probation Lulus",
        "Status Employee",
        "Probation",
        "Permanent (Karyawan Tetap) - No SK: " + skTetapNo,
      );

      // Update status di kandidat_probation
      if (probRowIndex !== -1) {
        probSheet.getRange(probRowIndex + 1, PROBATION_COL["Status"]).setValue("Completed - Passed");
        probSheet.getRange(probRowIndex + 1, PROBATION_COL["SK Status"]).setValue("SK Diterbitkan");
      }

      // Update status di kandidat_accepted (jika ada)
      try {
        var accSheet = getOrCreateAcceptedSheet_();
        var accData = accSheet.getDataRange().getValues();
        for (var a = 1; a < accData.length; a++) {
          if (String(accData[a][0] || '').trim() === String(evalData.recruitmentId || '').trim()) {
            accSheet.getRange(a + 1, 1).setValue("Completed - Passed Probation");
            break;
          }
        }
      } catch (e) {}

    } else if (isPutusKontrak) {
      var paklaringNo = "SKK/HRD/" + branchPrefix + "/" + yearStr + "/" + evalId;
      var todayDateOnly = nowStr.split(" ")[0];

      safeSetEmp("Status Employee", "Terminated");
      safeSetEmp("Status", "Terminated");
      safeSetEmp("Employment Status", "Terminated");
      safeSetEmp("Resign Date", todayDateOnly);
      safeSetEmp("Offboarding Type", "End of Probation");
      safeSetEmp("Offboarding Reason", "Tidak Lolos Evaluasi Probation");
      safeSetEmp("Nomor SK", paklaringNo);

      var prevNotes = String(empVal("HR Notes") || "");
      var newNoteLine = "[Evaluasi Probation: Putus Kontrak - Paklaring Diterbitkan] Skor: " + avg + (evalData.catatan ? " | " + evalData.catatan : "");
      safeSetEmp("HR Notes", prevNotes ? prevNotes + "\n" + newNoteLine : newNoteLine);
      safeSetEmp("Updated At", nowStr);

      writeAuditLog_(
        evalData.employeeId,
        "Probation Putus Kontrak",
        "Status Employee",
        "Probation",
        "Terminated (Paklaring Diterbitkan) - No Surat: " + paklaringNo,
      );

      // Update status di kandidat_probation
      if (probRowIndex !== -1) {
        probSheet.getRange(probRowIndex + 1, PROBATION_COL["Status"]).setValue("Terminated");
        probSheet.getRange(probRowIndex + 1, PROBATION_COL["SK Status"]).setValue("Paklaring Diterbitkan");
      }

    } else if (isPerpanjang) {
      if (evalData.kontrakBaruEnd) {
        safeSetEmp("End Date (Contract)", evalData.kontrakBaruEnd);
        safeSetEmp("Contract End", evalData.kontrakBaruEnd);
      }
      if (evalData.kontrakBaruStart) {
        safeSetEmp("Start Date (Contract)", evalData.kontrakBaruStart);
        safeSetEmp("Contract Start", evalData.kontrakBaruStart);
      }
      if (evalData.durasiPerpanjang) {
        safeSetEmp("Contract Duration", evalData.durasiPerpanjang);
      }

      var prevNotes = String(empVal("HR Notes") || "");
      var newNoteLine = "[Evaluasi Probation: Diperpanjang " + (evalData.durasiPerpanjang || "Masa Percobaan") + "] Skor: " + avg + (evalData.catatan ? " | " + evalData.catatan : "");
      safeSetEmp("HR Notes", prevNotes ? prevNotes + "\n" + newNoteLine : newNoteLine);
      safeSetEmp("Updated At", nowStr);

      writeAuditLog_(
        evalData.employeeId,
        "Probation Diperpanjang",
        "End Date (Contract)",
        String(empVal("Contract End")),
        evalData.kontrakBaruEnd + " (" + evalData.durasiPerpanjang + ") - Eval " + evalId,
      );

      // Update status di kandidat_probation
      if (probRowIndex !== -1) {
        probSheet.getRange(probRowIndex + 1, PROBATION_COL["Status"]).setValue("Extended");
        probSheet.getRange(probRowIndex + 1, PROBATION_COL["SK Status"]).setValue("Diperpanjang");
      }
    }

    return {
      success: true,
      evalId: evalId,
      employeeId: evalData.employeeId,
      keputusan: evalData.keputusan,
      nilaiRataRata: avg,
      isLulus: isLulus,
      isPutusKontrak: isPutusKontrak,
      isPerpanjang: isPerpanjang,
      nowStr: nowStr,
      skNumber: isLulus ? skTetapNo : (isPutusKontrak ? paklaringNo : ''),
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// PROCESS ROTATION — dipanggil dari modal Rotation di Employee page
// ============================================================
function processRotation(payload) {
  if (!payload || !payload.employeeId) {
    return { success: false, message: 'Employee ID wajib diisi.' };
  }
  if (!payload.rotationType) {
    return { success: false, message: 'Tipe rotasi wajib diisi.' };
  }
  if (!payload.effectiveDate) {
    return { success: false, message: 'Tanggal efektif wajib diisi.' };
  }
  if (!payload.newPosition) {
    return { success: false, message: 'Jabatan baru wajib diisi.' };
  }
  if (!payload.reason) {
    return { success: false, message: 'Alasan rotasi wajib diisi.' };
  }

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var now    = new Date();
    var nowStr = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
    var user   = Session.getActiveUser().getEmail() || 'HR Dashboard';

    var empSheet = getOrCreateEmployeeSheet_();
    var empData  = empSheet.getDataRange().getValues();
    var empHdr   = empData[0];
    var empCI    = {};
    empHdr.forEach(function(h, i) { empCI[String(h).trim()] = i; });

    var empRowIdx = -1;
    for (var r = 1; r < empData.length; r++) {
      if (String(empData[r][empCI['Employee ID']] || '').trim() === String(payload.employeeId).trim()) {
        empRowIdx = r;
        break;
      }
    }
    if (empRowIdx === -1) {
      return { success: false, message: 'Employee ID tidak ditemukan: ' + payload.employeeId };
    }
    var empRow = empData[empRowIdx];
    function ev(col) { return String(empRow[empCI[col]] || ''); }

    // Generate SK Number
    var branchPrefix = String(ev('Branch Name') || 'MITO').replace(/[^a-zA-Z0-9]/g, '').substring(0, 6).toUpperCase();
    var seq = generateSuratNumber_(now);
    var romanMonth = toRomanMonth_(now.getMonth() + 1);
    var skNumber = seq + '/HRD-SK/' + branchPrefix + '/' + romanMonth + '/' + now.getFullYear();

    // Update Employee sheet
    var empRowNum = empRowIdx + 1;
    function safeSet(col, val) { if (EMPLOYEE_COL[col]) empSheet.getRange(empRowNum, EMPLOYEE_COL[col]).setValue(val); }

    // Store old and new positions/departments
    var oldPosition = ev('Job Position (Location)') || ev('Job Position') || ev('Position');
    var oldDept = ev('Department');
    var newPosition = payload.newPosition;
    var newDept = payload.newDepartment || oldDept;

    safeSet('Job Position (Location)', newPosition);
    safeSet('Job Position', newPosition);
    if (newDept) safeSet('Department', newDept);
    safeSet('Nomor SK', skNumber);
    safeSet('Type of Rotation', payload.rotationType);
    safeSet('Job Position (Former)', oldPosition);
    safeSet('Tanggal Mutasi/Demosi/Promosi', payload.effectiveDate);

    var note = '[Rotasi ' + payload.rotationType + '] ' + oldPosition + ' → ' + newPosition + (newDept && newDept !== oldDept ? ' | Dept: ' + oldDept + ' → ' + newDept : '') + ' | Efektif: ' + payload.effectiveDate;
    var prevNotes = ev('HR Notes');
    safeSet('HR Notes', prevNotes ? prevNotes + '\n' + note : note);
    safeSet('Updated At', nowStr);

    writeAuditLog_(payload.employeeId, 'Rotasi', 'Job Position', oldPosition, newPosition + ' (' + payload.rotationType + ')');

    return {
      success: true,
      employeeId: payload.employeeId,
      skNumber: skNumber,
      oldPosition: oldPosition,
      newPosition: newPosition,
      oldDepartment: oldDept,
      newDepartment: newDept,
      rotationType: payload.rotationType,
      effectiveDate: payload.effectiveDate,
      reason: payload.reason,
      employeeData: {
        fullName: ev('Full Name'),
        nik: ev('NIK'),
        employeeId: payload.employeeId,
        branchName: ev('Branch Name'),
        position: newPosition,
        department: newDept,
        division: ev('Division'),
        joinDate: ev('Join Date'),
      },
      message: 'Rotasi berhasil diproses. SK: ' + skNumber,
    };
  } catch (err) {
    Logger.log('processRotation ERROR: ' + err);
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// OFFBOARDING DOCUMENTS — upload ke Google Drive, metadata di Employee sheet
// ============================================================
var OFFBOARDING_DOC_MAX_BYTES_ = 5 * 1024 * 1024;
var OFFBOARDING_ROOT_FOLDER_PROP_ = "OFFBOARDING_ROOT_FOLDER_ID";
var OFFBOARDING_DOC_ALLOWED_MIME_ = {
  "application/pdf": "pdf",
  "image/jpeg": "jpg",
  "image/jpg": "jpg",
  "image/png": "png",
  "application/msword": "doc",
  "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
    "docx",
};

function parseOffboardingDocuments_(val) {
  if (!val) return [];
  var str = String(val).trim();
  if (!str) return [];

  // 1. Backward compatibility: JSON format lama
  if (str.charAt(0) === '[' || str.charAt(0) === '{') {
    try {
      var parsed = JSON.parse(str);
      if (Array.isArray(parsed)) return parsed;
      if (parsed && typeof parsed === 'object') return [parsed];
    } catch (e) {
      /* parse sebagai plain URL text */
    }
  }

  // 2. Format link plain Google Drive (dipisahkan baris baru / koma)
  var lines = str.split(/[\r\n,]+/);
  var docs = [];
  lines.forEach(function (item) {
    var rawUrl = item.trim();
    if (!rawUrl) return;
    if (rawUrl.indexOf('http://') === 0 || rawUrl.indexOf('https://') === 0 || rawUrl.indexOf('drive.google.com') !== -1) {
      docs.push({
        type: 'Dokumen Offboarding',
        fileName: 'Buka Dokumen di Google Drive',
        driveFileName: 'Google Drive File',
        url: rawUrl,
      });
    }
  });
  return docs;
}

function validateOffboardingDocuments_(offboardingType, documents) {
  documents = documents || [];
  if (offboardingType === "Death") {
    var hasDeathCert = documents.some(function (d) {
      return (
        d &&
        String(d.type || "").trim() === "Surat Kematian" &&
        d.dataBase64
      );
    });
    if (!hasDeathCert) {
      return {
        valid: false,
        message:
          "Tipe Meninggal wajib melampirkan dokumen Surat Kematian.",
      };
    }
  }
  for (var i = 0; i < documents.length; i++) {
    var doc = documents[i];
    if (!doc || !doc.type || !doc.fileName || !doc.dataBase64) {
      return {
        valid: false,
        message: "Setiap dokumen wajib memiliki tipe, nama file, dan isi file.",
      };
    }
    var bytes = Utilities.base64Decode(doc.dataBase64).length;
    if (bytes > OFFBOARDING_DOC_MAX_BYTES_) {
      return {
        valid: false,
        message:
          'File "' + doc.fileName + '" melebihi batas 5 MB.',
      };
    }
    if (
      doc.mimeType &&
      !OFFBOARDING_DOC_ALLOWED_MIME_[String(doc.mimeType).toLowerCase()]
    ) {
      return {
        valid: false,
        message:
          'Format file "' +
          doc.fileName +
          '" tidak didukung. Gunakan PDF, JPG, PNG, DOC, atau DOCX.',
      };
    }
  }
  return { valid: true };
}

function sanitizeDriveName_(name) {
  return (
    String(name || "Unknown")
      .replace(/[^\w\s\-_.]/g, "")
      .replace(/\s+/g, "_")
      .substring(0, 80) || "Unknown"
  );
}

function getSpreadsheetParentFolder_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var file = DriveApp.getFileById(ss.getId());
  var parents = file.getParents();
  if (parents.hasNext()) return parents.next();
  return DriveApp.getRootFolder();
}

function getOrCreateOffboardingRootFolder_() {
  var props = PropertiesService.getScriptProperties();
  var cachedId = props.getProperty(OFFBOARDING_ROOT_FOLDER_PROP_);
  if (cachedId) {
    try {
      return DriveApp.getFolderById(cachedId);
    } catch (e) {
      props.deleteProperty(OFFBOARDING_ROOT_FOLDER_PROP_);
    }
  }

  var parent = getSpreadsheetParentFolder_();
  var iter = parent.getFoldersByName("MITO HRIS Offboarding");
  var folder = iter.hasNext() ? iter.next() : parent.createFolder("MITO HRIS Offboarding");
  props.setProperty(OFFBOARDING_ROOT_FOLDER_PROP_, folder.getId());
  return folder;
}

function formatDriveAuthError_(err) {
  var msg = String((err && err.message) || err || "");
  if (
    msg.indexOf("Izin") !== -1 ||
    msg.indexOf("permission") !== -1 ||
    msg.indexOf("Authorization") !== -1 ||
    msg.indexOf("DriveApp") !== -1
  ) {
    return (
      "Izin Google Drive belum diberikan. Buka Apps Script → jalankan fungsi " +
      "authorizeOffboardingDrive() → setujui akses Drive, lalu deploy ulang web app " +
      "versi baru sebagai user yang sama."
    );
  }
  return msg;
}

/**
 * Jalankan sekali dari Apps Script Editor untuk meminta izin Drive
 * setelah scope di appsscript.json diperbarui.
 */
function authorizeOffboardingDrive() {
  var folder = getOrCreateOffboardingRootFolder_();
  return (
    "OK — folder offboarding siap: " +
    folder.getName() +
    " (" +
    folder.getUrl() +
    ")"
  );
}

function getOrCreateEmployeeOffboardingFolder_(
  employeeId,
  fullName,
  existingFolderUrl,
) {
  if (existingFolderUrl) {
    try {
      var match = String(existingFolderUrl).match(/[-\w]{25,}/);
      if (match) {
        var folder = DriveApp.getFolderById(match[0]);
        if (folder) return folder;
      }
    } catch (e) {
      /* fallback create */
    }
  }
  var root = getOrCreateOffboardingRootFolder_();
  var folderName =
    sanitizeDriveName_(employeeId) + "_" + sanitizeDriveName_(fullName);
  var iter = root.getFoldersByName(folderName);
  if (iter.hasNext()) return iter.next();
  return root.createFolder(folderName);
}

function uploadOffboardingDocuments_(
  employeeId,
  fullName,
  documents,
  existingFolderUrl,
  existingLinksVal,
  uploadedBy,
) {
  if (!documents || !documents.length) {
    return {
      folderUrl: existingFolderUrl || "",
      linksString: existingLinksVal || "",
      linksJson: existingLinksVal || "",
      uploaded: [],
    };
  }
  var folder = getOrCreateEmployeeOffboardingFolder_(
    employeeId,
    fullName,
    existingFolderUrl,
  );
  var nowStr = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
  var existing = parseOffboardingDocuments_(existingLinksVal);
  var uploaded = [];
  var newUrls = [];

  documents.forEach(function (doc) {
    var bytes = Utilities.base64Decode(doc.dataBase64);
    var mime = String(doc.mimeType || "application/octet-stream").toLowerCase();
    var ext = OFFBOARDING_DOC_ALLOWED_MIME_[mime] || "bin";
    var safeType = sanitizeDriveName_(doc.type);
    var safeName = sanitizeDriveName_(doc.fileName).replace(/\.[^.]+$/, "");
    var fileName =
      safeType +
      "_" +
      Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd_HHmmss") +
      "_" +
      safeName +
      "." +
      ext;
    var blob = Utilities.newBlob(bytes, mime, fileName);
    var file = folder.createFile(blob);
    var fileUrl = file.getUrl();
    newUrls.push(fileUrl);
    uploaded.push({
      type: doc.type,
      fileName: doc.fileName,
      driveFileName: fileName,
      url: fileUrl,
      fileId: file.getId(),
      uploadedAt: nowStr,
      uploadedBy: uploadedBy || "HR Dashboard",
    });
  });

  // Ambil semua URL Google Drive (hanya link bersih, tanpa metadata JSON developer)
  var existingUrls = existing
    .map(function (d) {
      return d.url || (typeof d === "string" ? d : "");
    })
    .filter(Boolean);
  var allUrls = existingUrls.concat(newUrls);
  var cleanLinksText = allUrls.join("\n");

  var merged = existing.concat(uploaded);
  return {
    folderUrl: folder.getUrl(),
    linksString: cleanLinksText,
    linksJson: cleanLinksText,
    mergedDocs: merged,
    uploaded: uploaded,
  };
}

// ============================================================
// PROCESS OFFBOARDING — dipanggil dari modal Offboarding di Employee page
//
// payload: {
//   employeeId, offboardingType, lastWorkingDate, reason,
//   bpjsKetenagakerjaan, bpjsKesehatan, paklaring,
//   approvedBy, notes, skNumber,
//   documents: [{ type, fileName, mimeType, dataBase64 }]
// }
// ============================================================
function processOffboarding(payload) {
  if (!payload || !payload.employeeId) {
    return { success: false, message: 'Employee ID wajib diisi.' };
  }
  if (!payload.offboardingType) {
    return { success: false, message: 'Tipe offboarding wajib diisi.' };
  }
  if (!payload.lastWorkingDate) {
    return { success: false, message: 'Last Working Date wajib diisi.' };
  }
  if (!payload.reason) {
    return { success: false, message: 'Alasan offboarding wajib diisi.' };
  }

  var docValidation = validateOffboardingDocuments_(
    payload.offboardingType,
    payload.documents || [],
  );
  if (!docValidation.valid) {
    return { success: false, message: docValidation.message };
  }

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var now    = new Date();
    var nowStr = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
    var user   = Session.getActiveUser().getEmail() || 'HR Dashboard';
    var approvedBy = payload.approvedBy || user;

    // -- 1. Ambil data employee -----------------------------------
    var empSheet = getOrCreateEmployeeSheet_();
    var empData  = empSheet.getDataRange().getValues();
    var empHdr   = empData[0];
    var empCI    = {};
    empHdr.forEach(function(h, i) { empCI[String(h).trim()] = i; });

    var empRowIdx = -1;
    for (var r = 1; r < empData.length; r++) {
      if (String(empData[r][empCI['Employee ID']] || '').trim() === String(payload.employeeId).trim()) {
        empRowIdx = r;
        break;
      }
    }
    if (empRowIdx === -1) {
      return { success: false, message: 'Employee ID tidak ditemukan: ' + payload.employeeId };
    }

    var empRow = empData[empRowIdx];
    function ev(col) {
      var idx = empCI[col];
      if (idx !== undefined) return String(empRow[idx] || '');
      // fallback untuk header lama
      var fb = {
        'Position': ['Job Position (Location)', 'Job Position'],
        'NIK': ['NIK - NPWP 16 digit'],
      };
      if (fb[col]) {
        for (var i = 0; i < fb[col].length; i++) {
          var j = empCI[fb[col][i]];
          if (j !== undefined) return String(empRow[j] || '');
        }
      }
      return '';
    }

    // -- 2. Tentukan status baru berdasarkan tipe offboarding ----
    var statusMap = {
      'Resignation':        'Resigned',
      'Termination':        'Terminated',
      'Retirement':         'Retired',
      'Contract End':       'Inactive',
      'Contract Finished':  'Inactive',
      'On Leave':           'On Leave',
      'Death':              'Deceased',
    };
    var newStatus = statusMap[payload.offboardingType] || 'Inactive';
    var oldStatus = ev('Status Employee') || ev('Status') || 'Active';

    // -- 3. Update Employee sheet --------------------------------
    var empRowNum = empRowIdx + 1; // 1-based
    function safeSet(colName, value) {
      var colNum = EMPLOYEE_COL[colName];
      if (colNum) empSheet.getRange(empRowNum, colNum).setValue(value);
    }
    safeSet('Status Employee', newStatus);
    safeSet('Resign Date',     payload.lastWorkingDate);

    // Simpan posisi terakhir ke Job Position (Former) — hanya jika belum terisi
    var currentPosition = ev('Job Position (Location)') || ev('Job Position') || '';
    if (currentPosition && !ev('Job Position (Former)')) {
      safeSet('Job Position (Former)', currentPosition);
    }

    var branchPrefix = String(ev('Branch Name') || 'MITO').replace(/[^a-zA-Z0-9]/g, '').substring(0, 6).toUpperCase();
    var skNumber = String(payload.skNumber || '').trim();
    if (!skNumber) {
      var seq = generateSkOffNumber_(now);
      var romanMonth = toRomanMonth_(now.getMonth() + 1);
      skNumber = seq + '/HRD-SKK/' + branchPrefix + '/' + romanMonth + '/' + now.getFullYear();
    }
    safeSet('Nomor SK',        skNumber);
    safeSet('Offboarding Type', payload.offboardingType);
    safeSet('Offboarding Reason', payload.reason);
    safeSet('Offboarding Approved By', approvedBy);
    if (payload.bpjsKetenagakerjaan) {
      safeSet('BPJS Ketenagakerjaan', payload.bpjsKetenagakerjaan);
    }
    if (payload.bpjsKesehatan) {
      safeSet('BPJS Kesehatan', payload.bpjsKesehatan);
    }

    // -- Catatan HR: hanya ditambahkan jika ada catatan atau paklaring ditentukan khusus --
    var offbNotes = String(payload.notes || '').trim();
    var pakVal = String(payload.paklaring || '').trim();
    var isPaklaringSpecified = pakVal && pakVal !== 'Belum Ditentukan' && pakVal !== '—' && pakVal !== '-';

    var noteParts = [];
    if (offbNotes) {
      noteParts.push(offbNotes);
    }
    if (isPaklaringSpecified) {
      noteParts.push('Paklaring: ' + pakVal);
    }

    if (noteParts.length > 0) {
      var prevNotes = ev('HR Notes');
      var noteLine = '[Offboarding ' + nowStr + '] ' + noteParts.join(' | ');
      safeSet('HR Notes', prevNotes ? prevNotes + '\n' + noteLine : noteLine);
    }

    safeSet('Updated At',      nowStr);

    // -- 4. Upload dokumen offboarding ke Drive ------------------
    var existingFolder = ev('Offboarding Documents Folder');
    var existingLinks = ev('Offboarding Document Links');
    var uploadResult = uploadOffboardingDocuments_(
      payload.employeeId,
      ev('Full Name'),
      payload.documents || [],
      existingFolder,
      existingLinks,
      approvedBy,
    );
    if (uploadResult.folderUrl) {
      safeSet('Offboarding Documents Folder', uploadResult.folderUrl);
    }
    if (uploadResult.linksString || uploadResult.linksJson) {
      safeSet('Offboarding Document Links', uploadResult.linksString || uploadResult.linksJson);
    }

    // -- 5. Audit log --------------------------------------------
    writeAuditLog_(
      payload.employeeId,
      'Offboarding',
      'Status Employee',
      oldStatus,
      newStatus + ' — ' + payload.offboardingType + ' (SK: ' + skNumber + ')'
    );

    if (uploadResult.uploaded && uploadResult.uploaded.length) {
      var docSummary = uploadResult.uploaded
        .map(function (d) {
          return d.type + ': ' + d.fileName;
        })
        .join('; ');
      writeAuditLog_(
        payload.employeeId,
        'Offboarding Document',
        'Offboarding Document Links',
        existingLinks ? String(existingLinks).substring(0, 200) : '-',
        docSummary,
      );
    }

    return {
      success:       true,
      employeeId:    payload.employeeId,
      newStatus:     newStatus,
      skNumber:      skNumber,
      employeeData: {
        employeeId:      payload.employeeId,
        fullName:        ev('Full Name'),
        nik:             ev('NIK'),
        position:        ev('Position'),
        department:      ev('Department'),
        division:        ev('Division'),
        branchName:      ev('Branch Name') || ev('Branch'),
        joinDate:        ev('Join Date'),
        lastWorkingDate: payload.lastWorkingDate,
        offboardingType: payload.offboardingType,
        reason:          payload.reason,
        paklaring:       payload.paklaring,
        skNumber:        skNumber,
        approvedBy:      approvedBy,
        notes:           payload.notes || '',
        prevStatus:      oldStatus,
        isPermanent:     (String(oldStatus).toLowerCase().indexOf('permanent') !== -1 || String(oldStatus).toLowerCase().indexOf('pkwtt') !== -1 || String(oldStatus).toLowerCase().indexOf('tetap') !== -1),
      },
      documentsUploaded: (uploadResult.uploaded || []).length,
      documentsFolder: uploadResult.folderUrl || existingFolder,
      message:       'Offboarding berhasil diproses. Status karyawan diubah ke "' + newStatus + '".',
    };
  } catch (err) {
    Logger.log('processOffboarding ERROR: ' + err);
    return { success: false, message: formatDriveAuthError_(err) };
  } finally {
    lock.releaseLock();
  }
}
