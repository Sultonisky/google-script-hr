// ============================================================
// backend/Import.gs — BULK IMPORT KARYAWAN (Master Data)
// Validates rows and inserts into the Employee sheet.
// ============================================================

/**
 * Validates and imports an array of employee objects into the Employee sheet.
 * Each object should have keys matching the employee column headers.
 * @param {Array<Object>} rows - Array of employee data objects
 * @returns {Object} { success, imported, errors, warnings }
 */
function importEmployees(rows) {
  if (!rows || !Array.isArray(rows) || rows.length === 0) {
    return {
      success: false,
      imported: 0,
      errors: ["Tidak ada data untuk diimport."],
      warnings: [],
    };
  }

  var lock = LockService.getScriptLock();
  var warnings = [];
  var errors = [];

  try {
    lock.waitLock(30000);
  } catch (e) {
    return {
      success: false,
      imported: 0,
      errors: ["Server sedang sibuk, coba lagi."],
      warnings: [],
    };
  }

  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);
    if (!sheet) {
      return {
        success: false,
        imported: 0,
        errors: ['Sheet "' + EMPLOYEE_SHEET_NAME + '" tidak ditemukan.'],
        warnings: [],
      };
    }

    var existingIds = [];
    var lastRow = sheet.getLastRow();
    if (lastRow > 1) {
      var idData = sheet
        .getRange(2, EMPLOYEE_COL["Employee ID"], lastRow - 1, 1)
        .getValues();
      existingIds = idData.map(function (r) {
        return String(r[0]).trim().toUpperCase();
      });
    }

    var imported = 0;
    var batchData = [];

    for (var i = 0; i < rows.length; i++) {
      var row = rows[i];
      var rowNum = i + 1;

      // Required field validation
      if (!row.fullName || String(row.fullName).trim() === "") {
        errors.push("Baris " + rowNum + ": Nama lengkap wajib diisi.");
        continue;
      }

      // Generate Employee ID
      var empId = generateEmployeeId_(new Date());

      // Ensure uniqueness
      if (existingIds.indexOf(empId.toUpperCase()) !== -1) {
        warnings.push(
          "Baris " + rowNum + ": ID duplikat terdeteksi, ID baru dibuat.",
        );
        empId = generateEmployeeId_(new Date());
      }
      existingIds.push(empId.toUpperCase());

      var nowStr = Utilities.formatDate(
        new Date(),
        "GMT+7",
        "yyyy-MM-dd HH:mm:ss",
      );

      // Build row matching EMPLOYEE_HEADERS order via EMPLOYEE_COL
      var newRow = new Array(EMPLOYEE_HEADERS.length).fill("");
      newRow[EMPLOYEE_COL["Employee ID"] - 1] = empId;
      newRow[EMPLOYEE_COL["Full Name"] - 1] = String(row.fullName || "").trim();
      newRow[EMPLOYEE_COL["NIK - NPWP 16 digit"] - 1] = row.nik
        ? "'" + String(row.nik).trim()
        : "";
      newRow[EMPLOYEE_COL["NPWP"] - 1] = row.npwp
        ? "'" + String(row.npwp).trim()
        : "";
      newRow[EMPLOYEE_COL["Birth Place"] - 1] = String(
        row.birthPlace || "",
      ).trim();
      newRow[EMPLOYEE_COL["Birth Date"] - 1] = String(
        row.birthDate || "",
      ).trim();
      // Age column removed - do not import
      newRow[EMPLOYEE_COL["Gender"] - 1] = String(row.gender || "").trim();
      newRow[EMPLOYEE_COL["Religion"] - 1] = String(row.religion || "").trim();
      newRow[EMPLOYEE_COL["Marital Status"] - 1] = String(
        row.maritalStatus || "",
      ).trim();
      newRow[EMPLOYEE_COL["Blood Type"] - 1] = String(
        row.bloodType || "",
      ).trim();
      newRow[EMPLOYEE_COL["PTKP Status"] - 1] = String(
        row.ptkpStatus || "",
      ).trim();
      newRow[EMPLOYEE_COL["Citizen ID Address"] - 1] = String(
        row.citizenIdAddress || row.address || "",
      ).trim();
      newRow[EMPLOYEE_COL["Residential Address"] - 1] = String(
        row.residentialAddress || row.address || "",
      ).trim();
      newRow[EMPLOYEE_COL["Mobile Phone"] - 1] =
        row.mobilePhone || row.phone
          ? "'" + String(row.mobilePhone || row.phone).trim()
          : "";
      newRow[EMPLOYEE_COL["Personal Email"] - 1] = String(
        row.personalEmail || row.email || "",
      ).trim();
      newRow[EMPLOYEE_COL["Working Email"] - 1] = String(
        row.workingEmail || "",
      ).trim();
      newRow[EMPLOYEE_COL["Bank Name"] - 1] = String(row.bankName || "").trim();
      newRow[EMPLOYEE_COL["Bank Account"] - 1] = row.bankAccount
        ? "'" + String(row.bankAccount).trim()
        : "";
      newRow[EMPLOYEE_COL["Bank Account Holder"] - 1] = String(
        row.bankAccountHolder || row.fullName || "",
      ).trim();
      newRow[EMPLOYEE_COL["BPJS Ketenagakerjaan"] - 1] = row.bpjsKetenagakerjaan
        ? "'" + String(row.bpjsKetenagakerjaan).trim()
        : "";
      newRow[EMPLOYEE_COL["BPJS Kesehatan"] - 1] = row.bpjsKesehatan
        ? "'" + String(row.bpjsKesehatan).trim()
        : "";
      newRow[EMPLOYEE_COL["Branch Name"] - 1] = String(
        row.branchName || row.branch || "",
      ).trim();
      newRow[EMPLOYEE_COL["Division"] - 1] = String(row.division || "").trim();
      newRow[EMPLOYEE_COL["Department"] - 1] = String(
        row.department || "",
      ).trim();
      newRow[EMPLOYEE_COL["Job Position (Locaction)"] - 1] = String(
        row.positionCurrent || row.positionApplied || row.position || "",
      ).trim();
      newRow[EMPLOYEE_COL["Job Position"] - 1] = String(
        row.positionNoLocCurrent || row.position || "",
      ).trim();
      newRow[EMPLOYEE_COL["Job Level"] - 1] = String(row.jobLevel || "").trim();
      newRow[EMPLOYEE_COL["Grade"] - 1] = String(row.grade || "").trim();
      newRow[EMPLOYEE_COL["Area Kerja"] - 1] = String(
        row.areaKerja || row.district || "",
      ).trim();
      newRow[EMPLOYEE_COL["Lokasi Kerja"] - 1] = String(
        row.lokasiKerja || row.city || "",
      ).trim();
      newRow[EMPLOYEE_COL["Cost Center"] - 1] = String(
        row.costCenter || "",
      ).trim();
      newRow[EMPLOYEE_COL["Direct Superior"] - 1] = String(
        row.directSuperior || "",
      ).trim();
      newRow[EMPLOYEE_COL["Indirect Superior"] - 1] = String(
        row.indirectSuperior || "",
      ).trim();
      newRow[EMPLOYEE_COL["Status Employee"] - 1] = String(
        row.statusEmployee || row.employmentStatus || row.status || "Contract",
      ).trim();
      newRow[EMPLOYEE_COL["Join Date"] - 1] = String(row.joinDate || "").trim();
      newRow[EMPLOYEE_COL["End Date (Contract)"] - 1] = String(
        row.endDateContract || row.contractEnd || "",
      ).trim();
      newRow[EMPLOYEE_COL["Outsource Vendor"] - 1] = String(
        row.outsourceVendor || "",
      ).trim();
      newRow[EMPLOYEE_COL["Job Position (Former)"] - 1] = String(
        row.positionFormer || "",
      ).trim();
      newRow[EMPLOYEE_COL["Type of Rotation"] - 1] = String(
        row.typeOfRotation || "",
      ).trim();
      newRow[EMPLOYEE_COL["Tanggal Mutasi/Demosi/Promosi"] - 1] = String(
        row.mutasiDate || "",
      ).trim();
      newRow[EMPLOYEE_COL["Nomor SK"] - 1] = String(row.nomorSk || "").trim();
      newRow[EMPLOYEE_COL["Resign Date"] - 1] = String(
        row.resignDate || "",
      ).trim();
      newRow[EMPLOYEE_COL["HR Notes"] - 1] = String(row.hrNotes || "").trim();
      newRow[EMPLOYEE_COL["Created By"] - 1] = "system";
      newRow[EMPLOYEE_COL["Created At"] - 1] = nowStr;
      newRow[EMPLOYEE_COL["Updated At"] - 1] = nowStr;

      batchData.push(newRow);
      imported++;
    }

    // Batch write all valid rows
    if (batchData.length > 0) {
      var startRow = sheet.getLastRow() + 1;
      sheet
        .getRange(startRow, 1, batchData.length, batchData[0].length)
        .setValues(batchData);

      // Audit log for import
      for (var j = 0; j < batchData.length; j++) {
        writeAuditLog_(
          batchData[j][0],
          "Import",
          "Status Employee",
          "-",
          "Active",
        );
      }
    }

    return {
      success: imported > 0,
      imported: imported,
      errors: errors,
      warnings: warnings,
      message:
        imported +
        " karyawan berhasil diimport." +
        (errors.length > 0 ? " " + errors.length + " baris gagal." : ""),
    };
  } catch (err) {
    return {
      success: false,
      imported: 0,
      errors: ["Kesalahan server: " + err.message],
      warnings: [],
    };
  } finally {
    lock.releaseLock();
  }
}
