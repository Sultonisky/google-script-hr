// ============================================================
// backend/PortalSettings.gs — PORTAL SETTINGS
// Centralized configuration stored in ScriptProperties.
// All hardcoded portal values are replaced by dynamic settings.
// ============================================================

// ---- Default portal settings ----
var PORTAL_SETTINGS_DEFAULTS = {
  // Company Information
  companyName:        'PT Mahakarya Sukses Indonesia',
  companyLogo:        '',
  companyAddress:     'Jakarta, Indonesia',
  companyPhone:       '',
  companyEmail:       '',
  website:            '',
  // Branding
  sidebarLogo:        '',
  loginLogo:          '',
  portalTitle:        'Mahakarya HRIS',
  portalSubtitle:     'Applicant Tracking System',
  footer:             '© 2026 PT Mahakarya Sukses Indonesia — HRIS Portal',
  systemVersion:      '2.0.0',
  // Theme
  primaryColor:       '#005BAC',
  themeColor:         '#005BAC',
  secondaryColor:     '#6c757d',
  darkModeDefault:    'light',
  sidebarStyle:       'dark',
  // Dashboard
  refreshInterval:    60,
  defaultStatus:      '',
  defaultSource:      '',
  defaultSort:        'created_desc',
  defaultPageSize:    10,
  // Recruitment
  defaultCandidateStatus:  'Pending',
  defaultRecruitmentSource: '',
  publicFormEnabled:  true,
  maintenanceMode:    false
};

// ---- Get all portal settings ----
function getPortalSettings() {
  try {
    var props  = PropertiesService.getScriptProperties();
    var stored = props.getProperty('portal_settings');
    var saved  = stored ? JSON.parse(stored) : {};
    // Merge defaults with saved (defaults fill missing keys)
    var merged = {};
    var keys = Object.keys(PORTAL_SETTINGS_DEFAULTS);
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      merged[k] = saved.hasOwnProperty(k) && saved[k] !== '' ? saved[k] : PORTAL_SETTINGS_DEFAULTS[k];
    }
    return { success: true, settings: merged };
  } catch (e) {
    return { success: false, message: e.toString(), settings: PORTAL_SETTINGS_DEFAULTS };
  }
}

// ---- Save portal settings ----
function savePortalSettings(settingsObj) {
  try {
    if (!settingsObj || typeof settingsObj !== 'object')
      return { success: false, message: 'Data pengaturan tidak valid.' };

    var props  = PropertiesService.getScriptProperties();
    var stored = props.getProperty('portal_settings');
    var current = stored ? JSON.parse(stored) : {};

    // Only update provided keys
    var keys = Object.keys(PORTAL_SETTINGS_DEFAULTS);
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      if (settingsObj.hasOwnProperty(k)) {
        current[k] = String(settingsObj[k]);
      }
    }

    props.setProperty('portal_settings', JSON.stringify(current));
    return { success: true, message: 'Pengaturan portal berhasil disimpan.' };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}

// ---- Get a single portal setting (for internal use) ----
function getPortalSetting_(key) {
  try {
    var props   = PropertiesService.getScriptProperties();
    var stored  = props.getProperty('portal_settings');
    var current = stored ? JSON.parse(stored) : {};
    return current[key] || PORTAL_SETTINGS_DEFAULTS[key] || '';
  } catch (e) {
    return PORTAL_SETTINGS_DEFAULTS[key] || '';
  }
}

// ---- Reset portal settings to defaults ----
function resetPortalSettings() {
  try {
    var props = PropertiesService.getScriptProperties();
    props.deleteProperty('portal_settings');
    return { success: true, message: 'Pengaturan portal berhasil direset ke default.' };
  } catch (e) {
    return { success: false, message: e.toString() };
  }
}