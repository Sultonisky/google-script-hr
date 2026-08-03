// ============================================================
// backend/Employee.gs — EMPLOYEE MANAGEMENT
// CRUD operations for master employee data, converted from
// accepted candidates. Core HR data beyond recruitment.
// ============================================================

var EMPLOYEE_SHEET = 'Employee';
var EMPLOYEE_HEADERS = [
  'ID', 'NIK', 'Nama Lengkap', 'Email', 'No. Handphone',
  'Departemen', 'Posisi', 'Lokasi Kerja', 'Tipe Karyawan',
  'Tanggal Masuk', 'Status', 'Foto', 'Catatan HR', 'Dibuat', 'Diubah'
];

// ============= HELPER =============

function getOrCreateEmployeeSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(EMPLOYEE_SHEET);
  if (!sheet) {
    sheet = ss.insertSheet(EMPLOYEE_SHEET);
    sheet.getRange(1, 1, 1, EMPLOYEE_HEADERS.length).setValues([EMPLOYEE_HEADERS]);
    sheet.getRange(1, 1, 1, EMPLOYEE_HEADERS.length)
      .setBackground('#005BAC').setFontColor('#ffffff').setFontWeight('bold');
    sheet.setFrozenRows(1);
    autoResizeColumns_(sheet, EMPLOYEE_HEADERS.length);
  }
  return sheet;
}

function generateEmployeeId_() {
  var props = PropertiesService.getScriptProperties();
  var counter = parseInt(props.getProperty('employee_counter') || '0', 10);
  counter++;
  props.setProperty('employee_counter', String(counter));
  return 'EMP-' + padNumber_(counter, 5);
}

// ============= GET ALL EMPLOYEES =============

function getEmployeeList(filters) {
  try {
    var sheet = getOrCreateEmployeeSheet_();
    var data  = sheet.getDataRange().getValues();
    if (data.length <= 1) return { success: true, data: [], total: 0 };

    var headers = data[0];
    var items   = [];
    for (var i = 1; i < data.length; i++) {
      var row = {};
      for (var j = 0; j < headers.length; j++) {
        row[headers[j]] = data[i][j];
      }
      // Apply filters if provided
      if (filters) {
        if (filters.status && row['Status'] !== filters.status) continue;
        if (filters.department && row['Departemen'] !== filters.department) continue;
        if (filters.position && row['Posisi'] !== filters.position) continue;
        if (filters.location && row['Lokasi Kerja'] !== filters.location) continue;
        if (filters.search) {
          var q = String(filters.search).toLowerCase();
          var matchName  = String(row['Nama Lengkap']).toLowerCase().indexOf(q) !== -1;
          var matchNik   = String(row['NIK']).toLowerCase().indexOf(q) !== -1;
          var matchEmail = String(row['Email']).toLowerCase().indexOf(q) !== -1;
          if (!matchName && !matchNik && !matchEmail) continue;
        }
      }
      items.push({
        id:           row['ID'],
        nik:          row['NIK'],
        fullName:     row['Nama Lengkap'],
        email:        row['Email'],
        phone:        row['No. Handphone'],
        department:   row['Departemen'],
        position:     row['Posisi'],
        workLocation: row['Lokasi Kerja'],
        employeeType: row['Tipe Karyawan'],
        joinDate:     row['Tanggal Masuk'],
        status:       row['Status'],
        photo:        row['Foto'],
        notes:        row['Catatan HR'],
        created:      row['Dibuat'],
        modified:     row['Diubah']
      });
    }

    return { success: true, data: items, total: items.length };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= GET SINGLE EMPLOYEE =============

function getEmployeeById(id) {
  try {
    if (!id) return { success: false, message: 'ID harus diisi.' };

    var sheet = getOrCreateEmployeeSheet_();
    var data  = sheet.getDataRange().getValues();
    if (data.length <= 1) return { success: false, message: 'Data tidak ditemukan.' };

    var headers = data[0];
    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(id)) {
        var row = {};
        for (var j = 0; j < headers.length; j++) {
          row[headers[j]] = data[i][j];
        }
        return {
          success: true,
          data: {
            id:           row['ID'],
            nik:          row['NIK'],
            fullName:     row['Nama Lengkap'],
            email:        row['Email'],
            phone:        row['No. Handphone'],
            department:   row['Departemen'],
            position:     row['Posisi'],
            workLocation: row['Lokasi Kerja'],
            employeeType: row['Tipe Karyawan'],
            joinDate:     row['Tanggal Masuk'],
            status:       row['Status'],
            photo:        row['Foto'],
            notes:        row['Catatan HR'],
            created:      row['Dibuat'],
            modified:     row['Diubah']
          }
        };
      }
    }

    return { success: false, message: 'Data tidak ditemukan.' };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= ADD EMPLOYEE =============

function addEmployee(empData) {
  try {
    if (!empData || !empData.fullName) {
      return { success: false, message: 'Nama lengkap harus diisi.' };
    }

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateEmployeeSheet_();
    var now   = getNow_();
    var newId = generateEmployeeId_();

    var newRow = [
      newId,
      empData.nik          || '',
      empData.fullName     || '',
      empData.email        || '',
      empData.phone        || '',
      empData.department   || '',
      empData.position     || '',
      empData.workLocation || '',
      empData.employeeType || '',
      empData.joinDate     || now,
      empData.status       || 'Active',
      empData.photo        || '',
      empData.notes        || '',
      now,
      now
    ];

    sheet.appendRow(newRow);
    lock.releaseLock();

    return { success: true, message: 'Karyawan "' + empData.fullName + '" berhasil ditambahkan.', id: newId };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= UPDATE EMPLOYEE =============

function updateEmployee(id, updates) {
  try {
    if (!id || !updates) {
      return { success: false, message: 'ID dan data harus diisi.' };
    }

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateEmployeeSheet_();
    var data  = sheet.getDataRange().getValues();

    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(id)) {
        if (updates.nik !== undefined)          sheet.getRange(i + 1, 2).setValue(updates.nik);
        if (updates.fullName !== undefined)      sheet.getRange(i + 1, 3).setValue(updates.fullName);
        if (updates.email !== undefined)         sheet.getRange(i + 1, 4).setValue(updates.email);
        if (updates.phone !== undefined)         sheet.getRange(i + 1, 5).setValue(updates.phone);
        if (updates.department !== undefined)    sheet.getRange(i + 1, 6).setValue(updates.department);
        if (updates.position !== undefined)      sheet.getRange(i + 1, 7).setValue(updates.position);
        if (updates.workLocation !== undefined)  sheet.getRange(i + 1, 8).setValue(updates.workLocation);
        if (updates.employeeType !== undefined)  sheet.getRange(i + 1, 9).setValue(updates.employeeType);
        if (updates.joinDate !== undefined)      sheet.getRange(i + 1, 10).setValue(updates.joinDate);
        if (updates.status !== undefined)        sheet.getRange(i + 1, 11).setValue(updates.status);
        if (updates.photo !== undefined)         sheet.getRange(i + 1, 12).setValue(updates.photo);
        if (updates.notes !== undefined)         sheet.getRange(i + 1, 13).setValue(updates.notes);
        sheet.getRange(i + 1, 15).setValue(now);

        lock.releaseLock();
        return { success: true, message: 'Data karyawan berhasil diperbarui.' };
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

    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(id)) {
        var name = data[i][2];
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
      if (emp.status === 'Active') stats.active++;
      else stats.inactive++;

      var dept = emp.department || 'Belum Ditentukan';
      stats.byDepartment[dept] = (stats.byDepartment[dept] || 0) + 1;

      var type = emp.employeeType || 'Belum Ditentukan';
      stats.byType[type] = (stats.byType[type] || 0) + 1;

      var loc = emp.workLocation || 'Belum Ditentukan';
      stats.byLocation[loc] = (stats.byLocation[loc] || 0) + 1;
    });

    return { success: true, stats: stats };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}