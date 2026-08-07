# AGENTS.md — AI Agent Instructions for HRIS Project

## Project Overview

**Project Name**: Mahakarya HRIS — Applicant Tracking System (ATS)  
**Type**: Google Apps Script + HTML/Bootstrap 5  
**Database**: Google Spreadsheet  
**Purpose**: Recruitment management and HR dashboard for tracking candidates from application to employee onboarding

---

## Development Rules

### 1. Google Apps Script Rules

- **Single File Backend**: All Apps Script code MUST be in ONE `.gs` file (currently `Kode.gs`)
- **No Multiple doGet()**: Only ONE `doGet(e)` function allowed in the entire project
- **No Multiple doPost()**: Only ONE `doPost(e)` function allowed if needed
- **Function Naming**: Use camelCase for public functions, snake_case for private functions (trailing underscore)
- **Lock Service**: Always use `LockService.getScriptLock()` for concurrent operations (candidate updates, ID generation)
- **Properties Service**: Use `PropertiesService.getScriptProperties()` for counters and settings
- **Spreadsheet Operations**: Use `getOrCreateSheet_()` pattern for safe sheet creation
- **Error Handling**: Wrap all public functions in try-catch, return error messages as strings or objects
- **Audit Logging**: All status changes MUST call `writeAuditLog_()` with action, old value, new value

### 2. Bootstrap 5 Rules

- **Version**: Bootstrap 5.3.0 (CDN: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css`)
- **Icons**: Bootstrap Icons 1.11.3 (CDN: `https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css`)
- **No Custom CSS Frameworks**: Do not add Tailwind, Bulma, or other CSS frameworks
- **Component Classes**: Use standard Bootstrap classes (btn, card, modal, dropdown, etc.)
- **Grid System**: Use Bootstrap grid (container, row, col-*) for layouts
- **Responsive**: All pages MUST be mobile-responsive using Bootstrap breakpoints

### 3. Spreadsheet Rules

- **Sheet Names**: Use constants for sheet names (SHEET_NAME, DASHBOARD_SHEET_NAME, etc.)
- **Column Headers**: NEVER rename existing headers without explicit instruction
- **Column Order**: Core columns have fixed order (SHEET_HEADERS array), extra columns append at end
- **Data Types**: 
  - NIK and Phone: Store as text with leading apostrophe (`'` + value)
  - Dates: Store as strings in "yyyy-MM-dd HH:mm:ss" format (GMT+7)
  - Numbers: Store as numbers, not strings
- **Header Styling**: Bold, #005BAC background, white text, frozen first row
- **Auto-Resize**: Call `autoResizeColumns()` after creating sheets
- **Safe Operations**: Use `ensureExtraHeaders_()` to add columns without breaking existing data

### 4. File Modification Rules

- **Preserve Structure**: Never rewrite the project from scratch
- **Extend, Don't Replace**: Always add to existing implementation
- **Minimal Changes**: Only modify files necessary for the requested feature
- **No Renaming**: Never rename files, functions, or spreadsheet headers without instruction
- **Backward Compatibility**: All changes must maintain compatibility with existing data
- **Testing**: Verify changes don't break existing functionality

### 5. Response Format

Every response MUST include:
1. **Brief explanation** of what was changed
2. **List of modified files**
3. **Dependencies** (if any were added/modified)
4. **Breaking Changes**: Explicitly state "Breaking Changes: None" if applicable

### 6. Version Control Policy

- **NO Git Operations**: Do NOT initialize repositories, create commits, push, or pull
- **Local Development Only**: Treat workspace as local folder only
- **No GitHub**: Do not suggest Git operations unless explicitly requested

### 7. Coding Standards

- **Language**: JavaScript (Apps Script), HTML, CSS
- **Indentation**: 2 spaces (no tabs)
- **Quotes**: Single quotes for JavaScript, double quotes for HTML attributes
- **Naming**:
  - Public functions: camelCase (`simpanDataKandidat`)
  - Private functions: snake_case with trailing underscore (`getOrCreateSheet_`)
  - Constants: UPPER_SNAKE_CASE (`SHEET_NAME`)
  - Variables: camelCase
- **Comments**: Use `// ==============` section dividers, document complex logic
- **Error Messages**: Return user-friendly Indonesian messages
- **Date Format**: Always use "GMT+7" timezone, format "yyyy-MM-dd HH:mm:ss"

### 8. Architecture Preservation Rules

- **Single Source of Truth**: Existing codebase is the reference
- **No Rewrites**: Never rewrite existing modules
- **Function Signatures**: Maintain existing function signatures for public APIs
- **Data Flow**: Preserve existing data flow patterns
- **UI/UX**: Maintain existing design system (colors, spacing, components)

### 9. Dependency Analysis Requirements

Before any modification:
1. Read ALL files in workspace
2. Identify all function calls and dependencies
3. Map data flow between backend and frontend
4. Check for side effects on other modules
5. Verify spreadsheet column dependencies

### 10. Output Requirements

- **Production-Ready Code**: All code must be complete, no placeholders
- **No Pseudo Code**: Write actual working code
- **Complete Files**: Provide full file content when using `write_to_file`
- **Tested Logic**: Ensure all code paths are handled
- **Indonesian Language**: All user-facing text must be in Indonesian

---

## Quick Reference

### Current File Structure
```
e:/Project/HRIS/
├── Kode.gs              # Backend (655 lines) - ALL Apps Script code
├── Dashboard.html       # HR Dashboard (1890 lines) - Main interface
└── FormPendaftaran.html # Registration Portal (978 lines) - Candidate form
```

### Key Backend Functions
- `doGet(e)` - Router (dashboard vs registration)
- `simpanDataKandidat(formObject)` - Save candidate
- `getRecruitmentList()` - Fetch all candidates
- `updateCandidateStatus(id, status, notes)` - Generic status update
- `holdCandidate(id, reason, followUpDate, notes)` - Hold with reason
- `blacklistCandidate(id, reason, notes)` - Blacklist with reason
- `acceptCandidateToEmployee(id, notes)` - Accept and create employee
- `saveHrNotes(id, notes)` - Auto-save HR notes
- `getAuditLogForCandidate(id)` - Get activity timeline

### Key Frontend Pages
- **Dashboard**: `?page=dashboard` - Full HR dashboard with charts, filters, drawer
- **Registration**: `?type=kandidat` (default) - Candidate registration form

### Spreadsheet Sheets
1. `data_kandidat` - Candidate data (25 core + 6 extra columns)
2. `Employee` - Master employee data (10 columns)
3. `Audit_Log` - Activity tracking (6 columns)

### Color Palette
- Primary: `#005BAC`
- Primary Dark: `#00437e`
- Navy: `#0B2540`
- Accent: `#FDB913`
- Success: `#166534`
- Danger: `#991b1b`
- Warning: `#8a6100`

---

## Critical Warnings

⚠️ **NEVER** create a second `.gs` file — Apps Script will conflict  
⚠️ **NEVER** rename `Recruitment ID` column — breaks all lookups  
⚠️ **NEVER** change column order in SHEET_HEADERS — breaks existing data  
⚠️ **NEVER** remove `LockService` from concurrent operations  
⚠️ **NEVER** store NIK/Phone as numbers — leading zeros will be lost  
⚠️ **ALWAYS** test with existing spreadsheet data before deploying  
⚠️ **ALWAYS** preserve Indonesian language in user-facing text  

---

## Last Updated
2026-07-30 - Initial documentation based on codebase analysis