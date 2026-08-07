// ============================================================
// backend/Employee.gs — MASTER DATA KARYAWAN
// Schema: defined in EMPLOYEE_HEADERS (Config.gs)
// Column access: use EMPLOYEE_COL[headerName] — NEVER hardcoded indexes
// ============================================================

// ============= GENERATE EMPLOYEE ID =============
// Reuses generateEmployeeId_() from IdGenerator.gs

// ============= GET EMPLOYEE LIST =============
function getEmployeeList() {
  try {
    var sheet = getOrCreateEmployeeSheet_();
    if (!sheet || sheet.getLastRow() < 2) return { success: true, data: [] };

    var data     = sheet.getDataRange().getValues();
    var headers  = data[0];
    var colIndex = {};
    headers.forEach(function(h, i) { colIndex[String(h).trim()] = i; });

    var items = [];
    for (var r = 1; r < data.length; r++) {
      var row = data[r];
      if (!row.join('').toString().trim()) continue;

      items.push({
        employeeId:        String(row[colIndex['Employee ID']]         || ''),
        recruitmentId:     String(row[colIndex['Recruitment ID']]      || ''),
        fullName:          String(row[colIndex['Full Name']]           || ''),
        position:          String(row[colIndex['Position']]            || ''),
        email:             String(row[colIndex['Email']]               || ''),
        phone:             String(row[colIndex['Phone']]               || ''),
        joinDate:          fmtDateStr_(row[colIndex['Join Date']]),
        status:            String(row[colIndex['Status']]              || ''),
        notes:             String(row[colIndex['Notes']]               || ''),
        createdAt:         row[colIndex['Created At']] instanceof Date
                             ? Utilities.formatDate(row[colIndex['Created At']], 'GMT+7', 'dd/MM/yyyy HH:mm')
                             : fmtDateStr_(String(row[colIndex['Created At']]      || '')),
        _rawCreatedAt:     row[colIndex['Created At']] instanceof Date
                             ? row[colIndex['Created At']].getTime()
                             : (new Date(String(row[colIndex['Created At']] || ''))).getTime() || 0,
        companyEntity:     String(row[colIndex['Company Entity']]      || ''),
        employeeType:      String(row[colIndex['Employee Type']]       || ''),
        nik:               String(row[colIndex['NIK']]                 || ''),
        birthDate:         fmtDateStr_(row[colIndex['Birth Date']]),
        age:               String(row[colIndex['Age']]                 || ''),
        gender:            String(row[colIndex['Gender']]              || ''),
        maritalStatus:     String(row[colIndex['Marital Status']]      || ''),
        address:           String(row[colIndex['Address']]             || ''),
        city:              String(row[colIndex['City']]                || ''),
        education:         String(row[colIndex['Education']]           || ''),
        workExperience:    String(row[colIndex['Work Experience']]     || ''),
        department:        String(row[colIndex['Department']]          || ''),
        division:          String(row[colIndex['Division']]            || ''),
        branch:            String(row[colIndex['Branch']]              || ''),
        contractStart:     fmtDateStr_(row[colIndex['Contract Start']]),
        contractEnd:       fmtDateStr_(row[colIndex['Contract End']]),
        contractDuration:  String(row[colIndex['Contract Duration']]   || ''),
        employmentStatus:  String(row[colIndex['Employment Status']]   || ''),
        salary:            row[colIndex['Salary']] || '',
        salaryType:        String(row[colIndex['Salary Type']]         || ''),
        outsourceVendor:   String(row[colIndex['Outsource Vendor']]    || ''),
        contractNumber:    String(row[colIndex['Contract Number']]     || ''),
        district:          String(row[colIndex['District']]            || ''),
        recruitmentSource: String(row[colIndex['Recruitment Source']]  || ''),
        hrNotes:           String(row[colIndex['HR Notes']]            || ''),
        createdBy:         String(row[colIndex['Created By']]          || ''),
        updatedAt:         row[colIndex['Updated At']] instanceof Date
                             ? Utilities.formatDate(row[colIndex['Updated At']], 'GMT+7', 'dd/MM/yyyy HH:mm')
                             : fmtDateStr_(String(row[colIndex['Updated At']]       || ''))
      });
    }

    items.sort(function(a, b) {
      return (b._rawCreatedAt || 0) - (a._rawCreatedAt || 0);
    });

    items.forEach(function(item) { delete item._rawCreatedAt; });

    return { success: true, data: items };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= ADD EMPLOYEE =============
function addEmployee(empData) {
  try {
    if (!empData.fullName) return { success: false, message: 'Nama karyawan wajib diisi.' };

    var lock = LockService.getScriptLock();
    lock.waitLock(10000);

    var now        = new Date();
    var newId      = generateEmployeeId_(now);
    var createdAt  = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

    var sheet = getOrCreateEmployeeSheet_();

    // Build row using EMPLOYEE_COL for column order alignment
    var newRow = new Array(EMPLOYEE_HEADERS.length).fill('');
    newRow[EMPLOYEE_COL['Employee ID'] - 1]         = newId;
    newRow[EMPLOYEE_COL['Recruitment ID'] - 1]      = empData.recruitmentId   || '';
    newRow[EMPLOYEE_COL['Full Name'] - 1]            = empData.fullName        || '';
    newRow[EMPLOYEE_COL['Position'] - 1]             = empData.position        || '';
    newRow[EMPLOYEE_COL['Email'] - 1]                = empData.email           || '';
    newRow[EMPLOYEE_COL['Phone'] - 1]                = empData.phone           || '';
    newRow[EMPLOYEE_COL['Join Date'] - 1]            = empData.joinDate        || createdAt;
    newRow[EMPLOYEE_COL['Status'] - 1]               = empData.status          || 'Active';
    newRow[EMPLOYEE_COL['Notes'] - 1]                = empData.notes           || '';
    newRow[EMPLOYEE_COL['Created At'] - 1]           = createdAt;
    newRow[EMPLOYEE_COL['Company Entity'] - 1]       = empData.companyEntity   || 'PT Mahakarya Sukses Indonesia';
    newRow[EMPLOYEE_COL['Employee Type'] - 1]        = empData.employeeType    || '';
    newRow[EMPLOYEE_COL['NIK'] - 1]                  = empData.nik             || '';
    newRow[EMPLOYEE_COL['Birth Date'] - 1]           = empData.birthDate       || '';
    newRow[EMPLOYEE_COL['Age'] - 1]                  = empData.age             || '';
    newRow[EMPLOYEE_COL['Gender'] - 1]               = empData.gender          || '';
    newRow[EMPLOYEE_COL['Marital Status'] - 1]       = empData.maritalStatus   || '';
    newRow[EMPLOYEE_COL['Address'] - 1]              = empData.address         || '';
    newRow[EMPLOYEE_COL['City'] - 1]                 = empData.city            || empData.workLocation || '';
    newRow[EMPLOYEE_COL['Education'] - 1]            = empData.education       || '';
    newRow[EMPLOYEE_COL['Work Experience'] - 1]      = empData.workExperience  || '';
    newRow[EMPLOYEE_COL['Department'] - 1]           = empData.department      || '';
    newRow[EMPLOYEE_COL['Division'] - 1]             = empData.division        || '';
    newRow[EMPLOYEE_COL['Branch'] - 1]               = empData.branch          || '';
    newRow[EMPLOYEE_COL['Contract Start'] - 1]       = empData.contractStart   || '';
    newRow[EMPLOYEE_COL['Contract End'] - 1]         = empData.contractEnd     || '';
    newRow[EMPLOYEE_COL['Contract Duration'] - 1]    = empData.contractDuration|| '';
    newRow[EMPLOYEE_COL['Employment Status'] - 1]    = empData.employmentStatus|| 'Active';
    newRow[EMPLOYEE_COL['Salary'] - 1]               = empData.salary          || '';
    newRow[EMPLOYEE_COL['Salary Type'] - 1]          = empData.salaryType      || '';
    newRow[EMPLOYEE_COL['Outsource Vendor'] - 1]     = empData.outsourceVendor || '';
    newRow[EMPLOYEE_COL['Contract Number'] - 1]      = empData.contractNumber  || '';
    newRow[EMPLOYEE_COL['District'] - 1]             = empData.district        || '';
    newRow[EMPLOYEE_COL['Recruitment Source'] - 1]   = empData.recruitmentSource|| '';
    newRow[EMPLOYEE_COL['HR Notes'] - 1]             = empData.hrNotes         || '';
    newRow[EMPLOYEE_COL['Created By'] - 1]           = empData.createdBy       || '';
    newRow[EMPLOYEE_COL['Updated At'] - 1]           = createdAt;

    sheet.appendRow(newRow);
    lock.releaseLock();

    return { success: true, message: 'Karyawan "' + empData.fullName + '" berhasil ditambahkan.', id: newId };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= OFFBOARDING =============
// Status karyawan yang memicu offboarding otomatis
var OFFBOARDING_TRIGGER_STATUSES = ['Resigned', 'Terminated', 'On Leave'];

// Pemetaan status employee -> Offboarding Type
var OFFBOARDING_TYPE_MAP = {
  'Resigned':   'Resignation',
  'Terminated': 'Termination',
  'On Leave':   'On Leave'
};

// Kembalikan nama offboarding type untuk sebuah status, atau null jika
// status tersebut tidak memicu offboarding.
function resolveOffboardingType_(status) {
  status = String(status || '').trim();
  return OFFBOARDING_TYPE_MAP[status] || null;
}

// Simpan catatan offboarding ke sheet Offboarding (buat jika belum ada).
// Dipanggil dari updateEmployee saat status berubah ke Resigned / Terminated /
// On Leave. Mengirim lock dari caller (updateEmployee), jadi tidak mengambil
// lock ulang.
function offboardEmployee_(employeeId, offboardingType, reason, notes) {
  try {
    var sheet = getOrCreateEmployeeSheet_();
    var data  = sheet.getDataRange().getValues();
    var headers = data[0];
    var colIndex = {};
    headers.forEach(function (h, i) { colIndex[String(h).trim()] = i; });

    var row = null;
    for (var r = 1; r < data.length; r++) {
      if (String(data[r][colIndex['Employee ID']] || '') === String(employeeId)) {
        row = data[r];
        break;
      }
    }
    if (!row)
      return { success: false, message: 'Karyawan tidak ditemukan: ' + employeeId };

    // Cegah duplikat: sudah ada offboarding bertipe sama untuk employee ini
    var offSheet = getOrCreateOffboardingSheet_();
    if (offSheet.getLastRow() > 1) {
      var offData = offSheet
        .getRange(2, 1, offSheet.getLastRow() - 1, OFFBOARDING_HEADERS.length)
        .getValues();
      for (var i = 0; i < offData.length; i++) {
        if (
          String(offData[i][OFFBOARD_COL['Employee ID'] - 1] || '') ===
            String(employeeId) &&
          String(offData[i][OFFBOARD_COL['Offboarding Type'] - 1] || '') ===
            String(offboardingType)
        ) {
          return { success: false, duplicate: true, offboardingId: String(offData[i][OFFBOARD_COL['Offboarding ID'] - 1] || '') };
        }
      }
    }

    var user = Session.getActiveUser().getEmail() || 'HR Dashboard';
    var now = new Date();
    var nowStr = Utilities.formatDate(now, 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
    var offboardingId = generateOffboardingId_(now);

    function val(name) {
      var i = colIndex[name];
      return i === undefined ? '' : String(row[i] || '');
    }

    var newRow = new Array(OFFBOARDING_HEADERS.length).fill('');
    newRow[OFFBOARD_COL['Offboarding ID'] - 1]   = offboardingId;
    newRow[OFFBOARD_COL['Employee ID'] - 1]      = employeeId;
    newRow[OFFBOARD_COL['Full Name'] - 1]        = val('Full Name');
    newRow[OFFBOARD_COL['Position'] - 1]         = val('Position');
    newRow[OFFBOARD_COL['Department'] - 1]       = val('Department');
    newRow[OFFBOARD_COL['Join Date'] - 1]        = val('Join Date');
    newRow[OFFBOARD_COL['Last Working Date'] - 1] = nowStr;
    newRow[OFFBOARD_COL['Offboarding Type'] - 1] = offboardingType;
    newRow[OFFBOARD_COL['Reason'] - 1]           = reason || '';
    newRow[OFFBOARD_COL['Approved By'] - 1]      = user;
    newRow[OFFBOARD_COL['Notes'] - 1]            = notes || '';
    newRow[OFFBOARD_COL['Status'] - 1]           = 'Active';
    newRow[OFFBOARD_COL['Archived'] - 1]         = 'No';
    newRow[OFFBOARD_COL['Created By'] - 1]       = user;
    newRow[OFFBOARD_COL['Created At'] - 1]       = nowStr;
    newRow[OFFBOARD_COL['Updated At'] - 1]       = nowStr;

    offSheet.appendRow(newRow);

    writeAuditLog_(
      employeeId,
      'Offboarding',
      'Employment Status',
      offboardingType,
      offboardingType + ' -> ' + offboardingId,
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
    if (!id || !updates) return { success: false, message: 'ID dan data harus diisi.' };

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateEmployeeSheet_();
    var data  = sheet.getDataRange().getValues();

    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(id)) {
        var now = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

        // ---- Tangkap status lama untuk deteksi offboarding ----
        var oldEmploymentStatus = String(
          data[i][EMPLOYEE_COL['Employment Status'] - 1] || '',
        );
        var oldStatus = String(data[i][EMPLOYEE_COL['Status'] - 1] || '');

        // Map camelCase update keys to header names
        var keyMap = {
          companyEntity:   'Company Entity',
          employeeType:    'Employee Type',
          fullName:        'Full Name',
          nik:             'NIK',
          workLocation:    'City',
          age:             'Age',
          gender:          'Gender',
          maritalStatus:   'Marital Status',
          email:           'Email',
          phone:           'Phone',
          address:         'Address',
          education:       'Education',
          workExperience:  'Work Experience',
          department:      'Department',
          division:        'Division',
          branch:          'Branch',
          position:        'Position',
          joinDate:        'Join Date',
          contractStart:   'Contract Start',
          contractEnd:     'Contract End',
          contractDuration:'Contract Duration',
          employmentStatus:'Employment Status',
          salary:          'Salary',
          salaryType:      'Salary Type',
          outsourceVendor: 'Outsource Vendor',
          contractNumber:  'Contract Number',
          district:        'District',
          recruitmentId:   'Recruitment ID',
          recruitmentSource:'Recruitment Source',
          hrNotes:         'HR Notes',
          notes:           'Notes',
          status:          'Status',
          createdBy:       'Created By'
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
        sheet.getRange(i + 1, EMPLOYEE_COL['Updated At']).setValue(now);

        // ---- Trigger offboarding otomatis ----
        // Jika status baru masuk daftar pemicu (Resigned / Terminated / On Leave)
        // dan berbeda dari status lama, catat ke sheet Offboarding.
        var newEmploymentStatus = updates.employmentStatus !== undefined
          ? String(updates.employmentStatus)
          : oldEmploymentStatus;
        var newStatus = updates.status !== undefined
          ? String(updates.status)
          : oldStatus;

        // Nilai status baru (perubahan pertama yang ditemukan).
        // Hanya trigger jika field yang benar-benar berubah adalah trigger.
        var changedEmployment = newEmploymentStatus !== oldEmploymentStatus;
        var changedStatus = newStatus !== oldStatus;
        var offboardingType = null;
        if (changedEmployment) offboardingType = resolveOffboardingType_(newEmploymentStatus);
        if (!offboardingType && changedStatus) offboardingType = resolveOffboardingType_(newStatus);

        // Audit perubahan status (wajib sesuai AGENTS.md).
        if (changedEmployment || changedStatus) {
          var displayNewStatus = changedEmployment ? newEmploymentStatus : newStatus;
          var displayOldStatus = changedEmployment ? oldEmploymentStatus : oldStatus;
          writeAuditLog_(id, "Update Status", "Employment Status", displayOldStatus, displayNewStatus);
        }

        var offboardResult = null;
        if (offboardingType) {
          var reason = updates.reason || '';
          var note = updates.notes || updates.hrNotes || '';
          if (note === reason) note = '';
          offboardResult = offboardEmployee_(id, offboardingType, reason, note);
        }

        lock.releaseLock();

        return {
          success: true,
          message: 'Data karyawan berhasil diperbarui.',
          offboarding: offboardResult
            ? { triggered: true, type: offboardingType, id: offboardResult.offboardingId || null, duplicate: !!offboardResult.duplicate, offboardId: offboardResult.offboardingId }
            : { triggered: false },
        };
      }
    }

    lock.releaseLock();
    return { success: false, message: 'Karyawan tidak ditemukan.' };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= DELETE EMPLOYEE =============
function deleteEmployee(id) {
  try {
    if (!id) return { success: false, message: 'ID harus diisi.' };

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateEmployeeSheet_();
    var data  = sheet.getDataRange().getValues();
    var colNameIdx = 0; // Employee ID is column 1

    for (var i = 1; i < data.length; i++) {
      if (String(data[i][colNameIdx]) === String(id)) {
        var name = data[i][EMPLOYEE_COL['Full Name'] - 1];
        sheet.deleteRow(i + 1);
        lock.releaseLock();
        return { success: true, message: 'Karyawan "' + name + '" berhasil dihapus.' };
      }
    }

    lock.releaseLock();
    return { success: false, message: 'Karyawan tidak ditemukan.' };
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
      byLocation: {}
    };

    items.forEach(function(emp) {
      if (emp.employmentStatus === 'Active' || emp.status === 'Active') stats.active++;
      else stats.inactive++;

      var dept = emp.department || 'Belum Ditentukan';
      stats.byDepartment[dept] = (stats.byDepartment[dept] || 0) + 1;

      var type = emp.employeeType || 'Belum Ditentukan';
      stats.byType[type] = (stats.byType[type] || 0) + 1;

      var city = emp.city || 'Belum Ditentukan';
      stats.byLocation[city] = (stats.byLocation[city] || 0) + 1;
    });

    return { success: true, stats: stats };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}