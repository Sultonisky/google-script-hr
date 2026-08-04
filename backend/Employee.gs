// ============================================================
// backend/Employee.gs — EMPLOYEE MANAGEMENT
// CRUD operations for master employee data, converted from
// accepted candidates. Core HR data beyond recruitment.
// ============================================================


// ============= HELPER =============

function getOrCreateEmployeeSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(EMPLOYEE_SHEET_NAME);
  if (!sheet) {
    sheet = ss.insertSheet(EMPLOYEE_SHEET_NAME);
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
        if (filters.status && row['Employment Status'] !== filters.status) continue;
        if (filters.department && row['Department'] !== filters.department) continue;
        if (filters.position && row['Position'] !== filters.position) continue;
        if (filters.search) {
          var q = String(filters.search).toLowerCase();
          var matchName  = String(row['Full Name']).toLowerCase().indexOf(q) !== -1;
          var matchNik   = String(row['NIK']).toLowerCase().indexOf(q) !== -1;
          var matchEmail = String(row['Email']).toLowerCase().indexOf(q) !== -1;
          if (!matchName && !matchNik && !matchEmail) continue;
        }
      }
      items.push({
        employeeId:      row['Employee ID'],
        companyEntity:   row['Company Entity'],
        employeeType:    row['Employee Type'],
        fullName:        row['Full Name'],
        nik:             row['NIK'],
        birthDate:       row['Birth Date'],
        age:             row['Age'],
        gender:          row['Gender'],
        maritalStatus:   row['Marital Status'],
        email:           row['Email'],
        phone:           row['Phone'],
        address:         row['Address'],
        city:            row['City'],
        education:       row['Education'],
        workExperience:  row['Work Experience'],
        department:      row['Department'],
        position:        row['Position'],
        joinDate:        row['Join Date'],
        contractStart:   row['Contract Start'],
        contractEnd:     row['Contract End'],
        contractDuration:row['Contract Duration'],
        employmentStatus:row['Employment Status'],
        salary:          row['Salary'],
        salaryType:      row['Salary Type'],
        outsourceVendor: row['Outsource Vendor'],
        contractNumber:  row['Contract Number'],
        district:        row['District'],
        recruitmentId:   row['Recruitment ID'],
        recruitmentSource:row['Recruitment Source'],
        hrNotes:         row['HR Notes'],
        createdBy:       row['Created By'],
        updatedAt:       row['Updated At']
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
            employeeId:      row['Employee ID'],
            companyEntity:   row['Company Entity'],
            employeeType:    row['Employee Type'],
            fullName:        row['Full Name'],
            nik:             row['NIK'],
            birthDate:       row['Birth Date'],
            age:             row['Age'],
            gender:          row['Gender'],
            maritalStatus:   row['Marital Status'],
            email:           row['Email'],
            phone:           row['Phone'],
            address:         row['Address'],
            city:            row['City'],
            education:       row['Education'],
            workExperience:  row['Work Experience'],
            department:      row['Department'],
            position:        row['Position'],
            joinDate:        row['Join Date'],
            contractStart:   row['Contract Start'],
            contractEnd:     row['Contract End'],
            contractDuration:row['Contract Duration'],
            employmentStatus:row['Employment Status'],
            salary:          row['Salary'],
            salaryType:      row['Salary Type'],
            outsourceVendor: row['Outsource Vendor'],
            contractNumber:  row['Contract Number'],
            district:        row['District'],
            recruitmentId:   row['Recruitment ID'],
            recruitmentSource:row['Recruitment Source'],
            hrNotes:         row['HR Notes'],
            createdBy:       row['Created By'],
            updatedAt:       row['Updated At']
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
      newId,                                    // Employee ID
      empData.companyEntity   || '',            // Company Entity
      empData.employeeType    || '',            // Employee Type
      empData.fullName        || '',            // Full Name
      empData.nik             || '',            // NIK
      empData.birthDate       || '',            // Birth Date
      empData.age             || '',            // Age
      empData.gender          || '',            // Gender
      empData.maritalStatus   || '',            // Marital Status
      empData.email           || '',            // Email
      empData.phone           || '',            // Phone
      empData.address         || '',            // Address
      empData.city            || '',            // City
      empData.education       || '',            // Education
      empData.workExperience  || '',            // Work Experience
      empData.department      || '',            // Department
      empData.position        || '',            // Position
      empData.joinDate        || now,           // Join Date
      empData.contractStart   || '',            // Contract Start
      empData.contractEnd     || '',            // Contract End
      empData.contractDuration|| '',            // Contract Duration
      empData.employmentStatus|| 'Active',      // Employment Status
      empData.salary          || '',            // Salary
      empData.salaryType      || '',            // Salary Type
      empData.outsourceVendor || '',            // Outsource Vendor
      empData.contractNumber  || '',            // Contract Number
      empData.district        || '',            // District
      empData.recruitmentId   || '',            // Recruitment ID
      empData.recruitmentSource||'',            // Recruitment Source
      empData.hrNotes         || '',            // HR Notes
      empData.createdBy       || '',            // Created By
      now                                         // Updated At
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
        // Col indices: 1=Employee ID, 2=Company Entity, 3=Employee Type, 4=Full Name,
        // 5=NIK, 6=Birth Date, 7=Age, 8=Gender, 9=Marital Status, 10=Email,
        // 11=Phone, 12=Address, 13=City, 14=Education, 15=Work Experience,
        // 16=Department, 17=Position, 18=Join Date, 19=Contract Start,
        // 20=Contract End, 21=Contract Duration, 22=Employment Status,
        // 23=Salary, 24=Salary Type, 25=Outsource Vendor, 26=Contract Number,
        // 27=District, 28=Recruitment ID, 29=Recruitment Source,
        // 30=HR Notes, 31=Created By, 32=Updated At
        if (updates.companyEntity !== undefined)   sheet.getRange(i + 1, 2).setValue(updates.companyEntity);
        if (updates.employeeType !== undefined)     sheet.getRange(i + 1, 3).setValue(updates.employeeType);
        if (updates.fullName !== undefined)         sheet.getRange(i + 1, 4).setValue(updates.fullName);
        if (updates.nik !== undefined)              sheet.getRange(i + 1, 5).setValue(updates.nik);
        if (updates.birthDate !== undefined)        sheet.getRange(i + 1, 6).setValue(updates.birthDate);
        if (updates.age !== undefined)              sheet.getRange(i + 1, 7).setValue(updates.age);
        if (updates.gender !== undefined)           sheet.getRange(i + 1, 8).setValue(updates.gender);
        if (updates.maritalStatus !== undefined)    sheet.getRange(i + 1, 9).setValue(updates.maritalStatus);
        if (updates.email !== undefined)            sheet.getRange(i + 1, 10).setValue(updates.email);
        if (updates.phone !== undefined)            sheet.getRange(i + 1, 11).setValue(updates.phone);
        if (updates.address !== undefined)          sheet.getRange(i + 1, 12).setValue(updates.address);
        if (updates.city !== undefined)             sheet.getRange(i + 1, 13).setValue(updates.city);
        if (updates.education !== undefined)        sheet.getRange(i + 1, 14).setValue(updates.education);
        if (updates.workExperience !== undefined)   sheet.getRange(i + 1, 15).setValue(updates.workExperience);
        if (updates.department !== undefined)       sheet.getRange(i + 1, 16).setValue(updates.department);
        if (updates.position !== undefined)         sheet.getRange(i + 1, 17).setValue(updates.position);
        if (updates.joinDate !== undefined)         sheet.getRange(i + 1, 18).setValue(updates.joinDate);
        if (updates.contractStart !== undefined)    sheet.getRange(i + 1, 19).setValue(updates.contractStart);
        if (updates.contractEnd !== undefined)      sheet.getRange(i + 1, 20).setValue(updates.contractEnd);
        if (updates.contractDuration !== undefined) sheet.getRange(i + 1, 21).setValue(updates.contractDuration);
        if (updates.employmentStatus !== undefined) sheet.getRange(i + 1, 22).setValue(updates.employmentStatus);
        if (updates.salary !== undefined)           sheet.getRange(i + 1, 23).setValue(updates.salary);
        if (updates.salaryType !== undefined)       sheet.getRange(i + 1, 24).setValue(updates.salaryType);
        if (updates.outsourceVendor !== undefined)  sheet.getRange(i + 1, 25).setValue(updates.outsourceVendor);
        if (updates.contractNumber !== undefined)   sheet.getRange(i + 1, 26).setValue(updates.contractNumber);
        if (updates.district !== undefined)         sheet.getRange(i + 1, 27).setValue(updates.district);
        if (updates.recruitmentId !== undefined)    sheet.getRange(i + 1, 28).setValue(updates.recruitmentId);
        if (updates.recruitmentSource !== undefined)sheet.getRange(i + 1, 29).setValue(updates.recruitmentSource);
        if (updates.hrNotes !== undefined)          sheet.getRange(i + 1, 30).setValue(updates.hrNotes);
        if (updates.createdBy !== undefined)        sheet.getRange(i + 1, 31).setValue(updates.createdBy);
        sheet.getRange(i + 1, 32).setValue(now);

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
        var name = data[i][3];
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
      if (emp.employmentStatus === 'Active') stats.active++;
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