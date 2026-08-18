// ============================================================
// backend/Outsource.gs — OUTSOURCE EMPLOYEE REGISTRATION
// Flow: Form → Employee sheet
// ============================================================

// ---- Validasi form OS ----
function validateOutsourceForm_(f) {
  if (!f.full_name || f.full_name.trim().length < 3)
    return "Nama lengkap wajib diisi (minimal 3 karakter).";
  if (!f.nik || !/^[0-9]{16}$/.test(f.nik))
    return "NIK harus tepat 16 digit angka.";
  if (!f.birth_date) return "Tanggal lahir wajib diisi.";
  if (!f.birth_place || f.birth_place.trim() === "")
    return "Tempat lahir wajib diisi.";
  if (!f.gender) return "Jenis kelamin wajib dipilih.";
  if (!f.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email))
    return "Format email tidak valid.";
  if (!f.phone || !/^62[0-9]{8,12}$/.test(f.phone))
    return "Nomor HP tidak valid.";
  if (!f.address || f.address.trim() === "") return "Alamat KTP wajib diisi.";
  if (!f.lokasi_kerja || f.lokasi_kerja.trim() === "")
    return "Lokasi kerja wajib diisi.";
  if (!f.outsource_vendor || f.outsource_vendor.trim().length < 3)
    return "Vendor outsource wajib diisi (minimal 3 karakter).";
  if (!f.position || f.position.trim() === "")
    return "Posisi/jabatan wajib diisi.";
  if (!f.join_date) return "Tanggal masuk wajib diisi.";
  if (!f.employee_status) return "Status karyawan wajib dipilih.";
  return null;
}

// ---- Generate Outsource ID: OSM-YYYY-XXXX ----
function generateOutsourceId_(now) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var props = PropertiesService.getScriptProperties();
    var year = Utilities.formatDate(now, "GMT+7", "yyyy");
    var key = "OS_COUNTER_" + year;
    var counter = parseInt(props.getProperty(key) || "0", 10) + 1;
    props.setProperty(key, String(counter));
    return "OSM-" + year + "-" + String(counter).padStart(4, "0");
  } finally {
    lock.releaseLock();
  }
}

// ---- Simpan data OS langsung ke sheet Employee ----
function simpanDataOutsource(formObject) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var validationError = validateOutsourceForm_(formObject);
    if (validationError) {
      lock.releaseLock();
      return "Error: " + validationError;
    }

    var nikCheck = isNikExists_(formObject.nik);
    if (nikCheck.found) {
      lock.releaseLock();
      return (
        "Error: NIK ini sudah terdaftar dengan status " +
        nikCheck.status +
        ". Tidak dapat mendaftar lagi."
      );
    }

    var now = new Date();
    var employeeId = generateEmployeeId_(now);
    var createdAt = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");

    var sheet = getOrCreateEmployeeSheet_();

    var newRow = new Array(EMPLOYEE_HEADERS.length).fill("");
    newRow[EMPLOYEE_COL["Employee ID"] - 1] = employeeId;
    newRow[EMPLOYEE_COL["Full Name"] - 1] = formObject.full_name;
    newRow[EMPLOYEE_COL["Branch Name"] - 1] = formObject.branch_name || "";
    newRow[EMPLOYEE_COL["Division"] - 1] = formObject.division || "";
    newRow[EMPLOYEE_COL["Department"] - 1] = formObject.department || "";
    newRow[EMPLOYEE_COL["Job Position (Locaction)"] - 1] = formObject.position;
    newRow[EMPLOYEE_COL["Personal Email"] - 1] = formObject.email;
    newRow[EMPLOYEE_COL["Working Email"] - 1] = formObject.working_email || "";
    newRow[EMPLOYEE_COL["Area Kerja"] - 1] = formObject.area_kerja || "";
    newRow[EMPLOYEE_COL["Lokasi Kerja"] - 1] = formObject.lokasi_kerja;
    newRow[EMPLOYEE_COL["Job Level"] - 1] = formObject.job_level || "";
    newRow[EMPLOYEE_COL["Join Date"] - 1] = formObject.join_date;
    newRow[EMPLOYEE_COL["Status Employee"] - 1] =
      formObject.employee_status || "Probation";
    newRow[EMPLOYEE_COL["Direct Superior"] - 1] =
      formObject.direct_superior || "";
    newRow[EMPLOYEE_COL["Indirect Superior"] - 1] =
      formObject.indirect_superior || "";
    newRow[EMPLOYEE_COL["Personal Email"] - 1] = formObject.email;
    newRow[EMPLOYEE_COL["End Date (Contract)"] - 1] =
      formObject.contract_end_date || "";
    newRow[EMPLOYEE_COL["Birth Place"] - 1] = formObject.birth_place || "";
    newRow[EMPLOYEE_COL["Birth Date"] - 1] = formObject.birth_date;
    newRow[EMPLOYEE_COL["Citizen ID Address"] - 1] = formObject.address;
    newRow[EMPLOYEE_COL["Residential Address"] - 1] =
      formObject.address_residential || formObject.address || "";
    newRow[EMPLOYEE_COL["NIK - NPWP 16 digit"] - 1] = "'" + formObject.nik;
    newRow[EMPLOYEE_COL["NPWP"] - 1] = formObject.npwp
      ? "'" + formObject.npwp
      : "";
    newRow[EMPLOYEE_COL["PTKP Status"] - 1] = formObject.ptkp_status || "";
    newRow[EMPLOYEE_COL["Bank Name"] - 1] = "BCA";
    newRow[EMPLOYEE_COL["Bank Account"] - 1] = formObject.bank_account
      ? "'" + formObject.bank_account
      : "";
    newRow[EMPLOYEE_COL["Bank Account Holder"] - 1] =
      formObject.bank_account_holder || "";
    newRow[EMPLOYEE_COL["BPJS Ketenagakerjaan"] - 1] =
      formObject.bpjs_ketenagakerjaan
        ? "'" + formObject.bpjs_ketenagakerjaan
        : "";
    newRow[EMPLOYEE_COL["BPJS Kesehatan"] - 1] = formObject.bpjs_kesehatan
      ? "'" + formObject.bpjs_kesehatan
      : "";
    newRow[EMPLOYEE_COL["Mobile Phone"] - 1] = "'" + formObject.phone;
    newRow[EMPLOYEE_COL["Religion"] - 1] = formObject.religion || "";
    newRow[EMPLOYEE_COL["Gender"] - 1] = formObject.gender;
    newRow[EMPLOYEE_COL["Marital Status"] - 1] =
      formObject.marital_status || "";
    newRow[EMPLOYEE_COL["Blood Type"] - 1] = formObject.blood_type || "";
    newRow[EMPLOYEE_COL["Cost Center"] - 1] = formObject.cost_center || "";
    newRow[EMPLOYEE_COL["Outsource Vendor"] - 1] =
      formObject.outsource_vendor || formObject.vendor || "";
    newRow[EMPLOYEE_COL["Created By"] - 1] = "System (Outsource Form)";
    newRow[EMPLOYEE_COL["Created At"] - 1] = createdAt;
    newRow[EMPLOYEE_COL["Updated At"] - 1] = createdAt;

    sheet.appendRow(newRow);

    writeAuditLog_(
      employeeId,
      "Created",
      "Status Employee",
      "-",
      formObject.employee_status || "Probation",
    );
    return "Sukses";
  } catch (error) {
    return "Error: " + error.toString();
  } finally {
    lock.releaseLock();
  }
}

// ---- Helper: apakah baris Employee termasuk outsource ----
function isOutsourceEmployeeRow_(row, colIndex) {
  function gc(name) {
    var i = colIndex[name];
    return i !== undefined ? String(row[i] || "").trim() : "";
  }
  if (gc("Outsource Vendor")) return true;
  if (gc("Created By") === "System (Outsource Form)") return true;
  return false;
}

// ---- Get outsource list (Employee sheet — kolom Outsource Vendor terisi) ----
function getOutsourceList() {
  try {
    var sheet =
      SpreadsheetApp.getActiveSpreadsheet().getSheetByName(EMPLOYEE_SHEET_NAME);
    if (!sheet || sheet.getLastRow() < 2) return [];

    var data = sheet.getDataRange().getValues();
    var headers = data[0];

    var colIndex = {};
    headers.forEach(function (h, i) {
      colIndex[String(h).trim()] = i;
    });

    var result = [];
    for (var r = 1; r < data.length; r++) {
      var row = data[r];
      if (!row.join("").toString().trim()) continue;

      function gc(name) {
        var i = colIndex[name];
        if (i !== undefined) return String(row[i] || "");
        return "";
      }
      function gcd(name) {
        var i = colIndex[name];
        return i === undefined ? "" : fmtDateStr_(row[i]);
      }

      if (!isOutsourceEmployeeRow_(row, colIndex)) continue;

      var createdAtRaw =
        colIndex["Created At"] !== undefined ? row[colIndex["Created At"]] : "";
      var updatedAtRaw =
        colIndex["Updated At"] !== undefined ? row[colIndex["Updated At"]] : "";

      result.push({
        employeeId: gc("Employee ID"),
        fullName: gc("Full Name"),
        nik: gc("NIK - NPWP 16 digit").replace(/^'/, ""),
        birthDate: gcd("Birth Date"),
        birthPlace: gc("Birth Place"),
        age: gc("Age"),
        gender: gc("Gender"),
        religion: gc("Religion"),
        bloodType: gc("Blood Type"),
        maritalStatus: gc("Marital Status"),
        email: gc("Personal Email"),
        phone: gc("Mobile Phone").replace(/^'/, ""),
        address: gc("Citizen ID Address"),
        addressResidential: gc("Residential Address"),
        city: gc("Lokasi Kerja"),
        branchName: gc("Branch Name"),
        division: gc("Division"),
        department: gc("Department"),
        areaKerja: gc("Area Kerja"),
        lokasiKerja: gc("Lokasi Kerja"),
        costCenter: gc("Cost Center"),
        position: gc("Job Position (Locaction)") || gc("Job Position"),
        jobLevel: gc("Job Level"),
        employeeStatus: gc("Status Employee"),
        status: gc("Status Employee"),
        employmentStatus: gc("Status Employee"),
        outsourceVendor: gc("Outsource Vendor"),
        joinDate: gcd("Join Date"),
        contractEndDate: gcd("End Date (Contract)"),
        directSuperior: gc("Direct Superior"),
        indirectSuperior: gc("Indirect Superior"),
        bankName: gc("Bank Name"),
        bankAccount: gc("Bank Account").replace(/^'/, ""),
        bankAccountHolder: gc("Bank Account Holder"),
        npwp: gc("NPWP").replace(/^'/, ""),
        ptkpStatus: gc("PTKP Status"),
        bpjsKetenagakerjaan: gc("BPJS Ketenagakerjaan").replace(/^'/, ""),
        bpjsKesehatan: gc("BPJS Kesehatan").replace(/^'/, ""),
        createdBy: gc("Created By"),
        updatedAt:
          updatedAtRaw instanceof Date
            ? Utilities.formatDate(updatedAtRaw, "GMT+7", "dd/MM/yyyy HH:mm")
            : fmtDateStr_(String(updatedAtRaw || "")),
        createdDate:
          createdAtRaw instanceof Date
            ? Utilities.formatDate(createdAtRaw, "GMT+7", "dd/MM/yyyy HH:mm")
            : fmtDateStr_(String(createdAtRaw || "")),
      });
    }

    result.sort(function (a, b) {
      return (b.createdDate || "").localeCompare(a.createdDate || "");
    });

    return result;
  } catch (error) {
    return [];
  }
}

// ---- Get single outsource employee by Employee ID ----
function getOutsourceById(id) {
  try {
    var sheet =
      SpreadsheetApp.getActiveSpreadsheet().getSheetByName(EMPLOYEE_SHEET_NAME);
    if (!sheet || sheet.getLastRow() < 2)
      return { success: false, message: "Sheet Employee tidak ditemukan." };

    var data = sheet.getDataRange().getValues();
    var headers = data[0];
    var colIndex = {};
    headers.forEach(function (h, i) {
      colIndex[String(h).trim()] = i;
    });

    for (var r = 1; r < data.length; r++) {
      if (String(data[r][colIndex["Employee ID"]] || "") !== String(id))
        continue;
      if (!isOutsourceEmployeeRow_(data[r], colIndex))
        return {
          success: false,
          message: "Bukan karyawan outsource.",
        };

      var row = data[r];
      return {
        success: true,
        data: {
          employeeId: String(row[colIndex["Employee ID"]] || ""),
          fullName: String(row[colIndex["Full Name"]] || ""),
          nik: String(row[colIndex["NIK - NPWP 16 digit"]] || "").replace(
            /^'/,
            "",
          ),
          birthDate: fmtDateStr_(row[colIndex["Birth Date"]]),
          birthPlace: String(row[colIndex["Birth Place"]] || ""),
          age: String(row[colIndex["Age"]] || ""),
          gender: String(row[colIndex["Gender"]] || ""),
          religion: String(row[colIndex["Religion"]] || ""),
          bloodType: String(row[colIndex["Blood Type"]] || ""),
          maritalStatus: String(row[colIndex["Marital Status"]] || ""),
          email: String(row[colIndex["Personal Email"]] || ""),
          phone: String(row[colIndex["Mobile Phone"]] || "").replace(/^'/, ""),
          address: String(row[colIndex["Citizen ID Address"]] || ""),
          addressResidential: String(
            row[colIndex["Residential Address"]] || "",
          ),
          city: String(row[colIndex["Lokasi Kerja"]] || ""),
          branchName: String(row[colIndex["Branch Name"]] || ""),
          division: String(row[colIndex["Division"]] || ""),
          department: String(row[colIndex["Department"]] || ""),
          areaKerja: String(row[colIndex["Area Kerja"]] || ""),
          lokasiKerja: String(row[colIndex["Lokasi Kerja"]] || ""),
          costCenter: String(row[colIndex["Cost Center"]] || ""),
          position: String(
            row[colIndex["Job Position (Locaction)"]] ||
              row[colIndex["Job Position"]] ||
              "",
          ),
          jobLevel: String(row[colIndex["Job Level"]] || ""),
          employeeStatus: String(row[colIndex["Status Employee"]] || ""),
          joinDate: fmtDateStr_(row[colIndex["Join Date"]]),
          contractEndDate: fmtDateStr_(row[colIndex["End Date (Contract)"]]),
          directSuperior: String(row[colIndex["Direct Superior"]] || ""),
          indirectSuperior: String(row[colIndex["Indirect Superior"]] || ""),
          bankName: String(row[colIndex["Bank Name"]] || ""),
          bankAccount: String(row[colIndex["Bank Account"]] || "").replace(
            /^'/,
            "",
          ),
          bankAccountHolder: String(row[colIndex["Bank Account Holder"]] || ""),
          npwp: String(row[colIndex["NPWP"]] || "").replace(/^'/, ""),
          ptkpStatus: String(row[colIndex["PTKP Status"]] || ""),
          bpjsKetenagakerjaan: String(
            row[colIndex["BPJS Ketenagakerjaan"]] || "",
          ).replace(/^'/, ""),
          bpjsKesehatan: String(row[colIndex["BPJS Kesehatan"]] || "").replace(
            /^'/,
            "",
          ),
          createdBy: String(row[colIndex["Created By"]] || ""),
          createdDate:
            row[colIndex["Created At"]] instanceof Date
              ? Utilities.formatDate(
                  row[colIndex["Created At"]],
                  "GMT+7",
                  "dd/MM/yyyy HH:mm",
                )
              : fmtDateStr_(String(row[colIndex["Created At"]] || "")),
          updatedAt:
            row[colIndex["Updated At"]] instanceof Date
              ? Utilities.formatDate(
                  row[colIndex["Updated At"]],
                  "GMT+7",
                  "dd/MM/yyyy HH:mm",
                )
              : fmtDateStr_(String(row[colIndex["Updated At"]] || "")),
        },
      };
    }

    return { success: false, message: "Employee ID tidak ditemukan: " + id };
  } catch (error) {
    return { success: false, message: error.toString() };
  }
}

// ---- Update outsource employee ----
function updateOutsourceStatus(id, status, notes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet =
      SpreadsheetApp.getActiveSpreadsheet().getSheetByName(EMPLOYEE_SHEET_NAME);
    if (!sheet || sheet.getLastRow() < 2)
      return { success: false, message: "Sheet Employee tidak ditemukan." };

    var data = sheet.getDataRange().getValues();
    var headers = data[0];
    var colIndex = {};
    headers.forEach(function (h, i) {
      colIndex[String(h).trim()] = i;
    });

    for (var r = 1; r < data.length; r++) {
      if (String(data[r][colIndex["Employee ID"]] || "") !== String(id))
        continue;
      if (!isOutsourceEmployeeRow_(data[r], colIndex))
        return {
          success: false,
          message: "Bukan karyawan outsource.",
        };

      var oldStatus = String(data[r][colIndex["Status Employee"]] || "");
      var now = Utilities.formatDate(
        new Date(),
        "GMT+7",
        "yyyy-MM-dd HH:mm:ss",
      );

      if (status) {
        sheet.getRange(r + 1, colIndex["Status Employee"] + 1).setValue(status);
      }
      sheet.getRange(r + 1, colIndex["Updated At"] + 1).setValue(now);

      writeAuditLog_(
        id,
        "Update Status",
        "Status Employee",
        oldStatus,
        status || oldStatus,
      );
      return { success: true, employeeId: id, newStatus: status };
    }

    return { success: false, message: "Employee ID tidak ditemukan: " + id };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Delete outsource employee by Employee ID ----
function deleteOutsourceById(id) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var sheet =
      SpreadsheetApp.getActiveSpreadsheet().getSheetByName(EMPLOYEE_SHEET_NAME);
    if (!sheet || sheet.getLastRow() < 2)
      return { success: false, message: "Sheet Employee tidak ditemukan." };

    var data = sheet.getDataRange().getValues();
    var headers = data[0];
    var colIndex = {};
    headers.forEach(function (h, i) {
      colIndex[String(h).trim()] = i;
    });

    for (var r = 1; r < data.length; r++) {
      if (String(data[r][colIndex["Employee ID"]] || "") !== String(id))
        continue;
      if (!isOutsourceEmployeeRow_(data[r], colIndex))
        return {
          success: false,
          message: "Bukan karyawan outsource.",
        };

      var name = String(data[r][colIndex["Full Name"]] || "");
      sheet.deleteRow(r + 1);
      writeAuditLog_(id, "Deleted", "Status", "-", "Deleted");
      return {
        success: true,
        message: 'Karyawan "' + name + '" berhasil dihapus.',
      };
    }

    return { success: false, message: "Employee ID tidak ditemukan: " + id };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
