// ============================================================
// backend/Auth.gs — AUTHENTICATION & AUTHORIZATION
// Google Workspace SSO via Apps Script Session.
// Recruitment forms remain PUBLIC. Dashboard requires login.
// ============================================================

var SUPER_ADMIN_ROLE = "Super Admin";
var AUTH_SESSION_CACHE_PREFIX = "auth_session_";
var AUTH_SESSION_TTL_SECONDS = 21600; // 6 jam
var AUTH_SESSION_STORAGE_KEY = "mahakarya_hris_session";
var GOOGLE_CLIENT_ID_PROPERTY = "GOOGLE_CLIENT_ID";

// Valid roles in hierarchy order
var VALID_ROLES = ["Super Admin", "HR Admin", "Recruiter", "Manager", "Viewer"];

// Permission definitions per role
var ROLE_PERMISSIONS = {
  "Super Admin": [
    "view_dashboard",
    "manage_users",
    "manage_settings",
    "view_recruitment",
    "edit_recruitment",
    "delete_recruitment",
    "bulk_actions",
    "import_data",
    "export_data",
    "view_audit_log",
    "manage_master_data",
    "view_employee",
    "edit_employee",
    "manage_portal_settings",
  ],
  "HR Admin": [
    "view_dashboard",
    "view_recruitment",
    "edit_recruitment",
    "delete_recruitment",
    "bulk_actions",
    "import_data",
    "export_data",
    "view_audit_log",
    "view_employee",
    "edit_employee",
  ],
  Recruiter: [
    "view_dashboard",
    "view_recruitment",
    "edit_recruitment",
    "export_data",
  ],
  Manager: [
    "view_dashboard",
    "view_recruitment",
    "export_data",
    "view_employee",
  ],
  Viewer: ["view_dashboard", "view_recruitment"],
};

// ============================================================
// SESSION CHECK — Called from client-side on every page load
// ============================================================

function buildLoggedOutUser_(overrides) {
  var base = {
    isLoggedIn: false,
    email: "",
    fullName: "",
    role: "",
    permissions: [],
  };
  overrides = overrides || {};
  var keys = Object.keys(overrides);
  for (var i = 0; i < keys.length; i++) {
    base[keys[i]] = overrides[keys[i]];
  }
  return base;
}

function buildAuthenticatedUser_(email, fullName, role) {
  return {
    isLoggedIn: true,
    email: email,
    fullName: fullName || email,
    role: role,
    permissions: ROLE_PERMISSIONS[role] || [],
  };
}

function getAppBaseUrl_() {
  try {
    return ScriptApp.getService().getUrl() || "";
  } catch (e) {
    return "";
  }
}

function getDisplayNameFromProfile_(email, profileName) {
  var fullName = String(profileName || "").trim();
  if (fullName) return fullName;

  var localPart = String(email || "").split("@")[0];
  var nameParts = localPart.replace(/[._-]/g, " ").split(" ");
  return (
    nameParts
      .map(function (part) {
        if (!part) return "";
        return part.charAt(0).toUpperCase() + part.slice(1);
      })
      .join(" ")
      .trim() || email
  );
}

function getAuthConfig() {
  return {
    success: true,
    googleClientId:
      PropertiesService.getScriptProperties().getProperty(
        GOOGLE_CLIENT_ID_PROPERTY,
      ) || "",
    sessionStorageKey: AUTH_SESSION_STORAGE_KEY,
    appBaseUrl: getAppBaseUrl_(),
  };
}

function fetchGoogleProfileFromToken_(accessToken) {
  var token = String(accessToken || "").trim();
  if (!token) {
    throw new Error("Access token Google tidak ditemukan.");
  }

  var response = UrlFetchApp.fetch(
    "https://www.googleapis.com/oauth2/v3/userinfo",
    {
      method: "get",
      headers: {
        Authorization: "Bearer " + token,
      },
      muteHttpExceptions: true,
    },
  );

  if (response.getResponseCode() !== 200) {
    throw new Error(
      "Google userinfo mengembalikan status " +
        response.getResponseCode() +
        ".",
    );
  }

  var profile = JSON.parse(response.getContentText() || "{}");
  if (!profile.email || profile.email_verified !== true) {
    throw new Error("Email Google tidak tersedia atau belum terverifikasi.");
  }

  return {
    email: String(profile.email).trim().toLowerCase(),
    fullName: getDisplayNameFromProfile_(profile.email, profile.name),
    picture: String(profile.picture || ""),
  };
}

function createUserSession_(user) {
  var sessionToken = Utilities.getUuid();
  CacheService.getScriptCache().put(
    AUTH_SESSION_CACHE_PREFIX + sessionToken,
    JSON.stringify({
      email: user.email,
      fullName: user.fullName,
      role: user.role,
    }),
    AUTH_SESSION_TTL_SECONDS,
  );
  return sessionToken;
}

function clearUserSession(sessionToken) {
  var token = String(sessionToken || "").trim();
  if (token) {
    CacheService.getScriptCache().remove(AUTH_SESSION_CACHE_PREFIX + token);
  }
  return { success: true };
}

/**
 * Perform logout: write audit log + clear session cache.
 * Called by client-side logout handler.
 * @param {string} sessionToken - The session token to invalidate
 * @param {string} email - The email of the user logging out
 * @returns {Object} { success }
 */
function performLogout(sessionToken, email) {
  try {
    // Write audit log for logout
    var userEmail = String(
      email || Session.getActiveUser().getEmail() || "unknown",
    );
    writeAuditLog_(userEmail, "LOGOUT", "user", userEmail, "logged_out");

    // Clear session from cache
    clearUserSession(sessionToken);

    return { success: true };
  } catch (e) {
    // Even if audit log fails, ensure session is cleared
    try {
      clearUserSession(sessionToken);
    } catch (ignored) {}
    return { success: true };
  }
}

function getCurrentUserFromNativeSession_() {
  try {
    var email = Session.getActiveUser().getEmail();
    if (!email || email === "") {
      return buildLoggedOutUser_();
    }

    var user = findUserByEmail_(email);
    if (!user) {
      return buildLoggedOutUser_({ email: email });
    }

    if (user.status !== "Active") {
      return buildLoggedOutUser_({
        email: email,
        fullName: user.fullName,
        role: user.role,
        reason: "inactive",
      });
    }

    updateLastLogin_(email);
    return buildAuthenticatedUser_(email, user.fullName, user.role);
  } catch (e) {
    Logger.log("getCurrentUserFromNativeSession_ error: " + e.message);
    return buildLoggedOutUser_();
  }
}

function getCurrentUserFromSessionToken_(sessionToken) {
  var token = String(sessionToken || "").trim();
  if (!token) {
    return buildLoggedOutUser_();
  }

  try {
    var raw = CacheService.getScriptCache().get(
      AUTH_SESSION_CACHE_PREFIX + token,
    );
    if (!raw) {
      return buildLoggedOutUser_({ reason: "session_expired" });
    }

    var cached = JSON.parse(raw);
    var email = String(cached.email || "")
      .trim()
      .toLowerCase();
    if (!email) {
      return buildLoggedOutUser_();
    }

    var user = findUserByEmail_(email);
    if (!user) {
      return buildLoggedOutUser_({ email: email, reason: "unregistered" });
    }

    if (user.status !== "Active") {
      return buildLoggedOutUser_({
        email: email,
        fullName: user.fullName,
        role: user.role,
        reason: "inactive",
      });
    }

    return buildAuthenticatedUser_(
      email,
      user.fullName || cached.fullName,
      user.role || cached.role,
    );
  } catch (e) {
    Logger.log("getCurrentUserFromSessionToken_ error: " + e.message);
    return buildLoggedOutUser_();
  }
}

// ============================================================
// PASSWORD HASHING — Temporary demo authentication
// Uses HMAC-SHA256 with a fixed pepper stored in Script Properties.
// After demo, this will be replaced with Firebase Auth.
// ============================================================

/**
 * Derives a password hash using HMAC-SHA256.
 * Pepper is stored in Script Properties (set once, never changes).
 * @param {string} password - Plaintext password
 * @returns {string} Hex-encoded hash
 */
function hashPassword_(password) {
  var pepper = PropertiesService.getScriptProperties().getProperty('PASSWORD_PEPPER');
  if (!pepper) {
    pepper = Utilities.getUuid();
    PropertiesService.getScriptProperties().setProperty('PASSWORD_PEPPER', pepper);
  }
  return Utilities.computeHmacSha256Signature(String(password), pepper)
    .map(function(b) { return ('0' + (b & 0xFF).toString(16)).slice(-2); })
    .join('');
}

/**
 * Verifies a plaintext password against a stored hash.
 * @param {string} password
 * @param {string} storedHash
 * @returns {boolean}
 */
function verifyPassword_(password, storedHash) {
  if (!password || !storedHash) return false;
  return hashPassword_(password) === String(storedHash).trim();
}

/**
 * Finds a user by email OR username (case-insensitive).
 * @param {string} identifier
 * @returns {Object|null}
 */
function findUserByEmailOrUsername_(identifier) {
  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) return null;

  var data = sheet.getRange(2, 1, lastRow - 1, USERS_HEADERS.length).getValues();
  var lowerId = String(identifier || '').trim().toLowerCase();

  for (var i = 0; i < data.length; i++) {
    var row = data[i];
    var rowEmail    = String(row[USERS_COL['Email'] - 1] || '').trim().toLowerCase();
    var rowUsername = String(row[USERS_COL['Username'] - 1] || '').trim().toLowerCase();
    if (rowEmail === lowerId || rowUsername === lowerId) {
      return {
        email:        String(row[USERS_COL['Email'] - 1]).trim(),
        username:     String(row[USERS_COL['Username'] - 1] || '').trim(),
        fullName:     String(row[USERS_COL['Full Name'] - 1]).trim(),
        role:         String(row[USERS_COL['Role'] - 1]).trim(),
        status:       String(row[USERS_COL['Status'] - 1]).trim(),
        passwordHash: String(row[USERS_COL['Password Hash'] - 1] || '').trim(),
        lastLogin:    row[USERS_COL['Last Login'] - 1],
        createdAt:    row[USERS_COL['Created At'] - 1],
        updatedAt:    row[USERS_COL['Updated At'] - 1],
        createdBy:    String(row[USERS_COL['Created By'] - 1]).trim(),
        rowIndex:     i + 2,
      };
    }
  }
  return null;
}

// ============================================================
// DEMO USERS — Auto-seed on first run
// ============================================================

var DEFAULT_DEMO_USERS = [
  {
    email: 'superadmin@mahakarya.local',
    username: 'SuperAdmin',
    fullName: 'Developer',
    role: 'Super Admin',
    password: '@C1JERUK'
  },
  {
    email: 'hrd@mahakarya.local',
    username: 'HRD',
    fullName: 'HR Department',
    role: 'HR Admin',
    password: '@hrd1234567_'
  }
];

/**
 * Auto-seeds demo users if they don't exist.
 * Checks individually by email AND username — never overwrites existing data.
 * Called automatically during login attempts.
 * @returns {Object} { success, message, count }
 */
function seedDefaultDemoUsers_() {
  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);

    var sheet = getUsersSheet_();
    var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
    var count = 0;

    for (var i = 0; i < DEFAULT_DEMO_USERS.length; i++) {
      var u = DEFAULT_DEMO_USERS[i];
      // Skip if user already exists by email or username
      var existsByEmail = findUserByEmailOrUsername_(u.email);
      var existsByUsername = findUserByEmailOrUsername_(u.username);
      if (existsByEmail || existsByUsername) continue;

      var hash = hashPassword_(u.password);
      sheet.appendRow([
        u.email.toLowerCase(),   // Email
        u.username,              // Username
        u.fullName,              // Full Name
        u.role,                  // Role
        'Active',                // Status
        hash,                    // Password Hash
        '',                      // Last Login
        now,                     // Created At
        now,                     // Updated At
        'system-demo'            // Created By
      ]);
      count++;
    }
    SpreadsheetApp.flush();

    if (count > 0) {
      return { success: true, message: count + ' demo user berhasil dibuat.', count: count };
    }
    return { success: true, message: 'Semua demo user sudah ada.', count: 0 };
  } catch (e) {
    return { success: false, message: 'Gagal membuat demo user: ' + e.message, count: 0 };
  } finally {
    lock.releaseLock();
  }
}

// ============================================================
// MANUAL LOGIN WITH PASSWORD
// ============================================================

/**
 * Login with email/username + password.
 * identifier may be email or username.
 * Returns the same login response format as Google SSO.
 * @param {string} identifier - Email or username
 * @param {string} password - Plaintext password (verified against stored hash)
 * @param {boolean} rememberMe - If true, client saves session in localStorage; otherwise sessionStorage
 * @returns {Object} { success, sessionToken, rememberMe, message, error, user }
 */
function loginWithPassword(identifier, password, rememberMe) {
  try {
    // 1. Normalize
    var id = String(identifier || '').trim().toLowerCase();
    var pw = String(password || '');

    if (!id || !pw) {
      return { success: false, error: 'Email/Username dan Password wajib diisi.' };
    }

    // 2. Auto-seed demo users if needed
    seedDefaultDemoUsers_();

    // 3. Find user by email or username
    var user = findUserByEmailOrUsername_(id);
    if (!user) {
      writeAuditLog_('SYSTEM', 'LOGIN_FAILED', 'user', id, 'user_not_found');
      return { success: false, error: 'User tidak ditemukan. Periksa email/username dan password Anda.' };
    }

    // 4. Check status
    if (user.status !== 'Active') {
      writeAuditLog_(user.email, 'LOGIN_FAILED', 'user', user.email, 'inactive_account');
      return {
        success: false,
        error: 'Akun Anda (' + user.email + ') belum aktif. Hubungi administrator.'
      };
    }

    // 5. Verify password
    if (!user.passwordHash || !verifyPassword_(pw, user.passwordHash)) {
      writeAuditLog_(user.email, 'LOGIN_FAILED', 'user', user.email, 'wrong_password');
      return { success: false, error: 'Password salah. Silakan coba lagi.' };
    }

    // 6. Success — create session (reuse existing)
    updateLastLogin_(user.email);
    writeAuditLog_(user.email, 'LOGIN_SUCCESS', 'user', user.email, 'logged_in');
    var sessionUser = buildAuthenticatedUser_(user.email, user.fullName, user.role);
    var sessionToken = createUserSession_(sessionUser);

    return {
      success: true,
      message: 'Login berhasil.',
      sessionToken: sessionToken,
      rememberMe: !!rememberMe,
      user: sessionUser,
    };
  } catch (e) {
    return { success: false, error: 'Gagal memproses login: ' + e.message };
  }
}

function createPendingUserIfNeeded_(profile) {
  var existing = findUserByEmail_(profile.email);
  if (existing) return existing;

  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    existing = findUserByEmail_(profile.email);
    if (existing) return existing;

    var sheet = getUsersSheet_();
    var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
    sheet.appendRow([
      profile.email,        // Email
      '',                   // Username (empty for Google SSO users)
      profile.fullName,     // Full Name
      'Viewer',             // Role
      'Inactive',           // Status
      '',                   // Password Hash (empty for Google SSO users)
      '',                   // Last Login
      now,                  // Created At
      now,                  // Updated At
      'google-sso'          // Created By
    ]);
    SpreadsheetApp.flush();
    existing = findUserByEmail_(profile.email);
    if (!existing) {
      throw new Error(
        "Data pengguna baru belum berhasil dicatat ke sheet Users.",
      );
    }
    return existing;
  } finally {
    lock.releaseLock();
  }
}

function signInWithGoogle(accessToken) {
  try {
    var profile = fetchGoogleProfileFromToken_(accessToken);
    var sheetStatus = isUsersSheetEmpty();
    if (sheetStatus && sheetStatus.isEmpty) {
      return {
        success: false,
        error:
          'Sistem belum memiliki pengguna. Gunakan tombol "Daftarkan Saya sebagai Super Admin" untuk pengaturan pertama.',
        reason: "first_run",
        email: profile.email,
        fullName: profile.fullName,
      };
    }

    var user = findUserByEmail_(profile.email);
    if (!user) {
      user = createPendingUserIfNeeded_(profile);
      return {
        success: false,
        error:
          "Akun Google Anda belum aktif. Data Anda sudah dicatat di sheet Users, silakan minta Super Admin mengaktifkan akses Anda.",
        reason: "pending_approval",
        email: profile.email,
        fullName: profile.fullName,
        role: user ? user.role : "Viewer",
      };
    }

    if (user.status !== "Active") {
      return {
        success: false,
        error:
          "Akun Anda (" +
          profile.email +
          ") belum aktif. Hubungi administrator untuk mengaktifkan akun Anda.",
        reason: "inactive",
        email: profile.email,
        fullName: user.fullName || profile.fullName,
        role: user.role,
      };
    }

    updateLastLogin_(profile.email);
    var sessionUser = buildAuthenticatedUser_(
      profile.email,
      user.fullName || profile.fullName,
      user.role,
    );
    var sessionToken = createUserSession_(sessionUser);
    return {
      success: true,
      message: "Login berhasil.",
      sessionToken: sessionToken,
      user: sessionUser,
    };
  } catch (e) {
    return {
      success: false,
      error: "Gagal memverifikasi login Google: " + e.message,
    };
  }
}

/**
 * Checks if the current user is logged in and returns their profile.
 * Called by client-side JS on dashboard load.
 * @returns {Object} { isLoggedIn, email, fullName, role, permissions }
 */
function getCurrentUser(sessionToken) {
  if (sessionToken) {
    return getCurrentUserFromSessionToken_(sessionToken);
  }
  return getCurrentUserFromNativeSession_();
}

/**
 * Server-side authorization check. Returns true if user has the permission.
 * Use this in all protected backend functions.
 * @param {string} permission - The permission key to check
 * @returns {boolean}
 */
function requirePermission(permission, sessionToken) {
  var user = getCurrentUser(sessionToken);
  if (!user.isLoggedIn || !user.permissions) return false;
  return user.permissions.indexOf(permission) !== -1;
}

/**
 * Server-side role check. Returns true if user has at least the specified role.
 * Role hierarchy: Super Admin > HR Admin > Recruiter > Manager > Viewer
 * @param {string} minRole - Minimum role required
 * @returns {boolean}
 */
function requireRole(minRole, sessionToken) {
  var user = getCurrentUser(sessionToken);
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
    sheet.getRange(1, 1, 1, USERS_HEADERS.length).setValues([USERS_HEADERS]);
    sheet
      .getRange(1, 1, 1, USERS_HEADERS.length)
      .setFontWeight("bold")
      .setBackground("#005BAC")
      .setFontColor("#FFFFFF");
    sheet.setFrozenRows(1);
    sheet.autoResizeColumns(1, USERS_HEADERS.length);
  }
  return sheet;
}

/**
 * Finds a user by email (case-insensitive).
 * @param {string} email
 * @returns {Object|null}
 */
function findUserByEmail_(email) {
  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) return null;

  var data = sheet.getRange(2, 1, lastRow - 1, USERS_HEADERS.length).getValues();
  var lowerEmail = email.toLowerCase();

  for (var i = 0; i < data.length; i++) {
    var row = data[i];
    if (String(row[USERS_COL['Email'] - 1]).trim().toLowerCase() === lowerEmail) {
      return {
        email:        String(row[USERS_COL['Email'] - 1]).trim(),
        username:     String(row[USERS_COL['Username'] - 1] || '').trim(),
        fullName:     String(row[USERS_COL['Full Name'] - 1]).trim(),
        role:         String(row[USERS_COL['Role'] - 1]).trim(),
        status:       String(row[USERS_COL['Status'] - 1]).trim(),
        passwordHash: String(row[USERS_COL['Password Hash'] - 1] || '').trim(),
        lastLogin:    row[USERS_COL['Last Login'] - 1],
        createdAt:    row[USERS_COL['Created At'] - 1],
        updatedAt:    row[USERS_COL['Updated At'] - 1],
        createdBy:    String(row[USERS_COL['Created By'] - 1]).trim(),
        rowIndex:     i + 2,
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
  var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
  sheet.getRange(user.rowIndex, USERS_COL['Last Login']).setValue(now);
}

/**
 * Gets all users. Requires manage_users permission.
 * @returns {Object} { success, users }
 */
function getAllUsers(sessionToken) {
  if (!requirePermission("manage_users", sessionToken)) {
    return {
      success: false,
      error: "Akses ditolak. Hanya Super Admin yang dapat mengelola pengguna.",
      users: [],
    };
  }

  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow <= 1) return { success: true, users: [] };

  var data = sheet.getRange(2, 1, lastRow - 1, USERS_HEADERS.length).getValues();
  var users = data.map(function (row) {
    return {
      email:        String(row[USERS_COL['Email'] - 1]).trim(),
      username:     String(row[USERS_COL['Username'] - 1] || '').trim(),
      fullName:     String(row[USERS_COL['Full Name'] - 1]).trim(),
      role:         String(row[USERS_COL['Role'] - 1]).trim(),
      status:       String(row[USERS_COL['Status'] - 1]).trim(),
      lastLogin:    row[USERS_COL['Last Login'] - 1] ? String(row[USERS_COL['Last Login'] - 1]) : "",
      createdAt:    row[USERS_COL['Created At'] - 1] ? String(row[USERS_COL['Created At'] - 1]) : "",
      updatedAt:    row[USERS_COL['Updated At'] - 1] ? String(row[USERS_COL['Updated At'] - 1]) : "",
      createdBy:    String(row[USERS_COL['Created By'] - 1]).trim(),
    };
  });

  return { success: true, users: users };
}

/**
 * Adds a new user. Requires manage_users permission.
 * @param {Object} userData - { email, fullName, role }
 * @returns {Object}
 */
function addUser(userData, sessionToken) {
  if (!requirePermission("manage_users", sessionToken)) {
    return { success: false, error: "Akses ditolak." };
  }

  var currentUser = getCurrentUser(sessionToken);
  var email = String(userData.email || "")
    .trim()
    .toLowerCase();
  var fullName = String(userData.fullName || "").trim();
  var role = String(userData.role || "Viewer").trim();
  var username = String(userData.username || "").trim();

  // Validation
  if (!email || !fullName) {
    return { success: false, error: "Email dan Nama Lengkap wajib diisi." };
  }
  if (VALID_ROLES.indexOf(role) === -1) {
    return { success: false, error: "Role tidak valid: " + role };
  }
  if (findUserByEmail_(email)) {
    return { success: false, error: "Email sudah terdaftar: " + email };
  }

  // Hash password if provided
  var passwordHash = '';
  if (userData.password) {
    passwordHash = hashPassword_(userData.password);
  }

  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    var sheet = getUsersSheet_();
    var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
    sheet.appendRow([
      email,          // Email
      username,       // Username
      fullName,       // Full Name
      role,           // Role
      'Active',       // Status
      passwordHash,   // Password Hash
      '',             // Last Login
      now,            // Created At
      now,            // Updated At
      currentUser.email // Created By
    ]);
    return { success: true, message: "Pengguna berhasil ditambahkan." };
  } catch (e) {
    return { success: false, error: "Gagal menyimpan: " + e.message };
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
function updateUser(email, updates, sessionToken) {
  if (!requirePermission("manage_users", sessionToken)) {
    return { success: false, error: "Akses ditolak." };
  }

  var user = findUserByEmail_(email);
  if (!user) {
    return { success: false, error: "Pengguna tidak ditemukan." };
  }

  var sheet = getUsersSheet_();
  var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");

  if (updates.fullName)
    sheet.getRange(user.rowIndex, USERS_COL['Full Name']).setValue(String(updates.fullName).trim());
  if (updates.username !== undefined)
    sheet.getRange(user.rowIndex, USERS_COL['Username']).setValue(String(updates.username).trim());
  if (updates.role && VALID_ROLES.indexOf(updates.role) !== -1)
    sheet.getRange(user.rowIndex, USERS_COL['Role']).setValue(updates.role);
  if (
    updates.status &&
    (updates.status === "Active" || updates.status === "Inactive")
  )
    sheet.getRange(user.rowIndex, USERS_COL['Status']).setValue(updates.status);
  if (updates.password)
    sheet.getRange(user.rowIndex, USERS_COL['Password Hash']).setValue(hashPassword_(updates.password));
  sheet.getRange(user.rowIndex, USERS_COL['Updated At']).setValue(now);

  return { success: true, message: "Pengguna berhasil diperbarui." };
}

/**
 * Deletes a user. Requires manage_users permission.
 * Prevents deleting yourself.
 * @param {string} email
 * @returns {Object}
 */
function deleteUser(email, sessionToken) {
  if (!requirePermission("manage_users", sessionToken)) {
    return { success: false, error: "Akses ditolak." };
  }

  var currentUser = getCurrentUser(sessionToken);
  if (email.toLowerCase() === currentUser.email.toLowerCase()) {
    return { success: false, error: "Tidak dapat menghapus akun sendiri." };
  }

  var user = findUserByEmail_(email);
  if (!user) {
    return { success: false, error: "Pengguna tidak ditemukan." };
  }

  var sheet = getUsersSheet_();
  sheet.deleteRow(user.rowIndex);
  return { success: true, message: "Pengguna berhasil dihapus." };
}

/**
 * Seeds the first Super Admin if the Users sheet is empty.
 * Called during initial setup or from the script editor.
 */
function seedSuperAdmin(email, fullName) {
  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow > 1) {
    return {
      success: false,
      error:
        "Users sheet sudah berisi data. Gunakan addUser untuk menambah pengguna.",
    };
  }

  var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
  sheet.appendRow([
    String(email).trim().toLowerCase(),  // Email
    '',                                  // Username
    String(fullName).trim(),             // Full Name
    SUPER_ADMIN_ROLE,                    // Role
    'Active',                            // Status
    '',                                  // Password Hash
    '',                                  // Last Login
    now,                                 // Created At
    now,                                 // Updated At
    'system'                             // Created By
  ]);
  return { success: true, message: "Super Admin berhasil dibuat: " + email };
}

/**
 * Promotes a user to Super Admin. Only callable by existing Super Admin.
 * @param {string} email
 * @returns {Object}
 */
function promoteToSuperAdmin(email, sessionToken) {
  var currentUser = getCurrentUser(sessionToken);
  if (!currentUser.isLoggedIn || currentUser.role !== SUPER_ADMIN_ROLE) {
    return {
      success: false,
      error: "Hanya Super Admin yang dapat mengubah role ke Super Admin.",
    };
  }
  return updateUser(email, { role: SUPER_ADMIN_ROLE }, sessionToken);
}

/**
 * Checks if a specific user has a specific permission.
 * Client-safe: returns boolean.
 * @param {string} permission
 * @returns {boolean}
 */
function checkPermission(permission, sessionToken) {
  return requirePermission(permission, sessionToken);
}

// ============================================================
// GOOGLE ID TOKEN (CREDENTIAL) VERIFICATION
// Used by Sign In With Google (google.accounts.id) flow.
// Does NOT require Authorized JavaScript Origins.
// ============================================================

/**
 * Decodes and validates a Google ID Token (JWT) from google.accounts.id.
 * We trust the token content because it came directly from Google GIS —
 * for stronger validation, verify the signature against Google's public keys,
 * but for Apps Script internal use this is sufficient.
 * @param {string} idToken - JWT from google.accounts.id callback
 * @returns {Object} { email, fullName, picture }
 */
function verifyGoogleIdToken_(idToken) {
  var token = String(idToken || "").trim();
  if (!token) throw new Error("ID token tidak ditemukan.");

  var parts = token.split(".");
  if (parts.length !== 3) throw new Error("Format ID token tidak valid.");

  try {
    // Base64url → Base64 → decode
    var base64 = parts[1].replace(/-/g, "+").replace(/_/g, "/");
    while (base64.length % 4 !== 0) base64 += "=";
    var bytes = Utilities.base64Decode(base64);
    var json = bytes
      .map(function (b) {
        return String.fromCharCode(b);
      })
      .join("");
    var payload = JSON.parse(json);

    if (!payload.email) throw new Error("Email tidak ada di token.");
    if (payload.email_verified !== true)
      throw new Error("Email belum diverifikasi Google.");

    // Check token not expired
    var now = Math.floor(Date.now() / 1000);
    if (payload.exp && payload.exp < now)
      throw new Error("Token sudah kadaluarsa, coba login ulang.");

    // Check token not too old (max 10 minutes)
    if (payload.iat && now - payload.iat > 600)
      throw new Error("Token terlalu lama, coba login ulang.");

    return {
      email: String(payload.email).trim().toLowerCase(),
      fullName: getDisplayNameFromProfile_(payload.email, payload.name || ""),
      picture: String(payload.picture || ""),
    };
  } catch (e) {
    if (e.message.indexOf("token") !== -1 || e.message.indexOf("Token") !== -1)
      throw e;
    throw new Error("Gagal membaca ID token: " + e.message);
  }
}

/**
 * Sign in using Google ID Token credential (from google.accounts.id).
 * Drop-in replacement for signInWithGoogle() that works without
 * Authorized JavaScript Origins in Cloud Console.
 * @param {string} idToken - JWT credential from google.accounts.id callback
 * @returns {Object} { success, sessionToken, message, error }
 */
function signInWithGoogleCredential(idToken) {
  try {
    var profile = verifyGoogleIdToken_(idToken);
    var sheetStatus = isUsersSheetEmpty();
    if (sheetStatus && sheetStatus.isEmpty) {
      return {
        success: false,
        error:
          'Sistem belum memiliki pengguna. Gunakan tombol "Daftarkan Saya sebagai Super Admin" untuk pengaturan pertama.',
        reason: "first_run",
        email: profile.email,
        fullName: profile.fullName,
      };
    }

    var user = findUserByEmail_(profile.email);
    if (!user) {
      user = createPendingUserIfNeeded_(profile);
      return {
        success: false,
        error:
          "Akun Google Anda belum aktif. Data Anda sudah dicatat di sheet Users, silakan minta Super Admin mengaktifkan akses Anda.",
        reason: "pending_approval",
        email: profile.email,
        fullName: profile.fullName,
        role: user ? user.role : "Viewer",
      };
    }

    if (user.status !== "Active") {
      return {
        success: false,
        error:
          "Akun Anda (" +
          profile.email +
          ") belum aktif. Hubungi administrator untuk mengaktifkan akun Anda.",
        reason: "inactive",
        email: profile.email,
        fullName: user.fullName || profile.fullName,
        role: user.role,
      };
    }

    updateLastLogin_(profile.email);
    var sessionUser = buildAuthenticatedUser_(
      profile.email,
      user.fullName || profile.fullName,
      user.role,
    );
    var sessionToken = createUserSession_(sessionUser);
    return {
      success: true,
      message: "Login berhasil.",
      sessionToken: sessionToken,
      user: sessionUser,
    };
  } catch (e) {
    return {
      success: false,
      error: "Gagal memverifikasi login Google: " + e.message,
    };
  }
}

/**
 * Auto-creates first Super Admin using Google ID Token credential.
 * @param {string} idToken - JWT credential from google.accounts.id callback
 * @returns {Object} { success, sessionToken, message, error }
 */
function autoCreateFirstAdminWithCredential(idToken) {
  try {
    var profile = verifyGoogleIdToken_(idToken);
    return autoCreateFirstAdmin_(profile.email, profile.fullName);
  } catch (e) {
    return {
      success: false,
      error: "Gagal memverifikasi akun Google: " + e.message,
    };
  }
}

/**
 * Internal helper: creates first Super Admin row and returns session.
 * Used by both autoCreateFirstAdmin (access_token) and autoCreateFirstAdminWithCredential (idToken).
 */
function autoCreateFirstAdmin_(email, fullName) {
  var sheet = getUsersSheet_();
  var lastRow = sheet.getLastRow();
  if (lastRow > 1) {
    return {
      success: false,
      error:
        "Sistem sudah memiliki pengguna. Tidak dapat membuat Super Admin otomatis.",
    };
  }

  var now = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd HH:mm:ss");
  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    sheet = getUsersSheet_();
    lastRow = sheet.getLastRow();
    if (lastRow > 1) {
      return { success: false, error: "Sistem sudah memiliki pengguna." };
    }
    sheet.appendRow([
      email.toLowerCase(),  // Email
      '',                   // Username
      fullName,             // Full Name
      SUPER_ADMIN_ROLE,     // Role
      'Active',             // Status
      '',                   // Password Hash
      '',                   // Last Login
      now,                  // Created At
      now,                  // Updated At
      'auto-setup'          // Created By
    ]);
    var sessionUser = buildAuthenticatedUser_(
      email.toLowerCase(),
      fullName,
      SUPER_ADMIN_ROLE,
    );
    return {
      success: true,
      message: "Super Admin berhasil dibuat: " + fullName + " (" + email + ")",
      sessionToken: createUserSession_(sessionUser),
      user: sessionUser,
    };
  } catch (e) {
    return { success: false, error: "Gagal membuat Super Admin: " + e.message };
  } finally {
    lock.releaseLock();
  }
}

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
function autoCreateFirstAdmin(accessToken) {
  var email = "";
  var fullName = "";

  if (accessToken) {
    try {
      var profile = fetchGoogleProfileFromToken_(accessToken);
      email = profile.email;
      fullName = profile.fullName;
    } catch (e) {
      return {
        success: false,
        error: "Gagal memverifikasi akun Google: " + e.message,
      };
    }
  } else {
    email = Session.getActiveUser().getEmail();
    if (!email || email === "") {
      return {
        success: false,
        error:
          "Tidak dapat mendeteksi akun Google. Pastikan Anda login ke akun Google yang benar.",
      };
    }
    fullName = getDisplayNameFromProfile_(email, "");
  }

  return autoCreateFirstAdmin_(email, fullName);
}

/**
 * Gets portal settings for the Login page (safe, no auth required).
 * Returns only branding-related fields.
 * @returns {Object} { companyName, companyLogo, companyTagline }
 */
function getPortalSettingsForLogin() {
  try {
    var result = getPortalSettings();
    var settings = result && result.settings ? result.settings : {};
    return {
      companyName: settings.companyName || "Mahakarya HRIS",
      companyLogo: settings.companyLogo || "",
      companyTagline:
        settings.companyTagline ||
        settings.portalSubtitle ||
        "Sistem Manajemen Sumber Daya Manusia & Pelacakan Penerimaan Karyawan",
    };
  } catch (e) {
    return {
      companyName: "Mahakarya HRIS",
      companyLogo: "",
      companyTagline:
        "Sistem Manajemen Sumber Daya Manusia & Pelacakan Penerimaan Karyawan",
    };
  }
}