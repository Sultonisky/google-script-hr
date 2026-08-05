// ============================================================
// backend/Outsource.gs — OUTSOURCE EMPLOYEE REGISTRATION
// Flow: Form → raw_outsource (pending) → approved → Employee
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

// ---- Simpan data OS ke raw_outsource (pending approval) ----
function simpanDataOutsource(formObject) {
  try {
    var validationError = validateOutsourceForm_(formObject);
    if (validationError) return "Error: " + validationError;

    var now = new Date();
    var outsourceId = generateOutsourceId_(now);
    var createdAt = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");

    var sheet = getOrCreateOutsourceSheet_();

    var newRow = new Array(OUTSOURCE_HEADERS.length).fill("");
    newRow[OUTSOURCE_COL["Outsource ID"] - 1] = outsourceId;
    newRow[OUTSOURCE_COL["Created Date"] - 1] = createdAt;
    newRow[OUTSOURCE_COL["Full Name"] - 1] = formObject.full_name;
    newRow[OUTSOURCE_COL["NIK"] - 1] = "'" + formObject.nik;
    newRow[OUTSOURCE_COL["Birth Date"] - 1] = formObject.birth_date;
    newRow[OUTSOURCE_COL["Age"] - 1] = Number(formObject.age) || "";
    newRow[OUTSOURCE_COL["Gender"] - 1] = formObject.gender;
    newRow[OUTSOURCE_COL["Marital Status"] - 1] = formObject.marital_status;
    newRow[OUTSOURCE_COL["Email"] - 1] = formObject.email;
    newRow[OUTSOURCE_COL["Phone"] - 1] = "'" + formObject.phone;
    newRow[OUTSOURCE_COL["Address"] - 1] = formObject.address;
    newRow[OUTSOURCE_COL["City"] - 1] = formObject.city;
    newRow[OUTSOURCE_COL["Province"] - 1] = formObject.province || "";
    newRow[OUTSOURCE_COL["Position"] - 1] = formObject.position;
    newRow[OUTSOURCE_COL["Department"] - 1] = formObject.department || "";
    newRow[OUTSOURCE_COL["Join Date"] - 1] = formObject.join_date;
    newRow[OUTSOURCE_COL["Education"] - 1] = formObject.education;
    newRow[OUTSOURCE_COL["Work Experience"] - 1] =
      formObject.work_experience || "";
    newRow[OUTSOURCE_COL["Vendor Company"] - 1] =
      formObject.vendor_company || "";
    newRow[OUTSOURCE_COL["Contract Number"] - 1] =
      formObject.contract_number || "";
    newRow[OUTSOURCE_COL["Contract Duration"] - 1] =
      formObject.contract_duration || "";
    newRow[OUTSOURCE_COL["Salary"] - 1] = Number(formObject.salary) || 0;
    newRow[OUTSOURCE_COL["Recruitment Source"] - 1] =
      formObject.recruitment_source || "";
    newRow[OUTSOURCE_COL["Status"] - 1] = "Pending";
    newRow[OUTSOURCE_COL["Notes"] - 1] = "";
    newRow[OUTSOURCE_COL["Created By"] - 1] = "System";
    newRow[OUTSOURCE_COL["Updated At"] - 1] = createdAt;

    sheet.appendRow(newRow);

    writeAuditLog_(outsourceId, "Created", "Status", "-", "Pending");
    return "Sukses";
  } catch (error) {
    return "Error: " + error.toString();
  }
}

// ---- Approve outsource → Employee ----
function approveOutsourceToEmployee(outsourceId, notes) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var srcSheet =
      SpreadsheetApp.getActiveSpreadsheet().getSheetByName(
        OUTSOURCE_SHEET_NAME,
      );
    if (!srcSheet || srcSheet.getLastRow() < 2)
      return { success: false, message: "Sheet outsource tidak ditemukan." };

    var data = srcSheet.getDataRange().getValues();

    for (var i = 1; i < data.length; i++) {
      if (
        String(data[i][OUTSOURCE_COL["Outsource ID"] - 1]) !==
        String(outsourceId)
      )
        continue;

      var now = new Date();
      var employeeId = generateEmployeeId_(now);
      var createdAt = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");
      var row = data[i];

      // Write to Employee sheet using EMPLOYEE_COL
      var empSheet = getOrCreateEmployeeSheet_();
      var newRow = new Array(EMPLOYEE_HEADERS.length).fill("");
      newRow[EMPLOYEE_COL["Employee ID"] - 1] = employeeId;
      newRow[EMPLOYEE_COL["Recruitment ID"] - 1] = outsourceId;
      newRow[EMPLOYEE_COL["Full Name"] - 1] =
        row[OUTSOURCE_COL["Full Name"] - 1];
      newRow[EMPLOYEE_COL["Position"] - 1] = row[OUTSOURCE_COL["Position"] - 1];
      newRow[EMPLOYEE_COL["Email"] - 1] = row[OUTSOURCE_COL["Email"] - 1];
      newRow[EMPLOYEE_COL["Phone"] - 1] = row[OUTSOURCE_COL["Phone"] - 1];
      newRow[EMPLOYEE_COL["Join Date"] - 1] =
        row[OUTSOURCE_COL["Join Date"] - 1];
      newRow[EMPLOYEE_COL["Status"] - 1] = "Active";
      newRow[EMPLOYEE_COL["Notes"] - 1] = notes || "";
      newRow[EMPLOYEE_COL["Created At"] - 1] = createdAt;
      newRow[EMPLOYEE_COL["Company Entity"] - 1] =
        "PT Mahakarya Sukses Indonesia";
      newRow[EMPLOYEE_COL["Employee Type"] - 1] = "Outsource";
      newRow[EMPLOYEE_COL["NIK"] - 1] = row[OUTSOURCE_COL["NIK"] - 1];
      newRow[EMPLOYEE_COL["Birth Date"] - 1] =
        row[OUTSOURCE_COL["Birth Date"] - 1];
      newRow[EMPLOYEE_COL["Age"] - 1] = row[OUTSOURCE_COL["Age"] - 1];
      newRow[EMPLOYEE_COL["Gender"] - 1] = row[OUTSOURCE_COL["Gender"] - 1];
      newRow[EMPLOYEE_COL["Marital Status"] - 1] =
        row[OUTSOURCE_COL["Marital Status"] - 1];
      newRow[EMPLOYEE_COL["Address"] - 1] = row[OUTSOURCE_COL["Address"] - 1];
      newRow[EMPLOYEE_COL["City"] - 1] = row[OUTSOURCE_COL["City"] - 1];
      newRow[EMPLOYEE_COL["Education"] - 1] =
        row[OUTSOURCE_COL["Education"] - 1];
      newRow[EMPLOYEE_COL["Work Experience"] - 1] =
        row[OUTSOURCE_COL["Work Experience"] - 1];
      newRow[EMPLOYEE_COL["Department"] - 1] =
        row[OUTSOURCE_COL["Department"] - 1];
      newRow[EMPLOYEE_COL["Division"] - 1] = "";
      newRow[EMPLOYEE_COL["Branch"] - 1] = "";
      newRow[EMPLOYEE_COL["Contract Duration"] - 1] =
        row[OUTSOURCE_COL["Contract Duration"] - 1];
      newRow[EMPLOYEE_COL["Employment Status"] - 1] = "Active";
      newRow[EMPLOYEE_COL["Salary"] - 1] = row[OUTSOURCE_COL["Salary"] - 1];
      newRow[EMPLOYEE_COL["Salary Type"] - 1] = "Monthly";
      newRow[EMPLOYEE_COL["Outsource Vendor"] - 1] =
        row[OUTSOURCE_COL["Vendor Company"] - 1];
      newRow[EMPLOYEE_COL["Contract Number"] - 1] =
        row[OUTSOURCE_COL["Contract Number"] - 1];
      newRow[EMPLOYEE_COL["District"] - 1] = "";
      newRow[EMPLOYEE_COL["Recruitment Source"] - 1] =
        row[OUTSOURCE_COL["Recruitment Source"] - 1];
      newRow[EMPLOYEE_COL["HR Notes"] - 1] = notes || "";
      newRow[EMPLOYEE_COL["Created By"] - 1] = "System";
      newRow[EMPLOYEE_COL["Updated At"] - 1] = createdAt;

      empSheet.appendRow(newRow);

      // Update source row status
      srcSheet.getRange(i + 1, OUTSOURCE_COL["Status"]).setValue("Approved");
      srcSheet.getRange(i + 1, OUTSOURCE_COL["Updated At"]).setValue(createdAt);

      writeAuditLog_(outsourceId, "Approved", "Status", "Pending", "Approved");
      return {
        success: true,
        outsourceId: outsourceId,
        employeeId: employeeId,
        newStatus: "Approved",
      };
    }

    return {
      success: false,
      message: "Outsource ID tidak ditemukan: " + outsourceId,
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Reject outsource ----
function rejectOutsource(outsourceId, reason) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var srcSheet =
      SpreadsheetApp.getActiveSpreadsheet().getSheetByName(
        OUTSOURCE_SHEET_NAME,
      );
    if (!srcSheet || srcSheet.getLastRow() < 2)
      return { success: false, message: "Sheet outsource tidak ditemukan." };

    var data = srcSheet.getDataRange().getValues();

    for (var i = 1; i < data.length; i++) {
      if (
        String(data[i][OUTSOURCE_COL["Outsource ID"] - 1]) !==
        String(outsourceId)
      )
        continue;

      var now = new Date();
      var createdAt = Utilities.formatDate(now, "GMT+7", "yyyy-MM-dd HH:mm:ss");

      srcSheet.getRange(i + 1, OUTSOURCE_COL["Status"]).setValue("Rejected");
      srcSheet.getRange(i + 1, OUTSOURCE_COL["Notes"]).setValue(reason || "");
      srcSheet.getRange(i + 1, OUTSOURCE_COL["Updated At"]).setValue(createdAt);

      writeAuditLog_(outsourceId, "Rejected", "Status", "Pending", "Rejected");
      return { success: true, outsourceId: outsourceId, newStatus: "Rejected" };
    }

    return {
      success: false,
      message: "Outsource ID tidak ditemukan: " + outsourceId,
    };
  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}

// ---- Get outsource list (raw_outsource sheet) ----
function getOutsourceList() {
  var sheet =
    SpreadsheetApp.getActiveSpreadsheet().getSheetByName(OUTSOURCE_SHEET_NAME);
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
    result.push({
      outsourceId: String(row[colIndex["Outsource ID"]] || ""),
      createdDate:
        row[colIndex["Created Date"]] instanceof Date
          ? Utilities.formatDate(
              row[colIndex["Created Date"]],
              "GMT+7",
              "dd/MM/yyyy HH:mm",
            )
          : String(row[colIndex["Created Date"]] || ""),
      fullName: String(row[colIndex["Full Name"]] || ""),
      nik: String(row[colIndex["NIK"]] || ""),
      email: String(row[colIndex["Email"]] || ""),
      position: String(row[colIndex["Position"]] || ""),
      department: String(row[colIndex["Department"]] || ""),
      joinDate: String(row[colIndex["Join Date"]] || ""),
      vendorCompany: String(row[colIndex["Vendor Company"]] || ""),
      contractDuration: String(row[colIndex["Contract Duration"]] || ""),
      status: String(row[colIndex["Status"]] || "Pending"),
      notes: String(row[colIndex["Notes"]] || ""),
    });
  }

  result.sort(function (a, b) {
    return (b.createdDate || "").localeCompare(a.createdDate || "");
  });

  return result;
}
