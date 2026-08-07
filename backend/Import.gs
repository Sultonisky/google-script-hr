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
      newRow[EMPLOYEE_COL["Employee ID"] - 1]         = empId;
      newRow[EMPLOYEE_COL["Recruitment ID"] - 1]      = String(row.recruitmentId || "").trim();
      newRow[EMPLOYEE_COL["Full Name"] - 1]            = String(row.fullName || "").trim();
      newRow[EMPLOYEE_COL["Position"] - 1]             = String(row.positionApplied || row.position || "").trim();
      newRow[EMPLOYEE_COL["Email"] - 1]                = String(row.email || "").trim();
      newRow[EMPLOYEE_COL["Phone"] - 1]                = row.phone ? "'" + String(row.phone).trim() : "";
      newRow[EMPLOYEE_COL["Join Date"] - 1]            = String(row.joinDate || "").trim();
      newRow[EMPLOYEE_COL["Status"] - 1]               = String(row.status || "Active").trim();
      newRow[EMPLOYEE_COL["Notes"] - 1]                = String(row.notes || "").trim();
      newRow[EMPLOYEE_COL["Created At"] - 1]           = nowStr;
      newRow[EMPLOYEE_COL["Company Entity"] - 1]       = String(row.companyEntity || "PT Mahakarya Sukses Indonesia").trim();
      newRow[EMPLOYEE_COL["Employee Type"] - 1]        = String(row.employeeType || "").trim();
      newRow[EMPLOYEE_COL["NIK"] - 1]                  = row.nik ? "'" + String(row.nik).trim() : "";
      newRow[EMPLOYEE_COL["Birth Date"] - 1]           = String(row.birthDate || "").trim();
      newRow[EMPLOYEE_COL["Age"] - 1]                  = row.age ? Number(row.age) : "";
      newRow[EMPLOYEE_COL["Gender"] - 1]               = String(row.gender || "").trim();
      newRow[EMPLOYEE_COL["Marital Status"] - 1]       = String(row.maritalStatus || "").trim();
      newRow[EMPLOYEE_COL["Address"] - 1]              = String(row.address || "").trim();
      newRow[EMPLOYEE_COL["City"] - 1]                 = String(row.city || "").trim();
      newRow[EMPLOYEE_COL["Education"] - 1]            = String(row.education || "").trim();
      newRow[EMPLOYEE_COL["Work Experience"] - 1]      = String(row.workExperience || "").trim();
      newRow[EMPLOYEE_COL["Department"] - 1]           = String(row.department || "").trim();
      newRow[EMPLOYEE_COL["Division"] - 1]             = String(row.division || "").trim();
      newRow[EMPLOYEE_COL["Branch"] - 1]               = String(row.branch || "").trim();
      newRow[EMPLOYEE_COL["Contract Start"] - 1]       = String(row.contractStart || "").trim();
      newRow[EMPLOYEE_COL["Contract End"] - 1]         = String(row.contractEnd || "").trim();
      newRow[EMPLOYEE_COL["Contract Duration"] - 1]    = String(row.contractDuration || "").trim();
      newRow[EMPLOYEE_COL["Employment Status"] - 1]    = String(row.employmentStatus || "Active").trim();
      newRow[EMPLOYEE_COL["Salary"] - 1]               = row.salary ? Number(row.salary) : "";
      newRow[EMPLOYEE_COL["Salary Type"] - 1]          = String(row.salaryType || "").trim();
      newRow[EMPLOYEE_COL["Outsource Vendor"] - 1]     = String(row.outsourceVendor || "").trim();
      newRow[EMPLOYEE_COL["Contract Number"] - 1]      = String(row.contractNumber || "").trim();
      newRow[EMPLOYEE_COL["District"] - 1]             = String(row.district || "").trim();
      newRow[EMPLOYEE_COL["Recruitment Source"] - 1]   = String(row.recruitmentSource || "CSV Import").trim();
      newRow[EMPLOYEE_COL["HR Notes"] - 1]             = String(row.hrNotes || "").trim();
      newRow[EMPLOYEE_COL["Created By"] - 1]           = "system";
      newRow[EMPLOYEE_COL["Updated At"] - 1]           = nowStr;

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
        writeAuditLog_(batchData[j][0], "Import", "Status", "-", "Active");
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
