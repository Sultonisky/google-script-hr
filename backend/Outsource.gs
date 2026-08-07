// ============================================================
// backend/Outsource.gs — OUTSOURCE EMPLOYEE REGISTRATION
// Flow: Form → Employee sheet (Employee Type = "Outsource", Status = "Active")
// (No more raw_outsource staging sheet — data lands directly in Employee.)
// ============================================================

// ---- Validasi form OS ----
function validateOutsourceForm_(f) {
  if (!f.full_name || f.full_name.trim().length < 3)
    return "Nama lengkap wajib diisi (minimal 3 karakter).";
  if (!f.nik || !/^[0-9]{16}$/.test(f.nik))
    return "NIK harus tepat 16 digit angka.";
  if (!f.birth_date) return "Tanggal lahir wajib diisi.";
  if (!f.age || Number(f.age) < 17) return "Usia minimal 17 tahun.";
  if (!f.gender) return "Jenis kelamin wajib dipilih.";
  if (!f.marital_status) return "Status pernikahan wajib dipilih.";
  if (!f.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email))
    return "Format email tidak valid.";
  if (!f.phone || !/^62[0-9]{8,12}$/.test(f.phone))
    return "Nomor HP tidak valid.";
  if (!f.address || f.address.trim() === "") return "Alamat wajib diisi.";
  if (!f.city || f.city.trim() === "") return "Kota/Kabupaten wajib diisi.";
  if (!f.position || f.position.trim() === "")
    return "Posisi/jabatan wajib diisi.";
  if (!f.join_date) return "Tanggal mulai kerja wajib diisi.";
  if (!f.education) return "Pendidikan terakhir wajib dipilih.";
  if (!f.company_entity) return "Entitas perusahaan wajib dipilih.";
  if (!f.employee_type) return "Tipe karyawan wajib dipilih.";
  if (!f.employment_status) return "Status kepegawaian wajib dipilih.";
  if (!f.salary) return "Gaji wajib diisi dengan angka.";
  if (!f.salary_type) return "Tipe gaji wajib dipilih.";
  if (f.employee_type === "Outsource" && !f.outsource_vendor)
    return "Nama outsource vendor wajib diisi untuk tipe Outsource.";
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
  try {
    var validationError = validateOutsourceForm_(formObject);
    if (validationError) return "Error: " + validationError;

    var lock = LockService.getScriptLock();
    lock.waitLock(10000);
    try {
      var now = new Date();
      var outsourceId = generateOutsourceId_(now);
      var employeeId = generateEmployeeId_(now);
      var createdAt = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");

      var sheet = getOrCreateEmployeeSheet_();

      // Build row using EMPLOYEE_COL for exact column alignment (37 cols)
      var newRow = new Array(EMPLOYEE_HEADERS.length).fill("");
      newRow[EMPLOYEE_COL["Employee ID"] - 1] = employeeId;
      newRow[EMPLOYEE_COL["Recruitment ID"] - 1] = outsourceId;
      newRow[EMPLOYEE_COL["Full Name"] - 1] = formObject.full_name;
      newRow[EMPLOYEE_COL["Position"] - 1] = formObject.position;
      newRow[EMPLOYEE_COL["Email"] - 1] = formObject.email;
      newRow[EMPLOYEE_COL["Phone"] - 1] = "'" + formObject.phone;
      newRow[EMPLOYEE_COL["Join Date"] - 1] = formObject.join_date;
      newRow[EMPLOYEE_COL["Status"] - 1] = "Active";
      newRow[EMPLOYEE_COL["Notes"] - 1] = "";
      newRow[EMPLOYEE_COL["Created At"] - 1] = createdAt;
      newRow[EMPLOYEE_COL["Company Entity"] - 1] =
        formObject.company_entity || "PT Mahakarya Sukses Indonesia";
      newRow[EMPLOYEE_COL["Employee Type"] - 1] = "Outsource";
      newRow[EMPLOYEE_COL["NIK"] - 1] = "'" + formObject.nik;
      newRow[EMPLOYEE_COL["Birth Date"] - 1] = formObject.birth_date;
      newRow[EMPLOYEE_COL["Age"] - 1] = Number(formObject.age) || "";
      newRow[EMPLOYEE_COL["Gender"] - 1] = formObject.gender;
      newRow[EMPLOYEE_COL["Marital Status"] - 1] = formObject.marital_status;
      newRow[EMPLOYEE_COL["Address"] - 1] = formObject.address;
      newRow[EMPLOYEE_COL["City"] - 1] = formObject.city;
      newRow[EMPLOYEE_COL["Education"] - 1] = formObject.education;
      newRow[EMPLOYEE_COL["Work Experience"] - 1] =
        formObject.work_experience || "";
      newRow[EMPLOYEE_COL["Department"] - 1] = formObject.department || "";
      newRow[EMPLOYEE_COL["Division"] - 1] = "";
      newRow[EMPLOYEE_COL["Branch"] - 1] = "";
      newRow[EMPLOYEE_COL["Contract Start"] - 1] =
        formObject.contract_start || "";
      newRow[EMPLOYEE_COL["Contract End"] - 1] =
        formObject.contract_end || "";
      newRow[EMPLOYEE_COL["Contract Duration"] - 1] =
        formObject.contract_duration || "";
      newRow[EMPLOYEE_COL["Employment Status"] - 1] =
        formObject.employment_status || "Probation";
      newRow[EMPLOYEE_COL["Salary"] - 1] = Number(formObject.salary) || 0;
      newRow[EMPLOYEE_COL["Salary Type"] - 1] = formObject.salary_type || "Monthly";
      newRow[EMPLOYEE_COL["Outsource Vendor"] - 1] =
        formObject.outsource_vendor || "";
      newRow[EMPLOYEE_COL["Contract Number"] - 1] =
        formObject.contract_number || "";
      newRow[EMPLOYEE_COL["District"] - 1] = "";
      newRow[EMPLOYEE_COL["Recruitment Source"] - 1] =
        formObject.recruitment_source || "";
      newRow[EMPLOYEE_COL["HR Notes"] - 1] = "";
      newRow[EMPLOYEE_COL["Created By"] - 1] = "System (Outsource Form)";
      newRow[EMPLOYEE_COL["Updated At"] - 1] = createdAt;

      sheet.appendRow(newRow);

      writeAuditLog_(
        outsourceId,
        "Created",
        "Status",
        "-",
        "Active (Outsource)",
      );
      return "Sukses";
    } finally {
      lock.releaseLock();
    }
  } catch (error) {
    return "Error: " + error.toString();
  }
}

// ---- Get outsource list (from Employee sheet, Employee Type = "Outsource") ----
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

      // Filter: only Outsource employee type
      var empType = String(row[colIndex["Employee Type"]] || "").trim();
      if (empType !== "Outsource") continue;

       result.push({
        employeeId: String(row[colIndex["Employee ID"]] || ""),
        outsourceId: String(row[colIndex["Recruitment ID"]] || ""),
        createdDate: row[colIndex["Created At"]] instanceof Date
          ? Utilities.formatDate(row[colIndex["Created At"]], "GMT+7", "dd/MM/yyyy HH:mm")
          : fmtDateStr_(String(row[colIndex["Created At"]] || "")),
        _rawCreated: row[colIndex["Created At"]] instanceof Date
          ? row[colIndex["Created At"]].getTime()
          : (new Date(String(row[colIndex["Created At"]] || ""))).getTime() || 0,
        fullName: String(row[colIndex["Full Name"]] || ""),
        nik: String(row[colIndex["NIK"]] || ""),
          birthDate: fmtDateStr_(row[colIndex["Birth Date"]]),
          age: String(row[colIndex["Age"]] || ""),
        gender: String(row[colIndex["Gender"]] || ""),
        maritalStatus: String(row[colIndex["Marital Status"]] || ""),
        email: String(row[colIndex["Email"]] || ""),
        phone: String(row[colIndex["Phone"]] || ""),
        address: String(row[colIndex["Address"]] || ""),
        city: String(row[colIndex["City"]] || ""),
        district: String(row[colIndex["District"]] || ""),
        companyEntity: String(row[colIndex["Company Entity"]] || ""),
        position: String(row[colIndex["Position"]] || ""),
        department: String(row[colIndex["Department"]] || ""),
        division: String(row[colIndex["Division"]] || ""),
        branch: String(row[colIndex["Branch"]] || ""),
          joinDate: fmtDateStr_(row[colIndex["Join Date"]]),
          education: String(row[colIndex["Education"]] || ""),
        workExperience: String(row[colIndex["Work Experience"]] || ""),
        employmentStatus: String(
          row[colIndex["Employment Status"]] || "",
        ),
        contractStart: fmtDateStr_(row[colIndex["Contract Start"]]),
        contractEnd:   fmtDateStr_(row[colIndex["Contract End"]]),
        contractDuration: String(
          row[colIndex["Contract Duration"]] || "",
        ),
        salary: row[colIndex["Salary"]] === "" || row[colIndex["Salary"]] === null
          ? ""
          : row[colIndex["Salary"]],
        salaryType: String(row[colIndex["Salary Type"]] || ""),
        outsourceVendor: String(row[colIndex["Outsource Vendor"]] || ""),
        vendorCompany: String(row[colIndex["Outsource Vendor"]] || ""),
        contractNumber: String(row[colIndex["Contract Number"]] || ""),
        recruitmentSource: String(row[colIndex["Recruitment Source"]] || ""),
        status: String(row[colIndex["Status"]] || ""),
        employeeType: String(row[colIndex["Employee Type"]] || "Outsource"),
        companyEntity: String(row[colIndex["Company Entity"]] || ""),
        notes: String(row[colIndex["Notes"]] || ""),
        hrNotes: String(row[colIndex["HR Notes"]] || ""),
        createdBy: String(row[colIndex["Created By"]] || ""),
        updatedAt: row[colIndex["Updated At"]] instanceof Date
          ? Utilities.formatDate(row[colIndex["Updated At"]], "GMT+7", "dd/MM/yyyy HH:mm")
          : fmtDateStr_(String(row[colIndex["Updated At"]] || "")),
      });
    }

    result.sort(function (a, b) {
      return (b._rawCreated || 0) - (a._rawCreated || 0);
    });

    result.forEach(function (item) { delete item._rawCreated; });

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
      var empType = String(
        data[r][colIndex["Employee Type"]] || "",
      ).trim();
      if (empType !== "Outsource")
        return {
          success: false,
          message: "Bukan karyawan outsource.",
        };

      var row = data[r];
      return {
        success: true,
        data: {
          employeeId: String(row[colIndex["Employee ID"]] || ""),
          outsourceId: String(row[colIndex["Recruitment ID"]] || ""),
          fullName: String(row[colIndex["Full Name"]] || ""),
          nik: String(row[colIndex["NIK"]] || ""),
        birthDate: fmtDateStr_(row[colIndex["Birth Date"]]),
          age: String(row[colIndex["Age"]] || ""),
          gender: String(row[colIndex["Gender"]] || ""),
          maritalStatus: String(row[colIndex["Marital Status"]] || ""),
          email: String(row[colIndex["Email"]] || ""),
          phone: String(row[colIndex["Phone"]] || ""),
          address: String(row[colIndex["Address"]] || ""),
          city: String(row[colIndex["City"]] || ""),
          district: String(row[colIndex["District"]] || ""),
          companyEntity: String(row[colIndex["Company Entity"]] || ""),
          position: String(row[colIndex["Position"]] || ""),
          department: String(row[colIndex["Department"]] || ""),
          division: String(row[colIndex["Division"]] || ""),
          branch: String(row[colIndex["Branch"]] || ""),
        joinDate: fmtDateStr_(row[colIndex["Join Date"]]),
          education: String(row[colIndex["Education"]] || ""),
          workExperience: String(row[colIndex["Work Experience"]] || ""),
          employmentStatus: String(
            row[colIndex["Employment Status"]] || "",
          ),
          contractStart: fmtDateStr_(row[colIndex["Contract Start"]]),
          contractEnd:   fmtDateStr_(row[colIndex["Contract End"]]),
          contractDuration: String(
            row[colIndex["Contract Duration"]] || "",
          ),
          salary: row[colIndex["Salary"]] === "" || row[colIndex["Salary"]] === null
            ? ""
            : row[colIndex["Salary"]],
          salaryType: String(row[colIndex["Salary Type"]] || ""),
          outsourceVendor: String(row[colIndex["Outsource Vendor"]] || ""),
          contractNumber: String(row[colIndex["Contract Number"]] || ""),
          recruitmentSource: String(
            row[colIndex["Recruitment Source"]] || "",
          ),
          status: String(row[colIndex["Status"]] || ""),
          notes: String(row[colIndex["Notes"]] || ""),
          hrNotes: String(row[colIndex["HR Notes"]] || ""),
          createdBy: String(row[colIndex["Created By"]] || ""),
          createdDate: row[colIndex["Created At"]] instanceof Date
            ? Utilities.formatDate(row[colIndex["Created At"]], "GMT+7", "dd/MM/yyyy HH:mm")
            : fmtDateStr_(String(row[colIndex["Created At"]] || "")),
          updatedAt: row[colIndex["Updated At"]] instanceof Date
            ? Utilities.formatDate(row[colIndex["Updated At"]], "GMT+7", "dd/MM/yyyy HH:mm")
            : fmtDateStr_(String(row[colIndex["Updated At"]] || "")),
        },
      };
    }

    return { success: false, message: "Outsource ID tidak ditemukan: " + id };
  } catch (error) {
    return { success: false, message: error.toString() };
  }
}

// ---- Update outsource employee status/notes ----
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
      var empType = String(
        data[r][colIndex["Employee Type"]] || "",
      ).trim();
      if (empType !== "Outsource")
        return {
          success: false,
          message: "Bukan karyawan outsource.",
        };

      var oldStatus = String(data[r][colIndex["Status"]] || "");
      var now = Utilities.formatDate(
        new Date(),
        "GMT+7",
        "yyyy-MM-dd HH:mm:ss",
      );

      if (status) {
        sheet
          .getRange(r + 1, colIndex["Status"] + 1)
          .setValue(status);
      }
      if (notes !== undefined && notes !== null) {
        sheet
          .getRange(r + 1, colIndex["HR Notes"] + 1)
          .setValue(notes);
      }
      sheet
        .getRange(r + 1, colIndex["Updated At"] + 1)
        .setValue(now);

      writeAuditLog_(id, "Update Status", "Status", oldStatus, status || oldStatus);
      return {
        success: true,
        employeeId: id,
        newStatus: status,
      };
    }

    return { success: false, message: "Outsource ID tidak ditemukan: " + id };
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
      var empType = String(
        data[r][colIndex["Employee Type"]] || "",
      ).trim();
      if (empType !== "Outsource")
        return {
          success: false,
          message: "Bukan karyawan outsource.",
        };

      var name = String(data[r][colIndex["Full Name"]] || "");
      sheet.deleteRow(r + 1);
      writeAuditLog_(id, "Deleted", "Status", "-", "Deleted");
      return { success: true, message: 'Karyawan "' + name + '" berhasil dihapus.' };
    }

    return { success: false, message: "Outsource ID tidak ditemukan: " + id };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
