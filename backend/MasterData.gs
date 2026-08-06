// ============================================================
// backend/MasterData.gs — MASTER DATA CRUD
// Manages dropdown options: recruitment sources, statuses,
// departments, positions, work locations, employee types.
// Reads dynamically from Employee sheet (distinct column values)
// and falls back to DEFAULT_MASTER_DATA for categories not
// present in Employee sheet (e.g. offboarding_type).
// ============================================================

// ============= HELPER =============

function _getMasterDataSheet_() {
  try {
    return SpreadsheetApp.getActiveSpreadsheet().getSheetByName(
      EMPLOYEE_SHEET_NAME,
    );
  } catch (e) {
    return null;
  }
}

function _getColumnIndex_(headers) {
  var map = {};
  if (!headers) return map;
  for (var i = 0; i < headers.length; i++) {
    map[String(headers[i]).trim()] = i;
  }
  return map;
}

// ============= GET ALL MASTER DATA =============

function getMasterDataList() {
  try {
    var sheet = _getMasterDataSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      var fallback = [];
      var order = 1;
      Object.keys(MASTER_DATA_CATEGORIES).forEach(function (cat) {
        var defaults = DEFAULT_MASTER_DATA[cat] || [];
        defaults.forEach(function (name) {
          fallback.push({
            id: "MD-" + order,
            category: cat,
            name: name,
            description: "",
            order: order,
            active: true,
            created: "",
            modified: "",
          });
          order++;
        });
      });
      return {
        success: true,
        data: fallback,
        categories: MASTER_DATA_CATEGORIES,
      };
    }

    var data = sheet.getDataRange().getValues();
    var headers = data[0];
    var col = _getColumnIndex_(headers);

    // Track seen values per category to deduplicate Employee sheet rows
    var seenFromSheet = {};
    var items = [];
    for (var r = 1; r < data.length; r++) {
      var row = data[r];
      if (!row.join("").toString().trim()) continue;
      var order = parseInt(row[col["Order"]] || 1, 10) || r;
      Object.keys(MASTER_DATA_CATEGORIES).forEach(function (cat) {
        var empCol = MASTER_DATA_EMPLOYEE_COL_MAP[cat];
        if (empCol && col[empCol] !== undefined) {
          var val = String(row[col[empCol]] || "").trim();
          if (!val) return;
          var dupKey = cat + "|" + val.toLowerCase();
          if (seenFromSheet[dupKey]) return; // skip duplicate from sheet rows
          seenFromSheet[dupKey] = true;
          items.push({
            id: cat + "-" + r,
            category: cat,
            name: val,
            description: "",
            order: order,
            active: true,
            created: String(row[col["Created At"]] || ""),
            modified: String(row[col["Updated At"]] || ""),
          });
        }
      });
    }

    // Merge with defaults for EVERY category.
    // Categories derived from the Employee sheet (MASTER_DATA_EMPLOYEE_COL_MAP,
    // e.g. recruitment_source) still receive their DEFAULT_MASTER_DATA values as
    // a fallback when the sheet has no recorded entries for that column — which
    // is the normal case for recruitment_source since the value is only known at
    // candidate-application time, not for existing employees. This keeps the
    // registration-form dropdowns populated instead of rendering empty.
    // Duplicate values (same category + name, case-insensitive) are skipped so
    // values recorded on the Employee sheet always take precedence.
    var seen = {};
    items.forEach(function (it) {
      seen[it.category + "|" + it.name.toLowerCase()] = true;
    });

    var orderCounter = items.length + 1;
    Object.keys(MASTER_DATA_CATEGORIES).forEach(function (cat) {
      var defaults = DEFAULT_MASTER_DATA[cat] || [];
      defaults.forEach(function (name) {
        var key = cat + "|" + name.toLowerCase();
        if (seen[key]) return;
        seen[key] = true;
        items.push({
          id: "MD-" + orderCounter,
          category: cat,
          name: name,
          description: "",
          order: orderCounter,
          active: true,
          created: "",
          modified: "",
        });
        orderCounter++;
      });
    });

    return {
      success: true,
      data: items,
      categories: MASTER_DATA_CATEGORIES,
    };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= GET ITEMS BY CATEGORY =============

function getMasterDataByCategory(category) {
  try {
    var result = getMasterDataList();
    if (!result.success) return result;

    var filtered;
    if (category) {
      filtered = result.data.filter(function (item) {
        return item.category === category;
      });
    } else {
      filtered = result.data;
    }

    filtered.sort(function (a, b) {
      return (a.order || 0) - (b.order || 0);
    });

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

    var names = result.data.map(function (item) {
      return item.name;
    });
    return { success: true, data: names };
  } catch (e) {
    return { success: false, message: e.toString(), data: [] };
  }
}

// ============= GET ALL CATEGORIES GROUPED (for dropdowns) =============

function getMasterDataGrouped() {
  try {
    var result = getMasterDataList();
    if (!result.success) return result;

    var grouped = {};
    Object.keys(MASTER_DATA_CATEGORIES).forEach(function (cat) {
      grouped[cat] = result.data
        .filter(function (item) {
          return item.category === cat;
        })
        .sort(function (a, b) {
          return (a.order || 0) - (b.order || 0);
        })
        .map(function (item) {
          return item.name;
        });
    });

    return { success: true, data: grouped };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= GET MULTIPLE CATEGORIES (BATCH FOR DROPDOWNS) =============

function getMasterDataForCategories(categories) {
  try {
    if (
      !categories ||
      !Array.isArray(categories) ||
      categories.length === 0
    ) {
      categories = Object.keys(MASTER_DATA_CATEGORIES);
    }

    var result = getMasterDataList();
    if (!result.success) return result;

    var grouped = {};
    categories.forEach(function (cat) {
      if (!MASTER_DATA_CATEGORIES[cat]) {
        grouped[cat] = { label: cat, items: [] };
        return;
      }
      var items = result.data
        .filter(function (item) {
          return item.category === cat;
        })
        .sort(function (a, b) {
          return (a.order || 0) - (b.order || 0);
        })
        .map(function (item) {
          return {
            name: item.name,
            displayName: item.description || item.name,
          };
        });
      grouped[cat] = {
        label: MASTER_DATA_CATEGORIES[cat].label,
        items: items,
      };
    });

    return { success: true, data: grouped };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= GET ALL CATEGORIES SUMMARY =============

function getMasterDataSummary() {
  try {
    var result = getMasterDataList();
    if (!result.success) return result;

    var summary = {};
    Object.keys(MASTER_DATA_CATEGORIES).forEach(function (cat) {
      var items = result.data.filter(function (item) {
        return item.category === cat;
      });
      summary[cat] = {
        label: MASTER_DATA_CATEGORIES[cat].label,
        icon: MASTER_DATA_CATEGORIES[cat].icon,
        count: items.length,
        items: items.map(function (item) {
          return item.name;
        }),
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
      return {
        success: false,
        message: "Kategori dan nama harus diisi.",
      };
    }

    var empCol = MASTER_DATA_EMPLOYEE_COL_MAP[category];
    if (empCol) {
      return {
        success: false,
        message:
          'Kategori "' +
          category +
          '" dikelola otomatis dari data karyawan. Penambahan manual tidak tersedia.',
      };
    }

    if (!MASTER_DATA_CATEGORIES[category]) {
      return {
        success: false,
        message: "Kategori tidak valid: " + category,
      };
    }

    return {
      success: false,
      message:
        'Kategori "' +
        category +
        '" tidak memiliki sheet master data. Nilai diambil dari DEFAULT_MASTER_DATA.',
    };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= UPDATE MASTER DATA ITEM =============

function updateMasterDataItem(id, updates) {
  try {
    if (!id || !updates) {
      return {
        success: false,
        message: "ID dan data harus diisi.",
      };
    }

    return {
      success: false,
      message:
        "Master data tidak dapat diubah — nilainya dihasilkan otomatis dari sheet Employee.",
    };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= DELETE MASTER DATA ITEM (soft delete) =============

function deleteMasterDataItem(id) {
  try {
    if (!id)
      return { success: false, message: "ID harus diisi." };

    return {
      success: false,
      message:
        "Master data tidak dapat dihapus — nilainya dihasilkan otomatis dari sheet Employee.",
    };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ============= REORDER ITEMS =============

function reorderMasterDataItems(orderedIds) {
  try {
    if (!orderedIds || !Array.isArray(orderedIds)) {
      return {
        success: false,
        message: "Data urutan tidak valid.",
      };
    }

    return {
      success: false,
      message:
        "Master data tidak dapat diurutkan — nilainya dihasilkan otomatis dari sheet Employee.",
    };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}