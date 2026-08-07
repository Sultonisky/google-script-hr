# PROJECT.md — Technical Documentation

## Project Information

**Project Name**: MITO HRIS — Applicant Tracking System (ATS)  
**Version**: 1.0.0  
**Company**: MITO Group  
**Created**: 2026-07-30  
**Purpose**: Recruitment management and HR dashboard for tracking candidates from application to employee onboarding

---

## Technology Stack

### Backend
- **Platform**: Google Apps Script
- **Runtime**: Apps Script JavaScript (V8 runtime)
- **File**: Single file `Kode.gs` (655 lines)

### Frontend
- **HTML**: Vanilla HTML5
- **CSS Framework**: Bootstrap 5.3.0
- **Icons**: Bootstrap Icons 1.11.3
- **Font**: Inter (Google Fonts)
- **JavaScript**: Vanilla JavaScript (ES6+)

### Database
- **Type**: Google Spreadsheet
- **Sheets**: 3 sheets (data_kandidat, Employee, Audit_Log)

### External Libraries (CDN)
- Chart.js 4.4.0 - Data visualization
- SheetJS (xlsx) 0.18.5 - Excel export

---

## Architecture

### System Architecture
```
┌─────────────────┐
│   Browser       │
│   (HTML/JS)     │
└────────┬────────┘
         │
         │ google.script.run
         │ HtmlService
         ▼
┌─────────────────┐
│ Apps Script     │
│   Kode.gs       │
└────────┬────────┘
         │
         │ SpreadsheetApp
         ▼
┌─────────────────┐
│   Google        │
│   Spreadsheet   │
└─────────────────┘
```

### Request Flow
1. User accesses web app URL
2. `doGet(e)` routes to appropriate HTML page
3. Frontend calls backend functions via `google.script.run`
4. Backend performs CRUD operations on Spreadsheet
5. Data returned to frontend for rendering
6. All changes logged to Audit_Log sheet

---

## Folder Structure

```
e:/Project/HRIS/
├── Kode.gs              # Backend (655 lines) - ALL Apps Script code
├── Dashboard.html       # HR Dashboard (1890 lines) - Main interface
├── FormPendaftaran.html # Registration Portal (978 lines) - Candidate form
├── AGENTS.md            # AI Agent instructions
├── PROJECT.md           # This file - Technical documentation
├── ROADMAP.md           # Development roadmap
├── CHANGELOG.md         # Version history
└── TODO.md              # Task tracking
```

**Note**: Only one `.gs` file allowed in Apps Script projects to prevent function conflicts.

---

## Current Modules

### 1. Router Module
**Function**: `doGet(e)`  
**Purpose**: Routes requests to appropriate pages  
**Routes**:
- `?page=dashboard` → Dashboard.html
- `?type=kandidat` (default) → FormPendaftaran.html

### 2. Recruitment Portal Module
**Function**: `simpanDataKandidat(formObject)`  
**Purpose**: Save new candidate registration  
**Features**:
- Server-side validation (16 fields)
- Auto-generate Recruitment ID (REC-YYYYMMDD-000001)
- Timestamp creation
- Default status: "Pending"

### 3. Dashboard Module
**Function**: `getRecruitmentList()`  
**Purpose**: Fetch all candidates for dashboard display  
**Returns**: Array of candidate objects with all fields

### 4. Status Management Module
**Functions**:
- `updateCandidateStatus(id, status, notes)` - Generic status update
- `holdCandidate(id, reason, followUpDate, notes)` - Hold with reason
- `blacklistCandidate(id, reason, notes)` - Blacklist with reason
- `acceptCandidateToEmployee(id, notes)` - Accept and create employee

### 5. HR Notes Module
**Function**: `saveHrNotes(id, notes)`  
**Purpose**: Auto-save HR notes without changing status

### 6. Audit Log Module
**Function**: `writeAuditLog_(recruitmentId, action, oldValue, newValue)`  
**Purpose**: Log all status changes and actions  
**Related**: `getAuditLogForCandidate(id)` - Fetch activity timeline

### 7. ID Generator Module
**Functions**:
- `generateRecruitmentId_(timestamp)` - Daily reset counter
- `generateEmployeeId_(timestamp)` - Yearly reset counter

---

## Existing Features

### Dashboard Features
1. **Statistics Cards** (10 cards)
   - Total candidates
   - Pending count
   - Accepted count
   - Hold count
   - Blacklist count
   - Male/Female distribution
   - Fresh Graduate vs Experienced
   - Average salary expectation

2. **Charts** (7 visualizations)
   - Monthly recruitment trends (line chart)
   - Candidates per position (bar chart)
   - Candidates per city (bar chart)
   - Education distribution (bar chart)
   - Gender distribution (pie chart)
   - Recruitment source (pie chart)
   - Status distribution (doughnut chart)

3. **Data Table**
   - Paginated display (10/25/50/100 per page)
   - Search by ID, name, phone, email, position, city
   - Filter by status, position, education, gender, source, experience, city
   - Advanced filters: salary range, age range, date range
   - Sort by: newest, oldest, name, age, salary
   - Column header click sorting

4. **Candidate Profile Drawer**
   - Personal information display
   - Job information display
   - HR notes with auto-save (1200ms debounce)
   - Activity timeline
   - Document status display
   - Status action buttons (Accept, Hold, Blacklist, Pending)

5. **Export Functionality**
   - CSV export
   - Excel (XLSX) export
   - PDF export
   - Exports filtered data only

6. **Settings**
   - Dark/Light theme toggle
   - Page size configuration
   - Default status filter
   - Auto-refresh toggle
   - Refresh interval (15-300 seconds)

7. **Other Features**
   - Dark mode support
   - Print functionality
   - FAB speed dial
   - Responsive design (mobile/tablet/desktop)
   - Animated counters
   - Toast notifications

### Registration Portal Features
1. **Statistics Cards** (4 cards)
   - Total candidates
   - Pending review
   - Accepted
   - Hold/Blacklist

2. **Data Table**
   - Search by name, position, email, ID
   - Filter by status
   - Refresh data

3. **Responsive Design**
   - Mobile-friendly sidebar
   - Adaptive layouts

---

## Apps Script Backend

### Public Functions (Callable from Frontend)

| Function | Parameters | Returns | Purpose |
|----------|-----------|---------|---------|
| `doGet(e)` | event object | HtmlOutput | Router |
| `simpanDataKandidat(formObject)` | form data object | string | Save candidate |
| `getRecruitmentList()` | none | array | Fetch all candidates |
| `updateCandidateStatus(recruitmentId, newStatus, hrNotes)` | id, status, notes | object | Update status |
| `holdCandidate(recruitmentId, reason, followUpDate, hrNotes)` | id, reason, date, notes | object | Hold candidate |
| `blacklistCandidate(recruitmentId, reason, hrNotes)` | id, reason, notes | object | Blacklist candidate |
| `acceptCandidateToEmployee(recruitmentId, hrNotes)` | id, notes | object | Accept & create employee |
| `saveHrNotes(recruitmentId, hrNotes)` | id, notes | object | Save HR notes |
| `getAuditLogForCandidate(recruitmentId)` | id | array | Get activity timeline |

### Private Functions (Internal Use)

| Function | Purpose |
|----------|---------|
| `validateFormData_(f)` | Server-side form validation |
| `generateRecruitmentId_(timestamp)` | Generate unique recruitment ID |
| `generateEmployeeId_(timestamp)` | Generate unique employee ID |
| `getOrCreateSheet_()` | Get or create data_kandidat sheet |
| `getDashboardSheet_()` | Get dashboard sheet |
| `getOrCreateEmployeeSheet_()` | Get or create Employee sheet |
| `ensureExtraHeaders_(sheet)` | Add extra columns safely |
| `findCandidateRow_(sheet, recruitmentId)` | Find candidate row by ID |
| `setCell_(sheet, found, headerName, value)` | Set cell value by header name |
| `touchUpdatedAt_(sheet, found)` | Update timestamp |
| `writeAuditLog_(recruitmentId, action, oldValue, newValue)` | Write audit log |
| `setupSpreadsheet()` | Manual setup function |

### Google Services Used
- `SpreadsheetApp` - Spreadsheet operations
- `HtmlService` - HTML rendering
- `PropertiesService` - Counter storage
- `LockService` - Concurrent access control
- `Session` - User information
- `Utilities` - Date formatting

---

## Frontend Pages

### Dashboard.html (1890 lines)
**Route**: `?page=dashboard`  
**Purpose**: Full HR dashboard for candidate management  
**Key Features**:
- Statistics with animated counters
- 7 Chart.js visualizations
- Advanced data table with filters
- Candidate profile drawer
- Export to CSV/Excel/PDF
- Dark mode
- Settings page
- FAB speed dial

**State Management**:
- `allCandidates` - Full dataset
- `filteredCandidates` - Filtered dataset
- `currentPage` - Pagination state
- `chartInstances` - Chart.js instances
- `activeCandidateId` - Selected candidate
- `notesAutoSaveTimer` - Debounce timer

### FormPendaftaran.html (978 lines)
**Route**: `?type=kandidat` (default)  
**Purpose**: Candidate registration portal  
**Key Features**:
- Basic statistics
- Candidate table
- Search and filter
- Refresh functionality

**Note**: Registration form fields not visible in current file - may be in separate template or dynamically generated.

---

## Spreadsheet Structure

### Sheet 1: data_kandidat
**Purpose**: Store all candidate data  
**Total Columns**: 31 (25 core + 6 extra)

**Core Columns (SHEET_HEADERS)**:
1. Recruitment ID (string) - Primary key
2. Created Date (string) - "yyyy-MM-dd HH:mm:ss"
3. Full Name (string)
4. NIK (string) - 16 digits, stored with leading apostrophe
5. Birth Date (string) - "yyyy-MM-dd"
6. Age (number)
7. Gender (string) - "Male" or "Female"
8. Marital Status (string)
9. Email (string)
10. Phone (string) - Indonesian format, stored with leading apostrophe
11. Address (string)
12. City (string)
13. Position Applied (string)
14. Education (string)
15. Work Experience (string)
16. Last Company (string)
17. Current Employment Status (string)
18. Available to Join (string)
19. Expected Salary (number)
20. Recruitment Source (string)
21. CV Link (string)
22. Status (string) - Pending/Accepted/Hold/Blacklist
23. HR Notes (string) - Max 2000 characters
24. Created By (string)
25. Updated At (string) - "yyyy-MM-dd HH:mm:ss"

**Extra Columns (EXTRA_HEADERS)**:
26. Hold Reason (string)
27. Hold Follow Up Date (string)
28. Blacklist Reason (string)
29. Blacklist Date (string)
30. Blacklist Updated By (string)
31. Employee ID (string)

**Header Styling**: Bold, #005BAC background, white text, frozen first row

### Sheet 2: Employee
**Purpose**: Master employee data  
**Total Columns**: 10

**Columns (EMPLOYEE_HEADERS)**:
1. Employee ID (string) - Format: EMP-YYYY-0000
2. Recruitment ID (string) - Link to data_kandidat
3. Full Name (string)
4. Position (string)
5. Email (string)
6. Phone (string)
7. Join Date (string) - "yyyy-MM-dd"
8. Status (string) - "Active"
9. Notes (string)
10. Created At (string) - "yyyy-MM-dd HH:mm:ss"

**Header Styling**: Bold, #005BAC background, white text, frozen first row

### Sheet 3: Audit_Log
**Purpose**: Track all status changes and actions  
**Total Columns**: 6

**Columns**:
1. Timestamp (string) - "yyyy-MM-dd HH:mm:ss"
2. User (string) - Email or "HR Dashboard"
3. Recruitment ID (string)
4. Action (string) - Created/Hold/Blacklist/Accepted/Update Status
5. Old Value (string)
6. New Value (string)

**Header Styling**: Bold, #005BAC background, white text, frozen first row

---

## Data Flow

### 1. Candidate Registration Flow
```
User fills form
    ↓
FormPendaftaran.html validates
    ↓
google.script.run.simpanDataKandidat(formObject)
    ↓
Kode.gs validates server-side
    ↓
Generate Recruitment ID
    ↓
Append row to data_kandidat sheet
    ↓
Write audit log (Created: - → Pending)
    ↓
Return "Sukses" to frontend
    ↓
Show success message
```

### 2. Dashboard Data Loading Flow
```
Dashboard.html loads
    ↓
DOMContentLoaded event
    ↓
google.script.run.getRecruitmentList()
    ↓
Kode.gs reads data_kandidat sheet
    ↓
Map to objects with camelCase properties
    ↓
Return array to frontend
    ↓
populateAllDropdowns() - Build filter options
    ↓
updateStats() - Calculate statistics
    ↓
applyFilters() - Apply default filters
    ↓
renderTable() - Display paginated data
    ↓
renderCharts() - Draw visualizations
```

### 3. Status Update Flow
```
HR clicks status button (Accept/Hold/Blacklist/Pending)
    ↓
Show confirmation modal (if needed)
    ↓
google.script.run.[statusFunction](id, [reason], [notes])
    ↓
Kode.gs acquires LockService lock
    ↓
Find candidate row by Recruitment ID
    ↓
Update status and related fields
    ↓
Write audit log
    ↓
Release lock
    ↓
Return success/error to frontend
    ↓
Update local state
    ↓
Refresh table and stats
    ↓
Show toast notification
```

### 4. HR Notes Auto-Save Flow
```
User types in HR Notes textarea
    ↓
input event fires
    ↓
Clear previous timer
    ↓
Set new timer (1200ms)
    ↓
Timer fires → persistHrNotes()
    ↓
google.script.run.saveHrNotes(id, notes)
    ↓
Kode.gs updates HR Notes column
    ↓
Update Updated At timestamp
    ↓
Return success with timestamp
    ↓
Update UI state to "Tersimpan"
```

### 5. Employee Acceptance Flow
```
HR clicks Accept button
    ↓
Show confirmation modal
    ↓
google.script.run.acceptCandidateToEmployee(id, notes)
    ↓
Kode.gs acquires lock
    ↓
Find candidate row
    ↓
Generate Employee ID (EMP-YYYY-0000)
    ↓
Update candidate status to "Accepted"
    ↓
Set Employee ID in data_kandidat
    ↓
Append row to Employee sheet (if not exists)
    ↓
Write audit log
    ↓
Release lock
    ↓
Return success with Employee ID
    ↓
Update UI and show toast
```

---

## Dependencies

### External CDN Dependencies
1. **Bootstrap 5.3.0**
   - CSS: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css`
   - JS: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js`
   - Purpose: UI framework, modals, grid system

2. **Bootstrap Icons 1.11.3**
   - CSS: `https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css`
   - Purpose: Icon library

3. **Chart.js 4.4.0**
   - JS: `https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js`
   - Purpose: Data visualization (7 charts)

4. **SheetJS (xlsx) 0.18.5**
   - JS: `https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js`
   - Purpose: Excel export functionality

5. **Google Fonts - Inter**
   - URL: `https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap`
   - Purpose: Primary font family

### Google Apps Script Services
- `SpreadsheetApp` - Spreadsheet CRUD operations
- `HtmlService` - HTML output and templating
- `PropertiesService` - Persistent key-value storage (counters)
- `LockService` - Concurrent access control
- `Session` - Active user information
- `Utilities` - Date/time utilities

---

## Naming Convention

### Apps Script (Kode.gs)
- **Public Functions**: camelCase (`simpanDataKandidat`, `getRecruitmentList`)
- **Private Functions**: snake_case with trailing underscore (`getOrCreateSheet_`, `validateFormData_`)
- **Constants**: UPPER_SNAKE_CASE (`SHEET_NAME`, `DASHBOARD_SHEET_NAME`)
- **Variables**: camelCase (`recruitmentId`, `createdDate`)

### HTML/CSS
- **IDs**: camelCase (`btnRefresh`, `tableBody`, `searchInput`)
- **Classes**: kebab-case (`stat-card`, `badge-status`, `filter-bar`)
- **Data Attributes**: camelCase (`data-page`, `data-status`, `data-id`)

### Spreadsheet
- **Sheet Names**: PascalCase (`data_kandidat`, `Audit_Log`, `Employee`)
- **Column Headers**: PascalCase (`Recruitment ID`, `Created Date`, `Full Name`)

---

## Coding Style

### JavaScript (Apps Script)
```javascript
// Indentation: 2 spaces
// Quotes: Single quotes
// Semicolons: Required

// Section dividers
// ============================================================
// SECTION NAME
// ============================================================

// Function naming
function publicFunction(param) {
  // Implementation
}

function privateFunction_(param) {
  // Implementation
}

// Error handling
try {
  // Operation
  return { success: true, data: result };
} catch (error) {
  return { success: false, message: error.message };
} finally {
  // Cleanup
}
```

### HTML
```html
<!-- Indentation: 2 spaces -->
<!-- Quotes: Double quotes for attributes -->
<!-- Self-closing tags: Required -->

<div class="container">
  <button id="btnAction" type="button">Action</button>
</div>
```

### CSS
```css
/* Indentation: 2 spaces */
/* Selectors: kebab-case */
/* Properties: kebab-case */

.stat-card {
  background: #fff;
  border-radius: 16px;
}
```

---

## Future Expansion

### Planned Modules (Not Yet Implemented)
1. **Interview Module** - Schedule and track interviews
2. **Master Employee** - Full employee master data management
3. **Attendance System** - Time tracking and attendance
4. **Payroll Module** - Salary calculation and payslip generation
5. **Asset Management** - Company asset tracking
6. **Performance Review** - Employee performance evaluation
7. **Training Module** - Training program management
8. **Executive Dashboard** - High-level analytics and KPIs
9. **AI Recruitment** - AI-powered candidate screening
10. **Notification System** - Email/SMS notifications

### Potential Enhancements
- Multi-language support (i18n)
- Advanced reporting with custom reports
- Integration with email providers
- Document upload/storage (Google Drive)
- Interview scheduling with calendar integration
- Offer letter generation
- Onboarding checklist
- Employee self-service portal
- Mobile app (Progressive Web App)
- API endpoints for third-party integrations

---

## Deployment

### Google Apps Script Deployment
1. Open Google Apps Script editor
2. Copy `Kode.gs` content to Code.gs
3. Copy HTML files to project
4. Set `doGet(e)` as entry point
5. Deploy as web app:
   - Execute as: Me
   - Who has access: Anyone with link / Domain only
6. Get deployment URL

### Spreadsheet Setup
1. Create new Google Spreadsheet
2. Open Apps Script editor from spreadsheet
3. Paste code and HTML files
4. Run `setupSpreadsheet()` once to create sheets
5. Verify sheets created: data_kandidat, Employee, Audit_Log

### Browser Requirements
- Modern browser with JavaScript enabled
- Recommended: Chrome, Firefox, Edge, Safari (latest versions)
- Required: ES6+ support, Fetch API, Blob API

---

## Maintenance

### Regular Tasks
- Monitor spreadsheet size (Google Sheets limit: 10M cells)
- Review audit logs for data integrity
- Clean up old test data
- Update CDN versions periodically
- Backup spreadsheet data regularly

### Performance Considerations
- Client-side filtering for better performance
- Pagination to limit data transfer
- Debounced auto-save to reduce API calls
- Lock service to prevent concurrent write conflicts
- Chart.js instance cleanup to prevent memory leaks

---

## Support

### Common Issues
1. **Function not found**: Ensure only one `.gs` file exists
2. **Column not found**: Don't rename spreadsheet headers
3. **Permission denied**: Check Apps Script deployment settings
4. **Data not saving**: Verify NIK/Phone format (text with apostrophe)
5. **Charts not loading**: Check Chart.js CDN availability

### Debugging
- Use `Logger.log()` for server-side debugging
- Use `console.log()` for client-side debugging
- Check Apps Script Execution Log
- Review spreadsheet for data integrity
- Test with browser DevTools Network tab

---

## Last Updated
2026-07-30 - Initial documentation based on codebase analysis
