// ============================================================
// backend/Import.gs — BULK IMPORT KARYAWAN (Master Data)
// Validates rows and inserts into the Employee sheet.
// ============================================================

/**
 * Validates and imports an array of employee objects into the Employee sheet.
 * Each object should have keys matching the employee column headers.
 * @param {Array<Object>} rows - Array of employee data objects
 * @returns {Object} { success, imported, errors, warnings, message }
 */
function importEmployees(rows) {
  if (!rows || !Array.isArray(rows) || rows.length === 0) {
    return {
      success: false,
      imported: 0,
      errors: ["Tidak ada data untuk diimport."],
      warnings: [],
      message: "Tidak ada data untuk diimport.",
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
      errors: ["Server sedang sibuk, silakan coba lagi."],
      warnings: [],
      message: "Server sedang sibuk, silakan coba lagi.",
    };
  }

  try {
    var sheet = getOrCreateEmployeeSheet_();
    if (!sheet) {
      return {
        success: false,
        imported: 0,
        errors: ['Sheet "' + EMPLOYEE_SHEET_NAME + '" tidak dapat dibuat atau diakses.'],
        warnings: [],
        message: 'Sheet "' + EMPLOYEE_SHEET_NAME + '" tidak dapat diakses.',
      };
    }

    // Pastikan semua header dari EMPLOYEE_HEADERS ada di sheet
    ensureEmployeeHeaders_(sheet);
    SpreadsheetApp.flush();

    // Baca ulang lastCol dan headerRow setelah ensureEmployeeHeaders_
    var lastCol = sheet.getLastColumn();
    if (lastCol < EMPLOYEE_HEADERS.length) {
      lastCol = EMPLOYEE_HEADERS.length;
    }
    var headerRow = sheet.getRange(1, 1, 1, lastCol).getValues()[0];
    var colMap = {};
    headerRow.forEach(function (h, idx) {
      var headerName = String(h).trim();
      if (headerName) {
        colMap[headerName] = idx;
      }
    });

    var existingIds = [];
    var lastRow = sheet.getLastRow();
    var empIdColIdx = colMap["Employee ID"] !== undefined ? colMap["Employee ID"] + 1 : (EMPLOYEE_COL["Employee ID"] || 1);
    if (lastRow > 1) {
      var idData = sheet.getRange(2, empIdColIdx, lastRow - 1, 1).getValues();
      existingIds = idData.map(function (r) {
        return String(r[0]).trim().toUpperCase();
      });
    }

    var imported = 0;
    var batchData = [];
    var now = new Date();
    var nowStr = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");

    // Batch generate Employee IDs in one lock acquisition
    var batchIds = generateEmployeeIdBatch(rows.length);
    var idIdx = 0;

    // Helper case-insensitive value fetcher
    function getVal(obj, keys) {
      if (!obj || typeof obj !== 'object') return "";
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        if (obj[key] !== undefined && obj[key] !== null && obj[key] !== "") return String(obj[key]);
        var lowerKey = key.toLowerCase();
        for (var prop in obj) {
          if (prop.toLowerCase() === lowerKey) {
            var val = obj[prop];
            if (val !== undefined && val !== null && val !== "") return String(val);
          }
        }
      }
      return "";
    }

    for (var i = 0; i < rows.length; i++) {
      var row = rows[i];
      var rowNum = i + 1;

      // Required field validation: Full Name
      var fullName = String(row.fullName || row.name || row.nama || "").trim();
      if (!fullName) {
        errors.push("Baris " + rowNum + ": Nama lengkap wajib diisi.");
        continue;
      }

      // Determine Employee ID: prioritaskan dari file import, fallback generate
      var rawId = String(row.employeeId || row.empId || row.idKaryawan || "").trim();
      var empId;
      if (rawId) {
        if (existingIds.indexOf(rawId.toUpperCase()) !== -1) {
          errors.push("Baris " + rowNum + ": Employee ID '" + rawId + "' sudah ada di sheet.");
          continue;
        }
        empId = rawId;
      } else {
        empId = batchIds[idIdx++];
        if (!empId || existingIds.indexOf(String(empId).toUpperCase()) !== -1) {
          empId = generateEmployeeId_(new Date());
          while (existingIds.indexOf(String(empId).toUpperCase()) !== -1) {
            empId = generateEmployeeId_(new Date());
          }
        }
      }
      existingIds.push(String(empId).toUpperCase());

      // Build row array with length matching sheet columns
      var newRow = new Array(lastCol).fill("");
      function setVal(colName, val) {
        var idx = colMap[colName];
        if (idx !== undefined) {
          newRow[idx] = val;
        } else if (EMPLOYEE_COL[colName]) {
          newRow[EMPLOYEE_COL[colName] - 1] = val;
        } else {
          // Column not found in sheet - log as warning
          if (val && String(val).trim() !== "") {
            warnings.push("Baris " + rowNum + ": Kolom '" + colName + "' tidak ditemukan di sheet.");
          }
        }
      }

      setVal("Employee ID", empId);
      setVal("Full Name", fullName);
      setVal("NIK - NPWP 16 digit", row.nik ? "'" + String(row.nik).replace(/^'+/, "").trim() : "");
      setVal("NPWP", row.npwp ? "'" + String(row.npwp).replace(/^'+/, "").trim() : "");
      setVal("Birth Place", String(row.birthPlace || row.tempatLahir || "").trim());
      setVal("Birth Date", String(row.birthDate || row.tanggalLahir || "").trim());
      setVal("Gender", String(row.gender || row.jenisKelamin || "").trim());
      setVal("Religion", String(row.religion || row.agama || "").trim());
      setVal("Marital Status", String(row.maritalStatus || row.statusPernikahan || "").trim());
      setVal("Blood Type", String(row.bloodType || row.golonganDarah || "").trim());
      setVal("PTKP Status", String(row.ptkpStatus || row.ptkp || "").trim());
      setVal("Citizen ID Address", String(row.citizenIdAddress || row.alamatKtp || row.address || "").trim());
      setVal("Residential Address", String(row.residentialAddress || row.alamatDomisili || row.address || "").trim());
      
      var phoneVal = getVal(row, ['mobilePhone', 'phone', 'hp', 'noHp', 'Mobile Phone', 'Phone', 'HP', 'NoHP']);
      // Strip leading apostrophe (Excel text prefix), whitespace, and normalize +62 → 0
      phoneVal = phoneVal.replace(/^'+/, '').trim();
      setVal("Mobile Phone", phoneVal ? "'" + phoneVal : "");

      setVal("Personal Email", String(row.personalEmail || row.email || "").trim());
      setVal("Working Email", String(row.workingEmail || row.emailKantor || "").trim());
      setVal("Bank Name", String(row.bankName || row.namaBank || "BCA").trim());
      
      var bankAccVal = String(row.bankAccount || row.nomorRekening || row.rekening || "").replace(/^'+/, "").trim();
      setVal("Bank Account", bankAccVal ? "'" + bankAccVal : "");
      setVal("Bank Account Holder", String(row.bankAccountHolder || row.atasNama || fullName).trim());

      var bpjsTkVal = String(row.bpjsKetenagakerjaan || row.bpjsTk || "").replace(/^'+/, "").trim();
      setVal("BPJS Ketenagakerjaan", bpjsTkVal ? "'" + bpjsTkVal : "");

      var bpjsKesVal = String(row.bpjsKesehatan || row.bpjsKes || "").replace(/^'+/, "").trim();
      setVal("BPJS Kesehatan", bpjsKesVal ? "'" + bpjsKesVal : "");

      setVal("Branch Name", String(row.branchName || row.branch || row.cabang || "").trim());
      setVal("Division", getVal(row, ['division', 'divisi', 'Division', 'Divisi', 'div', 'bagian']));
      setVal("Department", getVal(row, ['department', 'dept', 'departemen', 'Department', 'Dept', 'Departemen', 'dept']));
      setVal("Job Position (Location)", getVal(row, ['positionCurrent', 'jobPositionLocation', 'position', 'jabatan', 'Job Position (location)', 'Job Position (Locaction)', 'posisi_lokasi']));
      setVal("Job Position", getVal(row, ['positionNoLocCurrent', 'positionNoLoc', 'jobPosition', 'position', 'Job Position', 'posisi', 'jabatan', 'JobPosition', 'job_position', 'Posisi', 'Jabatan', 'no_location']));
      setVal("Job Level", String(row.jobLevel || row.level || "").trim());
      setVal("Grade", String(row.grade || "").trim());
      setVal("Area Kerja", String(row.areaKerja || row.district || "").trim());
      setVal("Lokasi Kerja", String(row.lokasiKerja || row.city || "").trim());
      setVal("Cost Center", String(row.costCenter || "").trim());
      setVal("Direct Superior", getVal(row, ['directSuperior', 'atasanLangsung', 'Direct Superior', 'direct_superior', 'atasan_langsung']));
      setVal("Indirect Superior", getVal(row, ['indirectSuperior', 'atasanTidakLangsung', 'Indirect Superior', 'indirect_superior', 'atasan_tidak_langsung']));
      setVal("Status Employee", String(row.statusEmployee || row.employmentStatus || row.status || "Contract").trim());
      setVal("Join Date", String(row.joinDate || row.tanggalMasuk || "").trim());
      setVal("End Date (Contract)", String(row.endDateContract || row.contractEnd || row.akhirKontrak || "").trim());
      setVal("Outsource Vendor", String(row.outsourceVendor || row.vendor || "").trim());
      setVal("Job Position (Former)", String(row.positionFormer || "").trim());
      setVal("Type of Rotation", String(row.typeOfRotation || row.jenisRotasi || "").trim());
      setVal("Tanggal Mutasi/Demosi/Promosi", String(row.mutasiDate || "").trim());
      setVal("Nomor SK", String(row.nomorSk || "").trim());
      setVal("Resign Date", String(row.resignDate || row.tanggalResign || "").trim());
      setVal("HR Notes", String(row.hrNotes || row.notes || row.catatan || "").trim());
      setVal("Created By", "system");
      setVal("Created At", nowStr);
      setVal("Updated At", nowStr);

      batchData.push(newRow);
      imported++;
    }

    // Batch write all valid rows
    if (batchData.length > 0) {
      var startRow = sheet.getLastRow() + 1;
      sheet.getRange(startRow, 1, batchData.length, batchData[0].length).setValues(batchData);

      // Batch write audit logs in one setValues call
      var auditSheet = getOrCreateAuditLogSheet_();
      var auditRows = [];
      var auditUser = Session.getActiveUser().getEmail() || "HR Dashboard";
      var empIdColIdx = colMap["Employee ID"] !== undefined ? colMap["Employee ID"] : 0;
      for (var j = 0; j < batchData.length; j++) {
        auditRows.push([
          String(batchData[j][empIdColIdx] || ""),
          "Import",
          "Status Employee",
          "-",
          "Active",
          auditUser,
          nowStr,
        ]);
      }
      if (auditRows.length > 0) {
        var auditStartRow = auditSheet.getLastRow() + 1;
        auditSheet.getRange(auditStartRow, 1, auditRows.length, AUDIT_LOG_HEADERS.length).setValues(auditRows);
        SpreadsheetApp.flush();
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
    Logger.log("importEmployees ERROR: " + err);
    return {
      success: false,
      imported: 0,
      errors: ["Kesalahan server: " + err.message],
      warnings: [],
      message: "Kesalahan server: " + err.message,
    };
  } finally {
    lock.releaseLock();
  }
}
