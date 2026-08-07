# CONTRIBUTING.md — Developer Guide

**Project**: MITO HRIS  
**Version**: v0.6.0  
**Last Updated**: 2026-08-03

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Project Structure](#2-project-structure)
3. [Development Workflow](#3-development-workflow)
4. [Coding Standards](#4-coding-standards)
5. [Adding a New Backend Module](#5-adding-a-new-backend-module)
6. [Adding a New Page](#6-adding-a-new-page)
7. [Testing Checklist](#7-testing-checklist)
8. [Deployment](#8-deployment)

---

## 1. Prerequisites

- Google account with access to Google Apps Script editor
- Google Spreadsheet (created by the app automatically)
- Modern browser (Chrome, Firefox, Edge)
- VS Code or any text editor for local development
- Git (optional, for version control)

### Google Apps Script Setup

1. Open the target Google Spreadsheet
2. Go to **Extensions → Apps Script**
3. Delete any default `Code.gs` content
4. Copy `Kode.gs` as the main entry point
5. Create folder structure matching the project (`backend/`, `views/`, `partials/`, `css/`, `js/`)
6. Copy each file into its respective location

---

## 2. Project Structure

```
Kode.gs                      # Entry point (DO NOT add business logic here)
backend/                     # Server-side modules
  ├── Config.gs              # Constants & configuration
  ├── Auth.gs                # Authentication & RBAC
  ├── Recruitment.gs         # Candidate CRUD
  ├── Employee.gs            # Employee CRUD
  ├── MasterData.gs          # Master data CRUD
  ├── PortalSettings.gs      # Portal settings CRUD
  ├── Import.gs              # CSV/Excel import
  └── Outsource.gs           # Outsource registration
views/                       # Main HTML page templates
partials/                    # Reusable HTML components
css/                         # Stylesheets (as HTML includes)
js/                          # Client-side JavaScript (as HTML includes)
data/                        # Static data files
```

### File Naming Conventions

| Location | Convention | Example |
|----------|-----------|---------|
| Backend .gs | PascalCase | `Recruitment.gs` |
| Views HTML | PascalCase | `Dashboard.html` |
| Partials HTML | PascalCase | `Sidebar.html` |
| CSS includes | camelCase | `theme.html`, `table.html` |
| JS includes | camelCase | `app.html`, `employee.html` |

---

## 3. Development Workflow

### Step 1: Identify What to Change

- **Backend logic**: Edit `backend/*.gs` files
- **UI/page structure**: Edit `views/*.html`
- **Shared components**: Edit `partials/*.html`
- **Styling**: Edit `css/*.html` files
- **Client-side behavior**: Edit `js/*.html` files

### Step 2: Follow Naming Conventions

**Apps Script (backend/):**
```javascript
// Public functions: camelCase
function getRecruitmentList() { }

// Private functions: snake_case with trailing underscore
function findCandidateRow_(sheet, id) { }

// Constants: UPPER_SNAKE_CASE
var SHEET_NAME = 'data_kandidat';

// Section dividers
// ============================================================
// SECTION NAME
// ============================================================
```

**HTML/CSS:**
```html
<!-- IDs: camelCase -->
<button id="btnSave">Save</button>

<!-- Classes: kebab-case -->
<div class="stat-card">...</div>

<!-- Data attributes: camelCase -->
<tr data-candidate-id="REC-20260803-0001">
```

**JavaScript:**
```javascript
// Variables: camelCase
let filteredCandidates = [];

// Functions: camelCase
function renderTable() { }

// Constants: UPPER_SNAKE_CASE
const DEBOUNCE_DELAY = 1200;
```

### Step 3: Copy Files to Apps Script

Since Google Apps Script doesn't read from local folders, manually copy modified files to the Apps Script editor. Maintain the folder structure using Apps Script's folder feature.

### Step 4: Test

Follow the [Testing Checklist](#7-testing-checklist) below.

---

## 4. Coding Standards

### Backend (Apps Script)

1. **Error handling**: Always wrap public functions in try-catch
   ```javascript
   function myPublicFunction(param) {
     try {
       // ... business logic
       return { success: true, data: result };
     } catch (e) {
       return { success: false, error: e.message };
     }
   }
   ```

2. **Locking**: Use `LockService.getScriptLock()` for write operations
   ```javascript
   var lock = LockService.getScriptLock();
   try {
     lock.waitLock(10000);
     // ... write operation
   } finally {
     lock.releaseLock();
   }
   ```

3. **Timestamps**: Always use GMT+7
   ```javascript
   var now = Utilities.formatDate(new Date(), 'GMT+7', 'yyyy-MM-dd HH:mm:ss');
   ```

4. **NIK/Phone storage**: Always prefix with apostrophe for text storage
   ```javascript
   var nikValue = "'" + formObject.nik;  // Stored as text
   ```

5. **Audit logging**: Log all status changes
   ```javascript
   writeAuditLog_(recruitmentId, 'Status Update', oldValue, newValue);
   ```

### Frontend (HTML/JS)

1. **Bootstrap 5.3.0**: Use standard Bootstrap classes only
2. **Bootstrap Icons 1.11.3**: Use `<i class="bi bi-*"></i>` for icons
3. **Google Fonts**: Inter font family
4. **Dark mode**: Use CSS custom properties from `css/theme.html`
5. **Responsive**: All layouts must work on mobile, tablet, and desktop
6. **Debounce**: Use debounce for auto-save and search inputs (1200ms default)
7. **Toast notifications**: Use `showToast(message, type)` for user feedback

---

## 5. Adding a New Backend Module

### Step 1: Create the .gs file

```javascript
// backend/NewModule.gs
// ============================================================
// backend/NewModule.gs — BRIEF DESCRIPTION
// ============================================================

// Constants (if needed)
var NEW_MODULE_CONSTANT = 'value';

/**
 * Brief description of the function.
 * @param {type} param - Description
 * @returns {Object} Response object
 */
function publicFunctionName(param) {
  // Permission check (if protected)
  if (!requirePermission('required_permission')) {
    return { success: false, error: 'Akses ditolak.' };
  }

  try {
    // Business logic
    return { success: true, data: result };
  } catch (e) {
    return { success: false, error: e.message };
  }
}

// ============================================================
// PRIVATE FUNCTIONS
// ============================================================

function privateHelper_(param) {
  // Internal helper
}
```

### Step 2: Add routing (if it's a new page)

In `Kode.gs`, add a new route:
```javascript
if (page === 'new-page') {
  return HtmlService.createTemplateFromFile('views/NewPage').evaluate()
      .setTitle('New Page — MITO HRIS')
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}
```

### Step 3: Add permissions (if needed)

In `backend/Auth.gs`, add the new permission to relevant roles in `ROLE_PERMISSIONS`.

---

## 6. Adding a New Page

### Step 1: Create the view template

```html
<!-- views/NewPage.html -->
<!DOCTYPE html>
<html>
<head>
  <base target="_top">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?!= include('css/theme'); ?>
</head>
<body>
  <?!= include('partials/Sidebar'); ?>
  <?!= include('partials/Topbar'); ?>

  <main id="mainContent" class="main-content">
    <!-- Page content here -->
  </main>

  <?!= include('js/app'); ?>
  <?!= include('js/newPage'); ?>
</body>
</html>
```

### Step 2: Create the JS module

```html
<!-- js/newPage.html -->
<script>
(function() {
  'use strict';

  // State
  let pageData = [];

  // Initialize
  document.addEventListener('DOMContentLoaded', function() {
    loadData();
  });

  async function loadData() {
    try {
      const result = await serverCall('backendFunctionName');
      if (result.success) {
        pageData = result.data;
        render();
      }
    } catch (err) {
      showToast('Gagal memuat data: ' + err.message, 'danger');
    }
  }

  function render() {
    // Render UI
  }
})();
</script>
```

### Step 3: Add sidebar link

In `partials/Sidebar.html`, add a new nav item:
```html
<a href="?page=new-page" class="nav-link">
  <i class="bi bi-icon-name"></i>
  <span>New Page</span>
</a>
```

---

## 7. Testing Checklist

Before deploying any changes:

### Authentication
- [ ] Login page loads correctly
- [ ] First-run Super Admin creation works
- [ ] Role-based access control enforced
- [ ] Inactive users blocked
- [ ] Logout works

### Candidate Module
- [ ] Registration form submits successfully
- [ ] Recruitment ID generated correctly (REC-YYYYMMDD-NNNNNN)
- [ ] Dashboard loads all candidates
- [ ] Status changes work (Accept/Hold/Blacklist/Pending)
- [ ] HR Notes auto-save works (1200ms debounce)
- [ ] Audit log entries created for all changes
- [ ] Export (CSV/Excel/PDF) works
- [ ] Filters and search work correctly

### Employee Module
- [ ] Employee list loads
- [ ] Add new employee works
- [ ] Employee ID generated correctly (EMP-YYYY-NNNN)
- [ ] Edit employee works
- [ ] Delete employee works (with confirmation)
- [ ] Search and filter work

### Master Data
- [ ] Categories load correctly
- [ ] Add new item works
- [ ] Edit item works
- [ ] Delete item works (with confirmation)
- [ ] Duplicate detection works

### Portal Settings
- [ ] Settings load correctly
- [ ] Save settings works
- [ ] Changes reflect in UI (logo, company name, etc.)

### UI/UX
- [ ] Dark mode toggle works
- [ ] Sidebar responsive on mobile
- [ ] All modals open and close correctly
- [ ] Toast notifications appear and dismiss
- [ ] Loading spinners show during async operations
- [ ] Print layout works

---

## 8. Deployment

### First Deployment

1. Open Google Apps Script editor from the Spreadsheet
2. Copy all files maintaining folder structure
3. Run `seedSuperAdmin('email@domain.com', 'Admin Name')` once
4. Deploy as Web App:
   - Execute as: **Me** (owner)
   - Who has access: **Anyone** (public forms) or **Anyone within organization** (internal only)
5. Copy the deployment URL

### Updating Deployment

1. Copy changed files to Apps Script editor
2. Create new version: **Deploy → Manage deployments → Edit → Version: New**
3. Deploy

### Post-Deployment Verification

1. Test login with a registered user
2. Test candidate registration (public form)
3. Test dashboard data loading
4. Test at least one status change
5. Check audit log for entries

---

*Last Updated: 2026-08-03 — v0.6.0*
