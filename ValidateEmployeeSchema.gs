// ============================================================
// ValidateEmployeeSchema.gs — TEMPORARY VALIDATION SCRIPT
// Run validateEmployeeSchema() in Apps Script to compare:
//   1. EMPLOYEE_HEADERS (Config.gs) vs actual spreadsheet headers
//   2. All backend file column counts
// Remove this file after validation is complete.
// ============================================================

function validateEmployeeSchema() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName('Employee');

  var result = {
    configHeaders: EMPLOYEE_HEADERS,
    configCount: EMPLOYEE_HEADERS.length,
    spreadsheetHeaders: [],
    spreadsheetCount: 0,
    comparison: [],
    issues: [],
    recommendation: ''
  };

  if (!sheet) {
    result.issues.push('Sheet "Employee" does not exist.');
    result.recommendation = 'Sheet will be created on first use by getOrCreateEmployeeSheet_().';
    Logger.log(JSON.stringify(result, null, 2));
    return result;
  }

  var lastCol = sheet.getLastColumn();
  if (lastCol === 0) {
    result.issues.push('Sheet "Employee" exists but has no columns.');
    result.recommendation = 'Headers will be written on first use by getOrCreateEmployeeSheet_().';
    Logger.log(JSON.stringify(result, null, 2));
    return result;
  }

  var headers = sheet.getRange(1, 1, 1, lastCol).getValues()[0];
  result.spreadsheetHeaders = headers;
  result.spreadsheetCount = headers.length;

  // Compare column by column
  var maxLen = Math.max(EMPLOYEE_HEADERS.length, headers.length);
  for (var i = 0; i < maxLen; i++) {
    var configH = i < EMPLOYEE_HEADERS.length ? EMPLOYEE_HEADERS[i] : '(MISSING)';
    var sheetH  = i < headers.length ? headers[i] : '(MISSING)';
    var match   = configH === sheetH;
    result.comparison.push({
      index: i + 1,
      configHeader: configH,
      spreadsheetHeader: sheetH,
      match: match
    });
    if (!match) {
      result.issues.push('Column ' + (i + 1) + ': Config="' + configH + '" vs Sheet="' + sheetH + '"');
    }
  }

  // Summary
  if (result.issues.length === 0) {
    result.recommendation = 'SCHEMA IS SYNCED. No changes needed.';
  } else if (EMPLOYEE_HEADERS.length === headers.length) {
    result.recommendation = 'Column count matches (' + EMPLOYEE_HEADERS.length + ') but some headers differ. Safe to auto-rename.';
  } else {
    result.recommendation = 'Column count differs: Config=' + EMPLOYEE_HEADERS.length + ' vs Sheet=' + headers.length + '. Manual review recommended.';
  }

  Logger.log('=== EMPLOYEE SCHEMA VALIDATION ===');
  Logger.log('Config columns: ' + EMPLOYEE_HEADERS.length);
  Logger.log('Spreadsheet columns: ' + headers.length);
  Logger.log('Issues: ' + result.issues.length);
  result.issues.forEach(function(issue) { Logger.log('  ⚠️ ' + issue); });
  Logger.log('Recommendation: ' + result.recommendation);

  // Print comparison table
  Logger.log('');
  Logger.log('=== COMPARISON TABLE ===');
  result.comparison.forEach(function(row) {
    var status = row.match ? '✅' : '❌';
    Logger.log(status + ' Col ' + row.index + ': Config="' + row.configHeader + '" | Sheet="' + row.spreadsheetHeader + '"');
  });

  return result;
}

function autoFixEmployeeHeaders() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName('Employee');

  if (!sheet) {
    Logger.log('Sheet "Employee" does not exist. Will be created on first use.');
    return;
  }

  var lastCol = sheet.getLastColumn();
  var currentHeaders = lastCol > 0 ? sheet.getRange(1, 1, 1, lastCol).getValues()[0] : [];

  Logger.log('Current headers (' + currentHeaders.length + '): ' + JSON.stringify(currentHeaders));
  Logger.log('Target headers (' + EMPLOYEE_HEADERS.length + '): ' + JSON.stringify(EMPLOYEE_HEADERS));

  // Step 1: If column count differs, resize
  if (currentHeaders.length < EMPLOYEE_HEADERS.length) {
    // Add missing columns at the end
    var newCols = EMPLOYEE_HEADERS.length - currentHeaders.length;
    sheet.insertColumnsAfter(currentHeaders.length, newCols);
    Logger.log('Inserted ' + newCols + ' new columns');
  } else if (currentHeaders.length > EMPLOYEE_HEADERS.length) {
    // Extra columns — check if they have data
    var extraCount = currentHeaders.length - EMPLOYEE_HEADERS.length;
    var hasData = false;
    for (var c = EMPLOYEE_HEADERS.length + 1; c <= currentHeaders.length; c++) {
      var colData = sheet.getRange(2, c, Math.max(1, sheet.getLastRow() - 1), 1).getValues();
      for (var r = 0; r < colData.length; r++) {
        if (colData[r][0] !== '' && colData[r][0] !== null) {
          hasData = true;
          break;
        }
      }
      if (hasData) break;
    }
    if (hasData) {
      Logger.log('⚠️ ' + extraCount + ' extra columns contain data. NOT removing to prevent data loss.');
      Logger.log('Extra columns: ' + JSON.stringify(currentHeaders.slice(EMPLOYEE_HEADERS.length)));
    } else {
      Logger.log(extraCount + ' extra columns are empty. Safe to remove.');
    }
  }

  // Step 2: Update header row with correct names
  var headerRange = sheet.getRange(1, 1, 1, EMPLOYEE_HEADERS.length);
  headerRange.setValues([EMPLOYEE_HEADERS]);

  // Step 3: Style the header
  headerRange.setBackground('#005BAC')
    .setFontColor('#ffffff')
    .setFontWeight('bold');

  // Step 4: Freeze row 1
  sheet.setFrozenRows(1);

  Logger.log('✅ Headers updated successfully.');
  Logger.log('Final header count: ' + EMPLOYEE_HEADERS.length);

  // Re-validate
  validateEmployeeSchema();
}