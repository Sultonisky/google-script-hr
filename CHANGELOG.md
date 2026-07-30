# CHANGELOG.md — Version History

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-07-30

### Added
- Initial release of Mahakarya HRIS - Applicant Tracking System
- Google Apps Script backend with single-file architecture (Kode.gs)
- Candidate registration portal (FormPendaftaran.html)
- HR Dashboard with advanced analytics (Dashboard.html)
- Spreadsheet database with 3 sheets:
  - raw_kandidat (31 columns: 25 core + 6 extra)
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
| 1.0.0 | 2026-07-30 | Initial release - Foundation & Recruitment Portal |

---

## How to Update This File

When making changes, add a new section at the top following this format:

```markdown
## [VERSION] - YYYY-MM-DD

### Added
- New features

### Changed
- Changes to existing features

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
2026-07-30 - Initial changelog for version 1.0.0