// ============================================================
// backend/Auth.gs — AUTHENTICATION & AUTHORIZATION
// Google Workspace SSO via Apps Script Session.
// Recruitment forms remain PUBLIC. Dashboard requires login.
// ============================================================

var USERS_SHEET_NAME = 'Users';
var SUPER_ADMIN_ROLE = 'Super Admin';

var USER_HEADERS = [
  'Email',
  'Full Name',
  'Role',
  'Status',
  'Last Login',
  'Created At',
  'Updated At',
  'Created By'
];

// Valid roles in hierarchy order
var VALID_ROLES = ['Super Admin', 'HR Admin', 'Recruiter', 'Manager', 'Viewer'];

// Permission definitions per role
var ROLE_PERMISSIONS = {
  'Super Admin': [
    'view_dashboard', 'manage_users', 'manage_settings', 'view_recruitment',
    'edit_recruitment', 'delete_recruitment', 'bulk_actions', 'import_data',
    'export_data', 'view_audit_log', 'manage_master_data', 'view_employee',
    'edit_employee', 'manage_portal_settings'
  ],
  'HR Admin': [
    'view_dashboard', 'view_recruitment', 'edit_recruitment', 'delete_recruitment',
    'bulk_actions', 'import_data', 'export_data', 'view_audit_log',
    'view_employee', 'edit_employee'
  ],
  'Recruiter': [
    'view_dashboard', 'view_recruitment', 'edit_recruitment', 'export_data'
  ],
  'Manager': [
    'view_dashboard', 'view_recruitment', 'export_data', 'view_employee'
  ],
  'Viewer': [
    'view_dashboard', 'view_recruitment'
  ]
};

// ============================================================
// SESSION CHECK — Called from client-side on every page load
// ============================================================

/**
 * Checks if the current user is logged in and returns their profile.
 * Called by client-side JS on dashboard load.
 * @returns {Object} { isLoggedIn, email, fullName, role, permissions }
 */
function getCurrentUser() {
  try {
    var email = Session.getActiveUser().getEmail();
    var photoUrl = '';
    try { photoUrl = Session.getActiveUser().getPhotoUrl() || ''; } catch(ex) {}
    if (!email || email === '') {
      return { isLoggedIn: false, email: '', fullName: '', role: '', permissions: [], photoUrl: '' };
    }

    var user = findUserByEmail_(email);
    if (!user) {
      return { isLoggedIn: false, email: email, fullName: '', role: '', permissions: [], photoUrl: photoUrl };
    }

    if (user.status !== 'Active') {
      return { isLoggedIn: false, email: email, fullName: user.fullName, role: user.role, permissions: [], reason: 'inactive', photoUrl: photoUrl };
    }

    // Update last login
    updateLastLogin_(email);

    return {
      isLoggedIn: true,
      email: email,
      fullName: user.fullName,
      role: user.role,
      permissions: ROLE_PERMISSIONS[user.role] || [],
      photoUrl: photoUrl
    };
  } catch (e) {
    Logger.log('getCurrentUser error: ' + e.message);
    return { isLoggedIn: false, email: '', fullName: '', role: '', permissions: [], photoUrl: '' };
  }
}

/**
 * Server-side authorization check. Returns true if user has the permission.
 * Use this in all protected backend functions.
 * @param {string} permission - The permission key to check
 * @returns {boolean}
 */
function requirePermission(permission) {
  var user = getCurrentUser();
  if (!user.isLoggedIn || !user.permissions) return false;
  return user.permissions.indexOf(permission) !== -1;
}

/**
 * Server-side role check. Returns true if user has at least the specified role.
 * Role hierarchy: Super Admin > HR Admin > Recruiter > Manager > Viewer
 * @param {string} minRole - Minimum role required
 * @returns {boolean}
 */
function requireRole(minRole) {
  var user = getCurrentUser();
  if (!user.isLoggedIn) return false;
  var userLevel = VALID_ROLES.indexOf(user.role);
  var requiredLevel = VALID_ROLES.indexOf(minRole);
  if (userLevel === -1 || requiredLevel === -1) return false;
  return userLevel <= requiredLevel; // Lower index = higher privilege
}

// ============================================================
// USER MANAGEMENT CRUD
// ============================================================

/**
 * Returns the Users sheet, creating it if necessary.
 */
function getUsersSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(USERS_SHEET_NAME);
  if (!sheet) {
    sheet = ss.insertSheet(USERS_SHEET_NAME);
    sheet.getRange(1, 1, 1, USER_HEADERS.length).setValues([USER_HEADERS]);
    sheet.getRange(1, 1, 1, USER_HEADERS.length)
      .setFontWeight('bold')
      .setBackground('#005BAC')
      .setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, USER_HEADERS.length);
  }
  return sheet;
}

/**
 * Finds a user by email (case-insensitive).
 * @param {string} email
 * @returns {Object|null} { email, fullName, role, status, lastLogin, createdAt, updatedAt, createdBy, rowIndex }
 */
function findUserByEmail_(email) {
  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) return null;

  var data = sheet.getRange(2, 1, lastRow - 1, USER_HEADERS.length).getValues();
  var lowerEmail = email.toLowerCase();

  for (var i = 0; i < data.length; i++) {
    if (String(data[i][0]).trim().toLowerCase() === lowerEmail) {
      return {
        email: String(data[i][0]).trim(),
        fullName: String(data[i][1]).trim(),
        role: String(data[i][2]).trim(),
        status: String(data[i][3]).trim(),
        lastLogin: data[i][4],
        createdAt: data[i][5],
        updatedAt: data[i][6],
        createdBy: String(data[i][7]).trim(),
        rowIndex: i + 2
      };
    }
  }
  return null;
}

/**
 * Updates the last login timestamp for a user.
 */
function updateLastLogin_(email) {
  var user = findUserByEmail_(email);
  if (!user) return;
  var sheet = getUsersSheet_();
  var now = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
  sheet.getRange(user.rowIndex, 5).setValue(now);
}

/**
 * Gets all users. Requires manage_users permission.
 * @returns {Object} { success, users }
 */
function getAllUsers() {
  if (!requirePermission('manage_users')) {
    return { success: false, error: 'Akses ditolak. Hanya Super Admin yang dapat mengelola pengguna.', users: [] };
  }

  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) return { success: true, users: [] };

  var data = sheet.getRange(2, 1, lastRow - 1, USER_HEADERS.length).getValues();
  var users = data.map(function(row) {
    return {
      email: String(row[0]).trim(),
      fullName: String(row[1]).trim(),
      role: String(row[2]).trim(),
      status: String(row[3]).trim(),
      lastLogin: row[4] ? String(row[4]) : '',
      createdAt: row[5] ? String(row[5]) : '',
      updatedAt: row[6] ? String(row[6]) : '',
      createdBy: String(row[7]).trim()
    };
  });

  return { success: true, users: users };
}

/**
 * Adds a new user. Requires manage_users permission.
 * @param {Object} userData - { email, fullName, role }
 * @returns {Object}
 */
function addUser(userData) {
  if (!requirePermission('manage_users')) {
    return { success: false, error: 'Akses ditolak.' };
  }

  var currentUser = getCurrentUser();
  var email = String(userData.email || '').trim().toLowerCase();
  var fullName = String(userData.fullName || '').trim();
  var role = String(userData.role || 'Viewer').trim();

  // Validation
  if (!email || !fullName) {
    return { success: false, error: 'Email dan Nama Lengkap wajib diisi.' };
  }
  if (VALID_ROLES.indexOf(role) === -1) {
    return { success: false, error: 'Role tidak valid: ' + role };
  }
  if (findUserByEmail_(email)) {
    return { success: false, error: 'Email sudah terdaftar: ' + email };
  }

  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    var sheet = getUsersSheet_();
    var now = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
    sheet.appendRow([email, fullName, role, 'Active', '', now, now, currentUser.email]);
    return { success: true, message: 'Pengguna berhasil ditambahkan.' };
  } catch (e) {
    return { success: false, error: 'Gagal menyimpan: ' + e.message };
  } finally {
    lock.releaseLock();
  }
}

/**
 * Updates an existing user's profile or role. Requires manage_users permission.
 * @param {string} email - The user's email (identifier)
 * @param {Object} updates - { fullName, role, status }
 * @returns {Object}
 */
function updateUser(email, updates) {
  if (!requirePermission('manage_users')) {
    return { success: false, error: 'Akses ditolak.' };
  }

  var user = findUserByEmail_(email);
  if (!user) {
    return { success: false, error: 'Pengguna tidak ditemukan.' };
  }

  var sheet = getUsersSheet_();
  var now = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');

  if (updates.fullName) sheet.getRange(user.rowIndex, 2).setValue(String(updates.fullName).trim());
  if (updates.role && VALID_ROLES.indexOf(updates.role) !== -1) sheet.getRange(user.rowIndex, 3).setValue(updates.role);
  if (updates.status && (updates.status === 'Active' || updates.status === 'Inactive')) sheet.getRange(user.rowIndex, 4).setValue(updates.status);
  sheet.getRange(user.rowIndex, 7).setValue(now);

  return { success: true, message: 'Pengguna berhasil diperbarui.' };
}

/**
 * Deletes a user. Requires manage_users permission.
 * Prevents deleting yourself.
 * @param {string} email
 * @returns {Object}
 */
function deleteUser(email) {
  if (!requirePermission('manage_users')) {
    return { success: false, error: 'Akses ditolak.' };
  }

  var currentUser = getCurrentUser();
  if (email.toLowerCase() === currentUser.email.toLowerCase()) {
    return { success: false, error: 'Tidak dapat menghapus akun sendiri.' };
  }

  var user = findUserByEmail_(email);
  if (!user) {
    return { success: false, error: 'Pengguna tidak ditemukan.' };
  }

  var sheet = getUsersSheet_();
  sheet.deleteRow(user.rowIndex);
  return { success: true, message: 'Pengguna berhasil dihapus.' };
}

/**
 * Seeds the first Super Admin if the Users sheet is empty.
 * Called during initial setup or from the script editor.
 */
function seedSuperAdmin(email, fullName) {
  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow > 1) {
    return { success: false, error: 'Users sheet sudah berisi data. Gunakan addUser untuk menambah pengguna.' };
  }

  var now = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
  sheet.appendRow([String(email).trim().toLowerCase(), String(fullName).trim(), SUPER_ADMIN_ROLE, 'Active', '', now, now, 'system']);
  return { success: true, message: 'Super Admin berhasil dibuat: ' + email };
}

/**
 * Promotes a user to Super Admin. Only callable by existing Super Admin.
 * @param {string} email
 * @returns {Object}
 */
function promoteToSuperAdmin(email) {
  var currentUser = getCurrentUser();
  if (!currentUser.isLoggedIn || currentUser.role !== SUPER_ADMIN_ROLE) {
    return { success: false, error: 'Hanya Super Admin yang dapat mengubah role ke Super Admin.' };
  }
  return updateUser(email, { role: SUPER_ADMIN_ROLE });
}

/**
 * Checks if a specific user has a specific permission.
 * Client-safe: returns boolean.
 * @param {string} permission
 * @returns {boolean}
 */
function checkPermission(permission) {
  return requirePermission(permission);
}

// ============================================================
// LOGIN PAGE HELPERS
// ============================================================

/**
 * Checks if the Users sheet is empty (no users registered).
 * Used by the Login page to show first-run Super Admin setup.
 * @returns {Object} { isEmpty: boolean }
 */
function isUsersSheetEmpty() {
  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  return { isEmpty: lastRow <= 1 };
}

/**
 * Auto-creates the first Super Admin from the current Google account.
 * Only works if Users sheet is empty.
 * @returns {Object} { success, message, error }
 */
function autoCreateFirstAdmin() {
  var email = Session.getActiveUser().getEmail();
  if (!email || email === '') {
    return { success: false, error: 'Tidak dapat mendeteksi akun Google. Pastikan Anda login ke akun Google yang benar.' };
  }

  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow > 1) {
    return { success: false, error: 'Sistem sudah memiliki pengguna. Tidak dapat membuat Super Admin otomatis.' };
  }

  var fullName = email.split('@')[0];
  // Try to use proper name from email (capitalize parts)
  var nameParts = fullName.replace(/[._-]/g, ' ').split(' ');
  fullName = nameParts.map(function(part) {
    return part.charAt(0).toUpperCase() + part.slice(1);
  }).join(' ');

  var now = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    // Double-check after acquiring lock
    sheet = getUsersSheet_();
    lastRow = sheet.getLastRow();
    if (lastRow > 1) {
      return { success: false, error: 'Sistem sudah memiliki pengguna.' };
    }
    sheet.appendRow([email.toLowerCase(), fullName, SUPER_ADMIN_ROLE, 'Active', now, now, now, 'auto-setup']);
    return { success: true, message: 'Super Admin berhasil dibuat: ' + fullName + ' (' + email + ')' };
  } catch (e) {
    return { success: false, error: 'Gagal membuat Super Admin: ' + e.message };
  } finally {
    lock.releaseLock();
  }
}

/**
 * Gets portal settings for the Login page (safe, no auth required).
 * Returns only branding-related fields.
 * @returns {Object} { companyName, companyLogo, companyTagline }
 */
function getPortalSettingsForLogin() {
  try {
    var settings = getPortalSettings();
    return {
      companyName: settings.companyName || 'Mahakarya HRIS',
      companyLogo: settings.companyLogo || '',
      companyTagline: settings.companyTagline || 'Sistem Manajemen Sumber Daya Manusia & Pelacakan Penerimaan Karyawan'
    };
  } catch (e) {
    return { companyName: 'Mahakarya HRIS', companyLogo: '', companyTagline: 'Sistem Manajemen Sumber Daya Manusia & Pelacakan Penerimaan Karyawan' };
  }
}
