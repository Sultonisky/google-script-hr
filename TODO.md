# TODO.md — Task Tracking

**Project**: MITO HRIS - Applicant Tracking System  
**Version**: 1.0.0  
**Last Updated**: 2026-07-30

---

## High Priority

### Phase 2 Completion (Advanced Dashboard)
- [ ] Implement bulk actions (bulk status update, bulk delete)
- [ ] Add data import functionality (CSV/Excel import)
- [ ] Create custom report builder
- [ ] Implement dashboard customization (drag-drop widgets)
- [ ] Add advanced search with fuzzy matching
- [ ] Implement server-side pagination for large datasets
- [ ] Add caching layer for frequently accessed data

### Critical Improvements
- [ ] Add authentication/authorization system
- [ ] Implement role-based access control (RBAC)
- [ ] Add comprehensive error tracking (Stackdriver)
- [ ] Implement input sanitization for all user inputs
- [ ] Add CSRF protection
- [ ] Set up automated backup system for spreadsheet

### Bug Fixes
- [ ] Fix potential memory leak in Chart.js instances (ensure proper cleanup)
- [ ] Fix date parsing edge cases in filter functionality
- [ ] Resolve concurrent write conflicts in high-traffic scenarios
- [ ] Fix NIK/Phone validation for edge cases
- [ ] Address pagination issues when filtering results

---

## Medium Priority

### Phase 3: Candidate Management
- [ ] Implement document upload to Google Drive
  - CV upload
  - Portfolio upload
  - Certificate upload
  - Photo upload
- [ ] Add interview scheduling with calendar integration
- [ ] Create evaluation forms and scoring system
- [ ] Implement reference check tracking
- [ ] Add candidate comparison view (side-by-side)
- [ ] Implement scoring matrix and ranking system
- [ ] Add communication tracking (email/SMS)

### Phase 4: Interview Module
- [ ] Create interview scheduling system
- [ ] Implement interviewer assignment
- [ ] Build evaluation form builder
- [ ] Add scorecard templates
- [ ] Implement multi-panel scoring aggregation
- [ ] Create interview round management
- [ ] Add notification system for interviews

### Code Quality
- [ ] Add unit tests for Apps Script functions
- [ ] Implement logging framework
- [ ] Add JSDoc documentation to all functions
- [ ] Refactor duplicate code between Dashboard.html and FormPendaftaran.html
- [ ] Create shared JavaScript utilities module
- [ ] Implement error boundary handling

### Performance Optimization
- [ ] Optimize chart rendering for large datasets
- [ ] Implement lazy loading for table rows
- [ ] Add virtual scrolling for tables with 1000+ records
- [ ] Optimize spreadsheet read operations (batch reads)
- [ ] Implement client-side caching with TTL
- [ ] Reduce Chart.js memory footprint

---

## Low Priority

### Phase 5: Master Employee Management
- [ ] Enhance Employee sheet with complete master data
- [ ] Add organizational hierarchy (department, division, reporting manager)
- [ ] Implement employment history tracking
- [ ] Add salary history tracking
- [ ] Create employee self-service portal
- [ ] Build onboarding checklist
- [ ] Implement offboarding workflow
- [ ] Add organizational chart visualization

### Phase 6: Attendance & Leave Management
- [ ] Implement clock in/out system
- [ ] Add GPS location verification
- [ ] Create leave request system
- [ ] Implement leave balance tracking
- [ ] Add approval workflow
- [ ] Create leave calendar
- [ ] Implement public holiday management
- [ ] Add overtime tracking
- [ ] Create shift management system

### UI/UX Improvements
- [ ] Add keyboard shortcuts for common actions
- [ ] Implement undo/redo functionality
- [ ] Add tooltips for complex features
- [ ] Create onboarding tour for new users
- [ ] Add accessibility improvements (ARIA labels)
- [ ] Implement progressive web app (PWA) features
- [ ] Add offline mode support
- [ ] Create mobile app wrapper

### Reporting & Analytics
- [ ] Build custom report builder
- [ ] Add scheduled report generation
- [ ] Implement report templates
- [ ] Create dashboard sharing functionality
- [ ] Add data export scheduling
- [ ] Build ad-hoc query interface

---

## Ideas

### Feature Ideas
- [ ] Add candidate scoring/ranking system
- [ ] Implement AI-powered candidate matching
- [ ] Create referral tracking system
- [ ] Add social media integration (LinkedIn, etc.)
- [ ] Implement job board integration
- [ ] Add video interview support
- [ ] Create candidate feedback system
- [ ] Add offer letter generation
- [ ] Implement contract management
- [ ] Add benefits management
- [ ] Create expense reimbursement system
- [ ] Implement help desk/ticketing system
- [ ] Add knowledge base/wiki
- [ ] Create announcement system
- [ ] Add meeting scheduler

### Integration Ideas
- [ ] Google Calendar integration for interviews
- [ ] Gmail integration for notifications
- [ ] Google Drive integration for documents
- [ ] Slack/Microsoft Teams integration
- [ ] Zoom/Google Meet integration
- [ ] WhatsApp Business API for notifications
- [ ] LinkedIn API for candidate sourcing
- [ ] Job board APIs (JobStreet, Indeed, etc.)

### Automation Ideas
- [ ] Auto-send interview invitations
- [ ] Auto-remind for pending follow-ups
- [ ] Auto-archive old candidates
- [ ] Auto-generate reports on schedule
- [ ] Auto-assign recruiters to candidates
- [ ] Auto-update candidate status based on rules
- [ ] Auto-send offer letters
- [ ] Auto-create employee records from accepted candidates

---

## Technical Debt

### Code Refactoring
- [ ] Extract common CSS into shared stylesheet
- [ ] Create reusable JavaScript utility functions
- [ ] Implement consistent error handling pattern
- [ ] Standardize API response format
- [ ] Create shared component library
- [ ] Refactor monolithic Kode.gs into modules (when Apps Script supports it)
- [ ] Implement proper type checking (JSDoc or TypeScript)
- [ ] Add constants for magic numbers and strings

### Architecture Improvements
- [ ] Implement proper state management pattern
- [ ] Add API versioning strategy
- [ ] Create abstraction layer for spreadsheet operations
- [ ] Implement repository pattern for data access
- [ ] Add service layer for business logic
- [ ] Implement event-driven architecture
- [ ] Add middleware pattern for request processing

### Testing
- [ ] Set up testing framework for Apps Script
- [ ] Write unit tests for all public functions
- [ ] Write integration tests for data flows
- [ ] Add end-to-end testing for UI
- [ ] Create test data fixtures
- [ ] Implement CI/CD pipeline
- [ ] Add code coverage tracking
- [ ] Create test automation scripts

### Documentation
- [ ] Add inline code comments for complex logic
- [ ] Create API documentation
- [ ] Write user manual
- [ ] Create video tutorials
- [ ] Add troubleshooting guide
- [ ] Document deployment process
- [ ] Create architecture diagrams
- [ ] Add database schema documentation

---

## Bug Fixes

### Known Issues
- [ ] Fix: Chart.js instances not properly destroyed on page navigation
- [ ] Fix: Date filter not working correctly for edge cases (leap year, timezone)
- [ ] Fix: Concurrent updates may cause data loss in rare scenarios
- [ ] Fix: NIK validation too strict (rejects valid NIK with leading zeros)
- [ ] Fix: Phone validation fails for some valid Indonesian numbers
- [ ] Fix: Pagination breaks when filtering results with exact page size
- [ ] Fix: Auto-refresh continues after navigating away from dashboard
- [ ] Fix: Dark mode not persisted across sessions in some browsers
- [ ] Fix: Export functionality fails with special characters in data
- [ ] Fix: Mobile sidebar overlay not closing on Escape key

### Performance Issues
- [ ] Fix: Slow loading time with 1000+ candidates
- [ ] Fix: Chart rendering causes UI freeze on initial load
- [ ] Fix: Memory leak when opening/closing drawer repeatedly
- [ ] Fix: Excessive API calls during auto-refresh
- [ ] Fix: Table rendering slow with complex filters

### UI/UX Issues
- [ ] Fix: Button states not updating correctly after status change
- [ ] Fix: Toast notifications stacking incorrectly
- [ ] Fix: Loading spinner not showing in some scenarios
- [ ] Fix: Filter dropdowns not populating correctly on first load
- [ ] Fix: Print layout broken on some browsers
- [ ] Fix: Modal backdrop not closing on outside click

### Data Issues
- [ ] Fix: Duplicate Recruitment ID in rare race condition
- [ ] Fix: Audit log missing entries for failed operations
- [ ] Fix: Employee ID not generated when re-accepting candidate
- [ ] Fix: Extra headers not added to existing sheets correctly
- [ ] Fix: Data type inconsistency (numbers stored as strings)

---

## Completed Tasks

### Version 1.0.0 (2026-07-30)
- [x] Set up Google Apps Script project structure
- [x] Create single-file backend architecture (Kode.gs)
- [x] Implement spreadsheet database (3 sheets)
- [x] Build candidate registration form
- [x] Create HR Dashboard with statistics
- [x] Implement status management (Pending, Accepted, Hold, Blacklist)
- [x] Add HR Notes auto-save functionality
- [x] Implement audit logging system
- [x] Create unique ID generators
- [x] Add server-side validation
- [x] Implement responsive design
- [x] Add dark mode support
- [x] Create export functionality (CSV, Excel, PDF)
- [x] Add 7 Chart.js visualizations
- [x] Implement advanced filtering and sorting
- [x] Add candidate profile drawer
- [x] Create settings page
- [x] Add FAB speed dial
- [x] Implement print functionality
- [x] Add toast notifications
- [x] Create documentation (AGENTS.md, PROJECT.md, ROADMAP.md, CHANGELOG.md)

---

## Task Priority Matrix

| Task | Priority | Effort | Impact | Priority Score |
|------|----------|--------|--------|----------------|
| Add authentication | High | High | High | 9 |
| Bulk actions | High | Medium | High | 8 |
| Data import | High | Medium | High | 8 |
| Unit tests | Medium | High | Medium | 6 |
| Document upload | Medium | High | High | 7 |
| Interview module | Medium | High | High | 7 |
| Performance optimization | Medium | Medium | Medium | 5 |
| PWA support | Low | High | Low | 3 |
| AI features | Low | High | Medium | 4 |

**Priority Score**: (Priority + Impact) / Effort

---

## Sprint Planning

### Sprint 1 (Next 2 Weeks)
**Focus**: Phase 2 completion
- [ ] Bulk actions implementation
- [ ] Data import functionality
- [ ] Performance optimizations
- [ ] Bug fixes (top 5 issues)

### Sprint 2 (Following 2 Weeks)
**Focus**: Phase 3 start
- [ ] Document upload to Google Drive
- [ ] Interview scheduling
- [ ] Enhanced candidate profile
- [ ] Additional bug fixes

### Sprint 3 (Following 2 Weeks)
**Focus**: Code quality
- [ ] Unit tests for critical functions
- [ ] Error tracking implementation
- [ ] Code refactoring
- [ ] Documentation improvements

---

## Notes

- Tasks are prioritized based on business value and technical impact
- High priority items should be completed before moving to new features
- Technical debt should be addressed alongside feature development
- Bug fixes should be prioritized based on severity and user impact
- This TODO list should be reviewed and updated weekly
- Completed tasks should be moved to "Completed Tasks" section
- New tasks should be added to appropriate priority section

---

## How to Use This File

1. **Check off tasks** as they are completed: `[x]`
2. **Add new tasks** to appropriate section
3. **Update priority** based on changing requirements
4. **Move completed tasks** to "Completed Tasks" section
5. **Review weekly** in team meetings
6. **Update sprint planning** based on progress

---

## Last Updated
2026-07-30 - Initial TODO list based on current codebase analysis
