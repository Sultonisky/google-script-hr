# CHANGELOG.md — Version History

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.6.0] - 2026-08-03

### Added
- Architecture documentation (docs/ARCHITECTURE.md)
  - System overview and tech stack
  - Module architecture diagram
  - Data flow and spreadsheet schema
  - Security model and performance considerations
- Developer guide (docs/CONTRIBUTING.md)
  - Setup and development workflow
  - Code style and conventions
  - Bug reporting and feature request templates
- Updated development roadmap for v0.6.0 stabilization phase

### Changed
- Versioned roadmap as v0.6.0 (previously unversioned)
- Renamed v1.0.0 initial release to v0.1.0 (proper semantic versioning)
- Updated CHANGELOG.md with proper version history

### Fixed
- N/A

### Removed
- N/A

### Security
- N/A

### Documentation
- Created docs/ARCHITECTURE.md - complete system architecture documentation
- Created docs/CONTRIBUTING.md - developer contribution guide
- Updated ROADMAP.md - v0.6.0 stabilization phase roadmap
- Updated CHANGELOG.md - this file

---

## [0.5.0] - 2026-07-30

### Added
- UI polish and deployment readiness improvements
- Enhanced visual design across all modules
- Improved responsive layouts
- Performance optimizations

### Changed
- N/A

### Fixed
- N/A

### Removed
- N/A

### Security
- N/A

### Documentation
- N/A

---

## [0.4.0] - 2026-07-30

### Added
- Multi-module expansion
- Employee management module
- Master data module
- Settings module
- User management module
- Authentication system
- Role-based access control (RBAC)
- Portal settings management

### Changed
- N/A

### Fixed
- N/A

### Removed
- N/A

### Security
- Login/logout system
- Session management
- Role-based permissions (Admin, HRD, User)

### Documentation
- N/A

---

## [0.3.0] - 2026-07-29

### Added
- Modularization of backend code
- Separate backend modules:
  - Auth.gs - Authentication
  - Config.gs - Configuration
  - Employee.gs - Employee management
  - Import.gs - Data import
  - MasterData.gs - Master data
  - Outsource.gs - Outsource management
  - PortalSettings.gs - Portal settings
  - Recruitment.gs - Recruitment management
- Modular frontend views:
  - Dashboard.html - HR Dashboard
  - Employee.html - Employee management
  - Landing.html - Public landing page
  - Login.html - Login page
  - MasterData.html - Master data management
  - Settings.html - Settings page
  - UserManagement.html - User management
  - CandidateLanding.html - Candidate landing
  - AccessDenied.html - Access denied page
- Frontend JavaScript modules:
  - js/app.html - Main app logic
  - js/auth.html - Authentication
  - js/employee.html - Employee
  - js/filters.html - Filters
  - js/import.html - Import
  - js/masterData.html - Master data
  - js/settings.html - Settings
  - js/table.html - Table
  - js/userManagement.html - User management
- HTML partials:
  - partials/CandidateTable.html
  - partials/Modals.html
  - partials/SettingsPanel.html
  - partials/Sidebar.html
  - partials/Topbar.html
- CSS partials:
  - css/modal.html
  - css/table.html

### Changed
- N/A

### Fixed
- N/A

### Removed
- N/A

### Security
- N/A

### Documentation
- N/A

---

## [0.2.0] - 2026-07-29

### Added
- Advanced dashboard features
- Analytics visualizations
- Enhanced data table
- Advanced filtering and sorting
- Candidate profile drawer
- Export functionality (CSV, Excel, PDF)
- Dark mode
- Settings page
- FAB speed dial
- Toast notifications

### Changed
- N/A

### Fixed
- N/A

### Removed
- N/A

### Security
- N/A

### Documentation
- N/A

---

## [0.1.0] - 2026-07-28

### Added
- Initial release of MITO HRIS - Applicant Tracking System
- Google Apps Script backend with single-file architecture (Kode.gs)
- Candidate registration portal (FormPendaftaran.html)
- HR Dashboard with advanced analytics (Dashboard.html)
- Spreadsheet database with 3 sheets:
  - data_kandidat (31 columns: 25 core + 6 extra)
  - Employee (10 columns)
  - Audit_Log (6 columns)
- Unique ID generation system:
  - Recruitment ID (REC-YYYYMMDD-000001) with daily counter
  - Employee ID (EMP-YYYY-0000) with yearly counter
- Server-side form validation (16 fields)
- Status management:
  - Pending (default)
  - Accepted (with Employee ID generation)
  - Hold (with reason and follow-up date)
  - Blacklist (with reason and timestamp)
- HR Notes auto-save with 1200ms debounce
- Audit logging for all status changes
- Dashboard features:
  - 10 statistics cards with animated counters
  - 7 Chart.js visualizations
  - Advanced data table with pagination
  - Multi-criteria filtering and sorting
  - Candidate profile drawer
  - Export to CSV, Excel (XLSX), PDF
  - Dark mode toggle
  - Settings page
  - FAB speed dial
  - Print functionality
  - Toast notifications
- Responsive design (mobile, tablet, desktop)
- Indonesian language interface

### Changed
- N/A (Initial release)

### Fixed
- N/A (Initial release)

### Removed
- N/A (Initial release)

### Security
- LockService implementation for concurrent operations
- Server-side validation for all form inputs
- Input sanitization for XSS prevention
- Text storage for NIK and Phone (preserves leading zeros)

### Documentation
- AGENTS.md - AI Agent instructions
- PROJECT.md - Technical documentation
- ROADMAP.md - Development roadmap
- CHANGELOG.md - This file
- TODO.md - Task tracking

---

## [Unreleased]

### Added
- Features in development (see ROADMAP.md Phase 2)

### Changed
- N/A

### Fixed
- N/A

### Removed
- N/A

---

## Version History Summary

| Version | Date | Description |
|---------|------|-------------|
| 0.6.0 | 2026-08-03 | Stabilization: Documentation, Versioning, Testing |
| 0.5.0 | 2026-07-30 | UI Polish & Deployment Readiness |
| 0.4.0 | 2026-07-30 | Multi-Module Expansion |
| 0.3.0 | 2026-07-29 | Modularization |
| 0.2.0 | 2026-07-29 | Advanced Dashboard & Analytics |
| 0.1.0 | 2026-07-28 | Initial release - Foundation & Recruitment Portal |

---

## How to Update This File

When making changes, add a new section at the top following this format:

```markdown
## [VERSION] - YYYY-MM-DD

### Added
- New features

### Changed
- Changes to existing functionality

### Fixed
- Bug fixes

### Removed
- Removed features

### Security
- Security improvements

### Documentation
- Documentation updates
```

### Categories Legend
- **Added**: New features
- **Changed**: Changes to existing functionality
- **Deprecated**: Features that will be removed in future versions
- **Removed**: Features that have been removed
- **Fixed**: Bug fixes
- **Security**: Security improvements and vulnerability fixes
- **Documentation**: Documentation updates

---

## Notes

- This changelog tracks all notable changes to the project
- Each version should have a corresponding git tag (when version control is implemented)
- Breaking changes should be clearly marked
- Security fixes should be highlighted
- All user-facing changes should be documented in Indonesian

---

## Last Updated
2026-08-03 - Updated for v0.6.0 stabilization phase
