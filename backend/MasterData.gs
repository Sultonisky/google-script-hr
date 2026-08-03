// ============================================================
// backend/MasterData.gs — MASTER DATA CRUD
// Manages dropdown options: recruitment sources, statuses,
// departments, positions, work locations, employee types.
// ============================================================

var MASTER_DATA_SHEET   = 'master_data';
var MASTER_DATA_HEADERS = ['ID', 'Kategori', 'Nama', 'Deskripsi', 'Urutan', 'Aktif', 'Dibuat', 'Diubah'];

var MASTER_DATA_CATEGORIES = {
  recruitment_source: { label: 'Sumber Rekrutmen', icon: 'bi-link-45deg' },
  candidate_status:   { label: 'Status Kandidat',   icon: 'bi-flag-fill' },
  department:         { label: 'Departemen',         icon: 'bi-building' },
  position:           { label: 'Posisi',             icon: 'bi-person-workspace' },
  work_location:      { label: 'Lokasi Kerja',       icon: 'bi-geo-alt-fill' },
  employee_type:      { label: 'Tipe Karyawan',      icon: 'bi-person-badge' }
};

var DEFAULT_MASTER_DATA = {
  recruitment_source: ['Website', 'Job Fair', 'Referral', 'Social Media', 'Agency', 'Walk In', 'Other'],
  candidate_status:   ['Pending', 'Interview', 'Accepted', 'Hold', 'Blacklist', 'Rejected'],
  department:         ['Human Resources', 'Finance', 'Marketing', 'Operations', 'IT', 'Sales', 'Legal'],
  position:           ['Staff', 'Supervisor', 'Manager', 'Director', 'Intern', 'Outsource'],
  work_location:      ['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Medan'],
  employee_type:      ['Full Time', 'Part Time', 'Contract', 'Intern', 'Outsource']
};

// ============= HELPER =============

function getOrCreateMasterDataSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(MASTER_DATA_SHEET);
  if (!sheet) {
    sheet = ss.insertSheet(MASTER_DATA_SHEET);
    sheet.getRange(1, 1, 1, MASTER_DATA_HEADERS.length).setValues([MASTER_DATA_HEADERS]);
    sheet.getRange(1, 1, 1, MASTER_DATA_HEADERS.length)
      .setBackground('#005BAC').setFontColor('#ffffff').setFontWeight('bold');
    sheet.setFrozenRows(1);
    autoResizeColumns_(sheet, MASTER_DATA_HEADERS.length);
    initializeDefaultMasterData_();
  }
  return sheet;
}

function initializeDefaultMasterData_() {
  var sheet = getOrCreateMasterDataSheet_();
  var rows = [];
  var now = getNow_();
  var idCounter = 1;

  Object.keys(DEFAULT_MASTER_DATA).forEach(function(category) {
    var items = DEFAULT_MASTER_DATA[category];
    for (var i = 0; i < items.length; i++) {
      rows.push([
        'MD-' + padNumber_(idCounter, 4),
        category,
        items[i],
        '',
        i + 1,
        'TRUE',
        now,
        now
      ]);
      idCounter++;
    }
  });

  if (rows.length > 0) {
    sheet.getRange(2, 1, rows.length, MASTER_DATA_HEADERS.length).setValues(rows);
  }
}

function padNumber_(num, size) {
  var s = String(num);
  while (s.length < size) s = '0' + s;
  return s;
}

function generateMasterDataId_() {
  var props = PropertiesService.getScriptProperties();
  var counter = parseInt(props.getProperty('master_data_counter') || '0', 10);
  counter++;
  props.setProperty('master_data_counter', String(counter));
  return 'MD-' + padNumber_(counter, 4);
}

// ============= GET ALL MASTER DATA =============

function getMasterDataList() {
  try {
    var sheet = getOrCreateMasterDataSheet_();
    var data  = sheet.getDataRange().getValues();
    if (data.length <= 1) return { success: true, data: [], categories: MASTER_DATA_CATEGORIES };

    var headers = data[0];
    var items   = [];
    for (var i = 1; i < data.length; i++) {
      var row = {};
      for (var j = 0; j < headers.length; j++) {
        row[headers[j]] = data[i][j];
      }
      // Only include active items
      if (String(row['Aktif']).toUpperCase() === 'TRUE') {
        items.push({
          id:          row['ID'],
          category:    row['Kategori'],
          name:        row['Nama'],
          description: row['Deskripsi'],
          order:       row['Urutan'],
          active:      true,
          created:     row['Dibuat'],
          modified:    row['Diubah']
        });
      }
    }

    return { success: true, data: items, categories: MASTER_DATA_CATEGORIES };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= GET ITEMS BY CATEGORY =============

function getMasterDataByCategory(category) {
  try {
    var result = getMasterDataList();
    if (!result.success) return result;

    var filtered = result.data.filter(function(item) {
      return item.category === category;
    });

    // Sort by order
    filtered.sort(function(a, b) { return (a.order || 0) - (b.order || 0); });

    return { success: true, data: filtered };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= GET NAMES BY CATEGORY (for dropdowns) =============

function getMasterDataNamesByCategory(category) {
  try {
    var result = getMasterDataByCategory(category);
    if (!result.success) return { success: false, data: [] };

    var names = result.data.map(function(item) { return item.name; });
    return { success: true, data: names };
  } catch (e) {
    return { success: false, message: e.toString(), data: [] };
  }
}

// ============= GET ALL CATEGORIES SUMMARY =============

function getMasterDataSummary() {
  try {
    var result = getMasterDataList();
    if (!result.success) return result;

    var summary = {};
    Object.keys(MASTER_DATA_CATEGORIES).forEach(function(cat) {
      var items = result.data.filter(function(item) { return item.category === cat; });
      summary[cat] = {
        label: MASTER_DATA_CATEGORIES[cat].label,
        icon:  MASTER_DATA_CATEGORIES[cat].icon,
        count: items.length,
        items: items.map(function(item) { return item.name; })
      };
    });

    return { success: true, summary: summary };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= ADD MASTER DATA ITEM =============

function addMasterDataItem(category, name, description) {
  try {
    if (!category || !name) {
      return { success: false, message: 'Kategori dan nama harus diisi.' };
    }

    if (!MASTER_DATA_CATEGORIES[category]) {
      return { success: false, message: 'Kategori tidak valid: ' + category };
    }

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateMasterDataSheet_();
    var data  = sheet.getDataRange().getValues();

    // Check duplicate
    for (var i = 1; i < data.length; i++) {
      if (String(data[i][1]) === category && String(data[i][2]).toLowerCase() === String(name).toLowerCase()) {
        lock.releaseLock();
        return { success: false, message: 'Item "' + name + '" sudah ada dalam kategori ini.' };
      }
    }

    // Get max order
    var maxOrder = 0;
    for (var i = 1; i < data.length; i++) {
      if (String(data[i][1]) === category) {
        var ord = parseInt(data[i][4], 10) || 0;
        if (ord > maxOrder) maxOrder = ord;
      }
    }

    var now  = getNow_();
    var newId = generateMasterDataId_();
    var newRow = [newId, category, name, description || '', maxOrder + 1, 'TRUE', now, now];

    sheet.appendRow(newRow);
    lock.releaseLock();

    return { success: true, message: 'Item "' + name + '" berhasil ditambahkan.', id: newId };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= UPDATE MASTER DATA ITEM =============

function updateMasterDataItem(id, updates) {
  try {
    if (!id || !updates) {
      return { success: false, message: 'ID dan data harus diisi.' };
    }

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateMasterDataSheet_();
    var data  = sheet.getDataRange().getValues();

    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(id)) {
        // Check duplicate name if updating name
        if (updates.name) {
          for (var j = 1; j < data.length; j++) {
            if (i !== j && String(data[j][1]) === String(data[i][1]) &&
                String(data[j][2]).toLowerCase() === String(updates.name).toLowerCase()) {
              lock.releaseLock();
              return { success: false, message: 'Item "' + updates.name + '" sudah ada dalam kategori ini.' };
            }
          }
          sheet.getRange(i + 1, 3).setValue(updates.name);
        }
        if (updates.description !== undefined) sheet.getRange(i + 1, 4).setValue(updates.description);
        if (updates.order !== undefined)       sheet.getRange(i + 1, 5).setValue(updates.order);
        if (updates.active !== undefined)      sheet.getRange(i + 1, 6).setValue(updates.active ? 'TRUE' : 'FALSE');
        sheet.getRange(i + 1, 8).setValue(getNow_());

        lock.releaseLock();
        return { success: true, message: 'Item berhasil diperbarui.' };
      }
    }

    lock.releaseLock();
    return { success: false, message: 'Item tidak ditemukan.' };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= DELETE MASTER DATA ITEM (soft delete) =============

function deleteMasterDataItem(id) {
  try {
    if (!id) return { success: false, message: 'ID harus diisi.' };

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateMasterDataSheet_();
    var data  = sheet.getDataRange().getValues();

    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(id)) {
        sheet.getRange(i + 1, 6).setValue('FALSE');
        sheet.getRange(i + 1, 8).setValue(getNow_());
        lock.releaseLock();
        return { success: true, message: 'Item berhasil dihapus.' };
      }
    }

    lock.releaseLock();
    return { success: false, message: 'Item tidak ditemukan.' };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= REORDER ITEMS =============

function reorderMasterDataItems(orderedIds) {
  try {
    if (!orderedIds || !Array.isArray(orderedIds)) {
      return { success: false, message: 'Data urutan tidak valid.' };
    }

    var lock = LockService.getScriptLock();
    lock.waitLock(5000);

    var sheet = getOrCreateMasterDataSheet_();
    var data  = sheet.getDataRange().getValues();

    for (var i = 1; i < data.length; i++) {
      var idx = orderedIds.indexOf(String(data[i][0]));
      if (idx !== -1) {
        sheet.getRange(i + 1, 5).setValue(idx + 1);
      }
    }

    lock.releaseLock();
    return { success: true, message: 'Urutan berhasil diperbarui.' };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}