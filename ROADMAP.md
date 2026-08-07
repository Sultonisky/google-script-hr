# ROADMAP.md — Development Roadmap

## Current Status: v0.6.0 Stabilization Phase 🔧

**Version**: 0.6.0  
**Last Updated**: 2026-08-03

### Version History

| Version | Date | Milestone |
|---------|------|-----------|
| v0.1.0 | 2026-07-28 | Phase 1: Foundation & Recruitment Portal |
| v0.2.0 | 2026-07-29 | Phase 2: Advanced Dashboard & Analytics |
| v0.3.0 | 2026-07-29 | Phase 2.5: Modularization |
| v0.4.0 | 2026-07-30 | Phase 3: Multi-Module Expansion |
| v0.5.0 | 2026-07-30 | Phase 4: UI Polish & Deployment Readiness |
| v0.6.0 | 2026-08-03 | Stabilization: Documentation, Versioning, Testing |

---

## v0.6.0 — Stabilization Phase 🔧 CURRENT

**Status**: In Progress  
**Objective**: Stabilize the existing codebase, add documentation, set up proper versioning, and prepare for production deployment

### Stabilization Tasks

#### Documentation
- [x] Create docs/ directory structure
- [x] Create ARCHITECTURE.md — system architecture documentation
- [x] Create CONTRIBUTING.md — developer guide
- [ ] Update ROADMAP.md for v0.6.0
- [ ] Update CHANGELOG.md for v0.6.0

#### Versioning
- [ ] Set up package.json (if applicable)
- [ ] Initialize .gitignore
- [ ] Tag v0.6.0

#### Master Data Foundation
- [ ] Ensure master data categories are properly seeded
- [ ] Validate dropdown data integrity across modules
- [ ] Test master data CRUD operations

#### Employee Foundation
- [ ] Validate employee sheet schema (32 columns)
- [ ] Test employee CRUD operations
- [ ] Test outsource employee registration
- [ ] Verify employee ID generation (EMP-YYYY-NNNN)

#### Code Quality
- [ ] Review backend module boundaries
- [ ] Ensure consistent error handling across all modules
- [ ] Verify audit logging for all status changes
- [ ] Test RBAC permissions for all roles

#### Deployment Readiness
- [ ] Document deployment steps
- [ ] Create .gitignore
- [ ] Verify OAuth scopes
- [ ] Test public pages (landing, registration, candidate landing)
- [ ] Test authenticated pages (dashboard, employee, master data, settings, user management)

### Deliverables
- ARCHITECTURE.md
- CONTRIBUTING.md
- Updated CHANGELOG.md
- Updated ROADMAP.md
- .gitignore

---

## Phase 1: Foundation & Recruitment Portal ✅ COMPLETE (v0.1.0)

**Status**: Implemented  
**Duration**: Completed  
**Objective**: Establish core recruitment management system

### Features Implemented
- ✅ Google Apps Script backend with single-file architecture
- ✅ Spreadsheet database setup (data_kandidat, Employee, Audit_Log)
- ✅ Candidate registration form with validation
- ✅ Unique ID generation (Recruitment ID with daily counter)
- ✅ Basic dashboard with statistics
- ✅ Candidate list view with search and filter
- ✅ Responsive design with Bootstrap 5

### Deliverables
- `Kode.gs` - Backend API (655 lines)
- `FormPendaftaran.html` - Registration portal (978 lines)
- `Dashboard.html` - Basic dashboard (1890 lines)
- 3 spreadsheet sheets with proper structure

### Dependencies
- Google Apps Script runtime
- Google Spreadsheet
- Bootstrap 5.3.0
- Bootstrap Icons 1.11.3

---

## Phase 2: Advanced Dashboard & Analytics 🔄 IN PROGRESS

**Status**: Partially implemented  
**Objective**: Enhance dashboard with advanced analytics and candidate management

### Features Implemented
- ✅ 10 statistics cards with animated counters
- ✅ 7 Chart.js visualizations (monthly, position, city, education, gender, source, status)
- ✅ Advanced data table with pagination (10/25/50/100 per page)
- ✅ Multi-criteria filtering (status, position, education, gender, source, experience, city)
- ✅ Advanced filters (salary range, age range, date range)
- ✅ Sorting (newest, oldest, name, age, salary)
- ✅ Column header click sorting
- ✅ Candidate profile drawer with detailed view
- ✅ HR notes with auto-save (1200ms debounce)
- ✅ Activity timeline with audit log
- ✅ Export to CSV, Excel (XLSX), PDF
- ✅ Dark mode toggle
- ✅ Settings page (theme, page size, default status, auto-refresh)
- ✅ FAB speed dial for quick actions
- ✅ Print functionality
- ✅ Toast notifications
- ✅ Responsive design (mobile/tablet/desktop)

### Features Pending
- ⏳ Advanced search with fuzzy matching
- ⏳ Bulk actions (bulk status update, bulk delete)
- ⏳ Data import from CSV/Excel
- ⏳ Custom report builder
- ⏳ Dashboard customization (drag-drop widgets)

### Deliverables
- Enhanced `Dashboard.html` with full feature set
- Client-side filtering and sorting engine
- Chart.js integration with 7 visualizations
- Export functionality (CSV, XLSX, PDF)

### Dependencies
- Chart.js 4.4.0
- SheetJS 0.18.5
- Browser APIs (Blob, URL.createObjectURL)

---

## Phase 3: Candidate Management & Workflow 🔜 PLANNED

**Objective**: Complete candidate lifecycle management with workflow automation

### Features Planned
- 📋 Candidate status workflow automation
  - Auto-transition rules (e.g., auto-hold after 14 days)
  - Status change notifications
  - Workflow history visualization
- 📋 Advanced candidate profile
  - Document upload (CV, portfolio, certificates) to Google Drive
  - Interview scheduling with calendar integration
  - Evaluation forms and scoring
  - Reference check tracking
- 📋 Communication tracking
  - Email integration (Gmail API)
  - SMS notifications (Twilio or similar)
  - Interview invitation templates
- 📋 Candidate comparison
  - Side-by-side comparison view
  - Scoring matrix
  - Ranking system

### Deliverables
- Enhanced candidate profile with document management
- Workflow automation engine
- Communication module
- Comparison tools

### Dependencies
- Google Drive API
- Gmail API
- Calendar API
- Google Apps Script Advanced Services

---

## Phase 4: Interview Module 🔜 PLANNED

**Objective**: Manage interview process from scheduling to evaluation

### Features Planned
- 📅 Interview scheduling
  - Calendar integration with interviewers
  - Room/resource booking
  - Interview invitation emails
  - Reminder notifications
- 📅 Interview panel management
  - Interviewer assignment
  - Panel composition
  - Availability tracking
- 📅 Interview evaluation
  - Evaluation form builder
  - Scorecard templates
  - Multi-panel scoring aggregation
  - Feedback collection
- 📅 Interview workflow
  - Interview round management (HR, Technical, Final)
  - Next steps automation
  - Rejection/advancement notifications

### Deliverables
- Interview scheduling system
- Evaluation form system
- Panel management
- Notification system

### Dependencies
- Google Calendar API
- Gmail API
- New spreadsheet sheets: Interview_Schedule, Interview_Evaluation, Interview_Panel

---

## Phase 5: Master Employee Management 🔜 PLANNED

**Objective**: Complete employee master data and lifecycle management

### Features Planned
- 👤 Employee master data
  - Complete employee profile
  - Organizational hierarchy (department, division, reporting manager)
  - Employment history
  - Salary history
- 👤 Employee self-service
  - Personal information update
  - Leave request submission
  - Document access
  - Profile view
- 👤 Employee onboarding
  - Onboarding checklist
  - Document collection tracking
  - Equipment assignment
  - Training schedule
- 👤 Employee offboarding
  - Resignation processing
  - Exit interview
  - Asset return tracking
  - Knowledge transfer

### Deliverables
- Enhanced Employee sheet with complete master data
- Employee self-service portal
- Onboarding/offboarding workflows
- Organizational chart

### Dependencies
- Enhanced Employee spreadsheet schema
- Google Drive API for document storage
- Email notification system

---

## Phase 6: Attendance & Leave Management 🔜 PLANNED

**Objective**: Time tracking and leave management system

### Features Planned
- ⏰ Attendance tracking
  - Clock in/out (web-based)
  - GPS location verification
  - Photo capture (optional)
  - Attendance reports
- ⏰ Leave management
  - Leave request submission
  - Leave balance tracking
  - Approval workflow
  - Leave calendar
  - Public holiday management
- ⏰ Overtime tracking
  - Overtime request
  - Approval workflow
  - Overtime calculation
- ⏰ Shift management
  - Shift scheduling
  - Shift assignment
  - Shift swap requests

### Deliverables
- Attendance tracking system
- Leave management module
- Overtime tracking
- Shift management

### Dependencies
- New spreadsheet sheets: Attendance, Leave_Request, Leave_Balance, Shift
- Time zone handling (GMT+7)
- Date/time calculation utilities

---

## Phase 7: Payroll Module 🔜 PLANNED

**Objective**: Salary calculation and payslip generation

### Features Planned
- 💰 Salary configuration
  - Salary structure setup
  - Component management (basic, allowances, deductions)
  - Tax calculation (PPh 21)
  - BPJS calculation (BPJS Kesehatan, BPJS Ketenagakerjaan)
- 💰 Payroll processing
  - Automated salary calculation
  - Attendance integration
  - Leave deduction
  - Overtime calculation
- 💰 Payslip generation
  - Digital payslip (PDF)
  - Email distribution
  - Payslip history
- 💰 Payroll reporting
  - Salary summary reports
  - Tax reports (for BPJS/PTKP)
  - Cost analysis

### Deliverables
- Payroll calculation engine
- Payslip generator
- Tax and BPJS calculator
- Payroll reports

### Dependencies
- New spreadsheet sheets: Payroll_Config, Payroll_History, Payroll_Detail
- Attendance and Leave modules (Phase 6)
- Tax regulation updates (Indonesia PPh 21)
- PDF generation library

---

## Phase 8: Asset Management 🔜 PLANNED

**Objective**: Track company assets assigned to employees

### Features Planned
- 💼 Asset registry
  - Asset catalog (laptops, phones, equipment, etc.)
  - Asset categories
  - Purchase tracking
  - Depreciation calculation
- 💼 Asset assignment
  - Employee asset assignment
  - Assignment history
  - Return tracking
  - Damage/loss reporting
- 💼 Asset maintenance
  - Maintenance schedule
  - Service history
  - Warranty tracking
- 💼 Asset reports
  - Asset inventory
  - Assignment reports
  - Depreciation reports

### Deliverables
- Asset management module
- Assignment tracking
- Maintenance scheduling
- Asset reports

### Dependencies
- New spreadsheet sheets: Asset, Asset_Assignment, Asset_Maintenance
- Barcode/QR code generation (optional)
- Notification system

---

## Phase 9: Performance Review 🔜 PLANNED

**Objective**: Employee performance evaluation system

### Features Planned
- ⭐ Performance management
  - Goal setting (OKR/KPI)
  - Performance indicators
  - Review cycles (quarterly, annual)
  - 360-degree feedback
- ⭐ Evaluation process
  - Self-assessment
  - Manager assessment
  - Peer review
  - Calibration meetings
- ⭐ Performance tracking
  - Performance history
  - Improvement plans
  - Achievement tracking
- ⭐ Performance reports
  - Individual performance reports
  - Department performance
  - Company performance dashboard

### Deliverables
- Performance review module
- Goal tracking system
- 360-degree feedback
- Performance reports

### Dependencies
- New spreadsheet sheets: Performance_Goal, Performance_Review, Performance_Feedback
- Employee master data (Phase 5)
- Notification system

---

## Phase 10: Training & Development 🔜 PLANNED

**Objective**: Training program management and tracking

### Features Planned
- 📚 Training catalog
  - Training programs
  - Training materials
  - External training providers
  - Training calendar
- 📚 Training assignment
  - Mandatory training tracking
  - Optional training enrollment
  - Training history
  - Certification tracking
- 📚 Training evaluation
  - Post-training assessment
  - Feedback collection
  - Effectiveness measurement
- 📚 Training reports
  - Training completion rates
  - Cost per employee
  - Skills gap analysis

### Deliverables
- Training management module
- Assignment and tracking
- Evaluation system
- Training reports

### Dependencies
- New spreadsheet sheets: Training, Training_Assignment, Training_Evaluation
- Employee master data (Phase 5)
- Document management (Google Drive)

---

## Phase 11: Executive Dashboard 🔜 PLANNED

**Objective**: High-level analytics and KPIs for executive decision-making

### Features Planned
- 📊 Executive KPIs
  - Headcount trends
  - Turnover rate
  - Time-to-hire
  - Cost-per-hire
  - Diversity metrics
- 📊 Financial dashboard
  - Salary cost analysis
  - Budget vs actual
  - Department cost allocation
- 📊 Recruitment analytics
  - Source effectiveness
  - Funnel analysis
  - Quality of hire
  - Offer acceptance rate
- 📊 Predictive analytics
  - Attrition prediction
  - Hiring needs forecasting
  - Market salary benchmarking

### Deliverables
- Executive dashboard with high-level KPIs
- Financial analytics
- Predictive analytics (basic)
- Exportable reports

### Dependencies
- All previous phases completed
- Advanced Chart.js visualizations
- Statistical calculations
- Data aggregation from all modules

---

## Phase 12: AI-Powered Recruitment 🔜 PLANNED

**Objective**: Leverage AI for smarter recruitment

### Features Planned
- 🤖 AI candidate screening
  - Resume parsing
  - Skill extraction
  - Match scoring
  - Bias reduction
- 🤖 Chatbot interface
  - FAQ chatbot for candidates
  - Application status chatbot
  - Interview scheduling chatbot
- 🤖 Predictive analytics
  - Candidate success prediction
  - Salary recommendation
  - Turnover risk assessment
- 🤖 Automated sourcing
  - Job board integration
  - Social media sourcing
  - Candidate database matching

### Deliverables
- AI screening module
- Chatbot interface
- Predictive models
- Automated sourcing tools

### Dependencies
- Machine Learning APIs (Google Cloud AI, OpenAI)
- Natural Language Processing
- Integration with job boards
- Advanced analytics engine

---

## Implementation Priority

### High Priority (Next 3 Months)
1. **Phase 2 Completion**: Advanced dashboard features
   - Bulk actions
   - Data import
   - Custom reports
2. **Phase 3 Start**: Candidate management enhancements
   - Document upload to Google Drive
   - Interview scheduling

### Medium Priority (3-6 Months)
3. **Phase 3 Completion**: Full candidate workflow
4. **Phase 4**: Interview module
5. **Phase 5 Start**: Master employee enhancements

### Low Priority (6-12 Months)
6. **Phase 5 Completion**: Employee self-service
7. **Phase 6**: Attendance & leave management
8. **Phase 7**: Payroll module

### Future (12+ Months)
9. **Phase 8**: Asset management
10. **Phase 9**: Performance review
11. **Phase 10**: Training & development
12. **Phase 11**: Executive dashboard
13. **Phase 12**: AI-powered recruitment

---

## Technical Debt & Improvements

### Code Quality
- [ ] Add unit tests for Apps Script functions
- [ ] Implement error tracking (Stackdriver)
- [ ] Add logging framework
- [ ] Code documentation with JSDoc
- [ ] Refactor duplicate code (Dashboard.html vs FormPendaftaran.html)

### Performance
- [ ] Implement server-side pagination
- [ ] Add caching layer (PropertiesService)
- [ ] Optimize chart rendering
- [ ] Lazy loading for large datasets
- [ ] Implement virtual scrolling for tables

### Security
- [ ] Add authentication/authorization
- [ ] Implement role-based access control (RBAC)
- [ ] Add input sanitization
- [ ] Enable HTTPS only
- [ ] Add CSRF protection

### Infrastructure
- [ ] Set up CI/CD pipeline
- [ ] Automated testing
- [ ] Code review process
- [ ] Version control (Git)
- [ ] Deployment automation

---

## Milestones

| Milestone | Target Date | Status |
|-----------|-------------|--------|
| Phase 1: Foundation | 2026-07-30 | ✅ Complete |
| Phase 2: Advanced Dashboard | 2026-08-15 | 🔄 In Progress |
| Phase 3: Candidate Management | 2026-09-30 | 📅 Planned |
| Phase 4: Interview Module | 2026-10-31 | 📅 Planned |
| Phase 5: Master Employee | 2026-11-30 | 📅 Planned |
| Phase 6: Attendance & Leave | 2026-12-31 | 📅 Planned |
| Phase 7: Payroll | 2027-01-31 | 📅 Planned |
| Phase 8-10: Additional Modules | 2027-03-31 | 📅 Planned |
| Phase 11-12: Advanced Features | 2027-06-30 | 📅 Planned |

---

## Notes

- This roadmap is a living document and will be updated as priorities change
- Each phase should be completed and tested before moving to the next
- Backward compatibility must be maintained throughout all phases
- User feedback should be collected after each phase completion
- Documentation must be updated with each phase completion

---

## Last Updated
2026-08-03 - Updated for v0.6.0 Stabilization Phase
