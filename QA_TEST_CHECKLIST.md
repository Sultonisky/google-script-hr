# QA / System Integration Testing Checklist
## Mahakarya HRIS — Applicant Tracking System
**Version**: 1.0  
**Date**: 2026-08-03  
**Status**: Ready for Execution  

---

## MODULE 1: AUTHENTICATION & AUTHORIZATION (backend/Auth.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| AUTH-001 | First-time setup: auto-create Super Admin | 1. Open Login page with empty Users sheet<br>2. Click "Buat Super Admin" button | Super Admin created with current Google account email; redirected to dashboard | | |
| AUTH-002 | First-time setup: empty Users sheet detection | 1. Delete all rows in Users sheet<br>2. Load Login page | Shows first-run setup form, not login form | | |
| AUTH-003 | First-time setup: cannot create second admin | 1. After creating first admin<br>2. Attempt autoCreateFirstAdmin() again | Returns error: "Sistem sudah memiliki pengguna" | | |
| AUTH-004 | Login: valid Google account, registered user | 1. Login with registered Google account<br>2. Check session | Returns isLoggedIn=true, correct email, fullName, role, permissions | | |
| AUTH-005 | Login: valid Google account, NOT registered | 1. Login with unregistered Google account | Returns isLoggedIn=false, email provided but no role/permissions | | |
| AUTH-006 | Login: Inactive user blocked | 1. Set user status to "Inactive" in Users sheet<br>2. User attempts login | Returns isLoggedIn=false, reason="inactive" | | |
| AUTH-007 | Login: Last login timestamp updated | 1. User logs in<br>2. Check Users sheet column "Last Login" | Last Login column updated with GMT+7 timestamp | | |
| AUTH-008 | Session check: getCurrentUser() returns profile | 1. Call getCurrentUser() for logged-in user | Returns {isLoggedIn, email, fullName, role, permissions[], photoUrl} | | |
| AUTH-009 | Session check: getCurrentUser() for anonymous | 1. Call getCurrentUser() without Google login | Returns isLoggedIn=false, empty fields | | |
| AUTH-010 | Permission check: requirePermission() returns true | 1. Login as Super Admin<br>2. Call requirePermission('manage_users') | Returns true | | |
| AUTH-011 | Permission check: requirePermission() returns false | 1. Login as Viewer<br>2. Call requirePermission('manage_users') | Returns false | | |
| AUTH-012 | Role hierarchy: Super Admin > HR Admin > Recruiter > Manager > Viewer | 1. Test requireRole() with each role against minimum requirements | Lower index roles pass higher-level checks | | |
| AUTH-013 | getPortalSettingsForLogin() returns branding | 1. Call getPortalSettingsForLogin() | Returns {companyName, companyLogo, companyTagline} with defaults if empty | | |

---

## MODULE 2: USER MANAGEMENT (backend/Auth.gs — CRUD)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| USER-001 | Get all users: Super Admin | 1. Login as Super Admin<br>2. Call getAllUsers() | Returns {success: true, users: [...]} with all user records | | |
| USER-002 | Get all users: non-Admin (denied) | 1. Login as Viewer<br>2. Call getAllUsers() | Returns {success: false, error: "Akses ditolak"} | | |
| USER-003 | Add user: valid data | 1. Call addUser({email, fullName, role: 'HR Admin'}) | Returns success: true, user appears in Users sheet | | |
| USER-004 | Add user: missing email | 1. Call addUser({fullName: 'Test', role: 'Viewer'}) | Returns error: "Email dan Nama Lengkap wajib diisi" | | |
| USER-005 | Add user: invalid role | 1. Call addUser with role: 'InvalidRole' | Returns error: "Role tidak valid" | | |
| USER-006 | Add user: duplicate email | 1. Add user with existing email | Returns error: "Email sudah terdaftar" | | |
| USER-007 | Add user: no permission (non-admin) | 1. Login as Recruiter<br>2. Call addUser() | Returns {success: false, error: "Akses ditolak"} | | |
| USER-008 | Update user: change role | 1. Call updateUser(email, {role: 'HR Admin'}) | User role updated in sheet, "Updated At" timestamp set | | |
| USER-009 | Update user: change status to Inactive | 1. Call updateUser(email, {status: 'Inactive'}) | User status set to "Inactive" | | |
| USER-010 | Update user: non-existent email | 1. Call updateUser('nonexist@test.com', {role: 'Viewer'}) | Returns error: "Pengguna tidak ditemukan" | | |
| USER-011 | Delete user: valid | 1. Call deleteUser(email) | User removed from Users sheet | | |
| USER-012 | Delete user: cannot delete self | 1. Call deleteUser with own email | Returns error: "Tidak dapat menghapus akun sendiri" | | |
| USER-013 | Delete user: non-existent | 1. Call deleteUser('ghost@test.com') | Returns error: "Pengguna tidak ditemukan" | | |
| USER-014 | Promote to Super Admin: only by Super Admin | 1. Login as HR Admin<br>2. Call promoteToSuperAdmin(email) | Returns error: "Hanya Super Admin" | | |
| USER-015 | Promote to Super Admin: by Super Admin | 1. Login as Super Admin<br>2. Call promoteToSuperAdmin(email) | Target user role updated to Super Admin | | |
| USER-016 | seedSuperAdmin: works only on empty sheet | 1. Call seedSuperAdmin on empty Users sheet | Returns success: true | | |
| USER-017 | seedSuperAdmin: blocked on populated sheet | 1. Call seedSuperAdmin when Users sheet has data | Returns error: "Users sheet sudah berisi data" | | |

---

## MODULE 3: CANDIDATE REGISTRATION (FormPendaftaran.html + backend/Recruitment.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| REG-001 | Submit complete valid form | 1. Fill all required fields<br>2. Submit form | Returns "Sukses"; new row in raw_kandidat with status "Pending" | | |
| REG-002 | Submit form: missing required field (full_name) | 1. Leave full_name empty<br>2. Submit | Returns error: "Nama lengkap wajib diisi" | | |
| REG-003 | Submit form: invalid NIK (not 16 digits) | 1. Enter NIK "123"<br>2. Submit | Returns validation error for NIK | | |
| REG-004 | Submit form: invalid email format | 1. Enter "notanemail"<br>2. Submit | Returns error: "Format email tidak valid" | | |
| REG-005 | Submit form: invalid phone (not starting with 62) | 1. Enter "0812345678"<br>2. Submit | Returns error for phone format | | |
| REG-006 | Submit form: expected salary not a number | 1. Enter "abc" in salary<br>2. Submit | Returns validation error for salary | | |
| REG-007 | Fresh Graduate: Last Company defaults to "-" | 1. Select "Fresh Graduate" for work_experience<br>2. Submit | "Last Company" column = "-" | | |
| REG-008 | Recruitment ID generated | 1. Submit valid form | Unique Recruitment ID (e.g., RCR-00001) generated and stored | | |
| REG-009 | Created Date stored as GMT+7 | 1. Submit form<br>2. Check raw_kandidat | "Created Date" in "yyyy-MM-dd HH:mm:ss" GMT+7 format | | |
| REG-010 | NIK stored with leading apostrophe (text) | 1. Submit form with NIK starting with 0<br>2. Check sheet | NIK preserved as text, leading zeros intact | | |
| REG-011 | Phone stored with leading apostrophe (text) | 1. Submit form<br>2. Check sheet | Phone stored as text string | | |
| REG-012 | Audit log entry on registration | 1. Submit form<br>2. Check Audit_Log sheet | Entry with action "Created", old="-", new="Pending" | | |
| REG-013 | Form validation: age must be ≥17 | 1. Enter age as 16<br>2. Submit | Returns validation error: "Usia minimal 17 tahun" | | |
| REG-014 | Form validation: gender required | 1. Leave gender empty<br>2. Submit | Returns validation error for gender | | |
| REG-015 | Form validation: position_applied required | 1. Leave position empty<br>2. Submit | Returns validation error for position | | |

---

## MODULE 4: RECRUITMENT LIST & DASHBOARD (backend/Recruitment.gs + views/Dashboard.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| DASH-001 | Get recruitment list: populated sheet | 1. Call getRecruitmentList() | Returns array of candidate objects sorted by createdDate descending | | |
| DASH-002 | Get recruitment list: empty sheet | 1. Call getRecruitmentList() with empty sheet | Returns empty array [] | | |
| DASH-003 | Get recruitment list: all fields mapped | 1. Call getRecruitmentList() | Each object contains all 28+ fields (recruitmentId, fullName, nik, etc.) | | |
| DASH-004 | Get recruitment list: dates formatted dd/MM/yyyy | 1. Check createdDate, birthDate fields | Dates formatted as dd/MM/yyyy HH:mm or dd/MM/yyyy | | |
| DASH-005 | Get recruitment list: expectedSalary as number | 1. Check expectedSalary field | Returns as Number type, not string | | |
| DASH-006 | Dashboard charts render | 1. Open Dashboard page<br>2. Verify chart elements | Pie charts for Status, bar charts for Position/Source visible | | |
| DASH-007 | Dashboard filters: by status | 1. Select status filter<br>2. Apply | Table shows only candidates matching selected status | | |
| DASH-008 | Dashboard filters: by position | 1. Select position filter<br>2. Apply | Table shows only matching position candidates | | |
| DASH-009 | Dashboard filters: by search text | 1. Type in search box | Table filters candidates by name/email/phone | | |
| DASH-010 | Dashboard: candidate drawer/detail panel | 1. Click on candidate row | Detail panel/drawer opens with all candidate info | | |
| DASH-011 | Dashboard: audit log for candidate | 1. Open candidate detail<br>2. View activity timeline | Shows all audit log entries for that candidate | | |

---

## MODULE 5: CANDIDATE STATUS MANAGEMENT (backend/Recruitment.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| STATUS-001 | Update status: Pending → Accepted | 1. Call updateCandidateStatus(id, 'Accepted', 'notes') | Status updated, audit log written, updatedAt touched | | |
| STATUS-002 | Update status: Pending → Hold | 1. Call updateCandidateStatus(id, 'Hold') | Status updated to Hold | | |
| STATUS-003 | Update status: Pending → Blacklist | 1. Call updateCandidateStatus(id, 'Blacklist') | Status updated to Blacklist | | |
| STATUS-004 | Update status: invalid status value | 1. Call updateCandidateStatus(id, 'InvalidStatus') | Returns error: "Status tidak valid" | | |
| STATUS-005 | Update status: non-existent ID | 1. Call updateCandidateStatus('XXX-99999', 'Accepted') | Returns error: "Recruitment ID tidak ditemukan" | | |
| STATUS-006 | Update status: HR notes updated | 1. Call updateCandidateStatus(id, 'Accepted', 'Hire!') | "HR Notes" column updated | | |
| STATUS-007 | Hold candidate with reason | 1. Call holdCandidate(id, 'Background check', '2026-09-01', 'notes') | Status=Hold, Hold Reason, Hold Follow Up Date set; audit log with reason | | |
| STATUS-008 | Hold candidate: reason optional | 1. Call holdCandidate(id, '', '', '') | Status=Hold, reason field empty | | |
| STATUS-009 | Blacklist candidate with reason | 1. Call blacklistCandidate(id, 'Fake documents', 'notes') | Status=Blacklist, Blacklist Reason, Date, UpdatedBy all set | | |
| STATUS-010 | Blacklist: Blacklist Updated By = current user | 1. Blacklist candidate<br>2. Check Blacklist Updated By | Shows Session.getActiveUser().getEmail() | | |
| STATUS-011 | Audit log on status change | 1. Change status from Pending to Accepted<br>2. Check Audit_Log | Action="Update Status", oldValue="Pending", newValue="Accepted" | | |
| STATUS-012 | Updated At timestamp touched | 1. Perform any status update<br>2. Check Updated At column | Updated At set to current GMT+7 timestamp | | |
| STATUS-013 | LockService concurrency protection | 1. Trigger concurrent updates (simulated) | Lock acquired, only one operation succeeds, no data corruption | | |

---

## MODULE 6: ACCEPT CANDIDATE TO EMPLOYEE (backend/Recruitment.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| ACCEPT-001 | Accept candidate: creates employee record | 1. Call acceptCandidateToEmployee(id, 'notes')<br>2. Check Employee sheet | New Employee row created with Employee ID, data copied from candidate | | |
| ACCEPT-002 | Accept candidate: generates Employee ID | 1. Accept candidate | Employee ID generated (EMP-XXXXX format) | | |
| ACCEPT-003 | Accept candidate: sets Status=Accepted | 1. Call acceptCandidateToEmployee(id) | Status updated to "Accepted" in raw_kandidat | | |
| ACCEPT-004 | Accept candidate: Employee ID stored back | 1. Accept candidate<br>2. Check raw_kandidat | Employee ID written to "Employee ID" column in candidate row | | |
| ACCEPT-005 | Accept candidate: re-accept uses existing Employee ID | 1. Accept candidate (gets EMP-00001)<br>2. Accept same candidate again | Reuses EMP-00001, does NOT create duplicate employee | | |
| ACCEPT-006 | Accept candidate: employee default values | 1. Accept candidate<br>2. Check Employee sheet | Company="PT Mahakarya Sukses Indonesia", Type="PKWTT", Status="Active", Salary Type="Monthly" | | |
| ACCEPT-007 | Accept candidate: audit log entry | 1. Accept candidate<br>2. Check Audit_Log | Action="Accepted", newValue="Accepted -> Employee EMP-XXXXX" | | |
| ACCEPT-008 | Accept candidate: non-existent ID | 1. Call acceptCandidateToEmployee('FAKE-ID') | Returns error: "Recruitment ID tidak ditemukan" | | |

---

## MODULE 7: HR NOTES (backend/Recruitment.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| NOTES-001 | Save HR notes | 1. Call saveHrNotes(id, 'Interview scheduled') | Notes saved, updatedAt updated; returns {success, updatedAt} | | |
| NOTES-002 | Save HR notes: empty notes | 1. Call saveHrNotes(id, '') | Notes cleared, success=true | | |
| NOTES-003 | Save HR notes: status NOT changed | 1. Call saveHrNotes(id, 'notes')<br>2. Check status | Status remains unchanged (still "Pending") | | |
| NOTES-004 | Save HR notes: non-existent ID | 1. Call saveHrNotes('XXX-99999', 'notes') | Returns error: "Recruitment ID tidak ditemukan" | | |
| NOTES-005 | Auto-save notes (no status change) | 1. Save notes via dashboard drawer | Notes saved without triggering status audit log | | |

---

## MODULE 8: CANDIDATE DELETION (backend/Recruitment.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| DEL-001 | Delete candidate: valid ID | 1. Call deleteCandidate(id) | Row removed from raw_kandidat; audit log written | | |
| DEL-002 | Delete candidate: non-existent ID | 1. Call deleteCandidate('XXX-99999') | Returns error: "Recruitment ID tidak ditemukan" | | |
| DEL-003 | Delete candidate: audit log written | 1. Delete candidate<br>2. Check Audit_Log | Action="Deleted", oldValue="-", newValue="Deleted" | | |
| DEL-004 | Delete candidate: sheet lock released | 1. Delete candidate<br>2. Attempt another operation | No lock contention; subsequent operations succeed | | |

---

## MODULE 9: BULK ACTIONS (backend/BulkActions.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| BULK-001 | Bulk update status: multiple valid IDs | 1. Call bulkUpdateStatus([id1, id2], 'Accepted') | Both updated; returns {updated: 2, failed: 0} | | |
| BULK-002 | Bulk update status: mix of valid/invalid | 1. Call bulkUpdateStatus([id1, 'FAKE'], 'Hold') | Returns {updated: 1, failed: 1, failedIds: ['FAKE']} | | |
| BULK-003 | Bulk update status: invalid status | 1. Call bulkUpdateStatus([id1], 'Invalid') | Returns error: "Status tidak valid" | | |
| BULK-004 | Bulk update status: empty array | 1. Call bulkUpdateStatus([], 'Accepted') | Returns error: "Tidak ada kandidat yang dipilih" | | |
| BULK-005 | Bulk update status: null input | 1. Call bulkUpdateStatus(null, 'Accepted') | Returns error: "Tidak ada kandidat yang dipilih" | | |
| BULK-006 | Bulk update status: audit log for each | 1. Bulk update 3 candidates<br>2. Check Audit_Log | 3 entries with action="Bulk Update Status" | | |
| BULK-007 | Bulk delete: multiple valid IDs | 1. Call bulkDeleteCandidates([id1, id2]) | Both rows deleted; returns {deleted: 2, failed: 0} | | |
| BULK-008 | Bulk delete: mix of valid/invalid | 1. Call bulkDeleteCandidates([id1, 'FAKE']) | Returns {deleted: 1, failed: 1} | | |
| BULK-009 | Bulk delete: empty array | 1. Call bulkDeleteCandidates([]) | Returns error: "Tidak ada kandidat yang dipilih" | | |
| BULK-010 | Bulk delete: rows deleted bottom-to-top | 1. Delete multiple rows<br>2. Check remaining IDs | Row order preserved; no index shifting errors | | |
| BULK-011 | Bulk delete: audit log for each | 1. Bulk delete 2 candidates<br>2. Check Audit_Log | 2 entries with action="Bulk Deleted" | | |
| BULK-012 | Bulk lock: 30s timeout protection | 1. Simulate heavy load | Lock acquired with 30s timeout; fails gracefully on timeout | | |

---

## MODULE 10: CSV IMPORT (backend/Import.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| IMP-001 | Import valid CSV data (multiple rows) | 1. Call importCandidates([{fullName, phone, ...}]) | Returns {imported: N, errors: []}; rows added to sheet | | |
| IMP-002 | Import: empty array | 1. Call importCandidates([]) | Returns error: "Tidak ada data untuk diimport" | | |
| IMP-003 | Import: null input | 1. Call importCandidates(null) | Returns error: "Tidak ada data untuk diimport" | | |
| IMP-004 | Import: missing fullName | 1. Import row without fullName | Returns error: "Baris 1: Nama lengkap wajib diisi" | | |
| IMP-005 | Import: missing phone | 1. Import row without phone | Returns error: "Baris 1: Nomor telepon wajib diisi" | | |
| IMP-006 | Import: duplicate Recruitment ID handling | 1. Import rows with potential ID collision | Warning generated; new unique ID created | | |
| IMP-007 | Import: batch write performance | 1. Import 100 rows | All 100 rows written in single batch operation | | |
| IMP-008 | Import: audit log for each row | 1. Import 5 rows<br>2. Check Audit_Log | 5 entries with action="Import" | | |
| IMP-009 | Import: Recruitment Source defaults to "CSV Import" | 1. Import row without source | recruitmentSource = "CSV Import" | | |
| IMP-010 | Import: Status defaults to "Pending" | 1. Import row | Status column = "Pending" | | |
| IMP-011 | Validate import rows: valid data | 1. Call validateImportRows([{valid data}]) | Returns {valid: 1, invalid: 0, details: [...]} | | |
| IMP-012 | Validate import rows: invalid email | 1. Call validateImportRows with invalid email | Returns row error: "Format email tidak valid" | | |
| IMP-013 | Validate import rows: age out of range | 1. Call validateImportRows with age=5 | Returns error: "Usia harus antara 15-100" | | |
| IMP-014 | Import: lock timeout protection | 1. Simulate concurrent imports | Returns error: "Server sedang sibuk" on timeout | | |

---

## MODULE 11: EMPLOYEE MANAGEMENT (backend/Employee.gs + views/Employee.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| EMP-001 | Get employee list: populated sheet | 1. Call getEmployeeList() | Returns {success, data: [...], total} with all employees | | |
| EMP-002 | Get employee list: empty sheet | 1. Call getEmployeeList() on empty Employee sheet | Returns {success: true, data: [], total: 0} | | |
| EMP-003 | Get employee list: filter by status | 1. Call getEmployeeList({status: 'Active'}) | Only Active employees returned | | |
| EMP-004 | Get employee list: filter by department | 1. Call getEmployeeList({department: 'IT'}) | Only IT employees returned | | |
| EMP-005 | Get employee list: search by name | 1. Call getEmployeeList({search: 'John'}) | Only employees matching "John" in name/nik/email | | |
| EMP-006 | Get employee by ID: valid | 1. Call getEmployeeById('EMP-00001') | Returns {success, data: {...full employee object}} | | |
| EMP-007 | Get employee by ID: not found | 1. Call getEmployeeById('EMP-99999') | Returns error: "Data tidak ditemukan" | | |
| EMP-008 | Get employee by ID: empty ID | 1. Call getEmployeeById('') | Returns error: "ID harus diisi" | | |
| EMP-009 | Add employee: valid data | 1. Call addEmployee({fullName: 'Test', ...}) | Returns {success, id: 'EMP-XXXXX'}; row added | | |
| EMP-010 | Add employee: missing fullName | 1. Call addEmployee({}) | Returns error: "Nama lengkap harus diisi" | | |
| EMP-011 | Add employee: generates unique ID | 1. Add 2 employees<br>2. Compare IDs | Both have unique EMP-XXXXX IDs | | |
| EMP-012 | Add employee: default status=Active | 1. Call addEmployee without status | Status = "Active" | | |
| EMP-013 | Update employee: valid fields | 1. Call updateEmployee(id, {department: 'HR'}) | Department updated; "Diubah" timestamp set | | |
| EMP-014 | Update employee: non-existent ID | 1. Call updateEmployee('EMP-99999', {department: 'HR'}) | Returns error: "Karyawan tidak ditemukan" | | |
| EMP-015 | Update employee: null data | 1. Call updateEmployee(null, null) | Returns error: "ID dan data harus diisi" | | |
| EMP-016 | Delete employee: valid | 1. Call deleteEmployee(id) | Employee row removed; returns success message with name | | |
| EMP-017 | Delete employee: non-existent | 1. Call deleteEmployee('EMP-99999') | Returns error: "Karyawan tidak ditemukan" | | |
| EMP-018 | Employee stats: correct counts | 1. Call getEmployeeStats() | Returns {total, active, inactive, byDepartment, byType, byLocation} | | |
| EMP-019 | Employee stats: empty list | 1. Call getEmployeeStats() on empty sheet | Returns total=0, active=0, inactive=0 | | |

---

## MODULE 12: OUTSOURCE REGISTRATION (backend/Outsource.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| OS-001 | Submit valid outsource form | 1. Call simpanDataOutsource({valid data}) | Returns "Sukses"; row added to Employee sheet | | |
| OS-002 | OS form: missing full_name | 1. Submit without full_name | Returns validation error: "Nama lengkap wajib diisi" | | |
| OS-003 | OS form: invalid NIK (not 16 digits) | 1. Submit NIK="12345" | Returns error: "NIK harus tepat 16 digit angka" | | |
| OS-004 | OS form: invalid phone format | 1. Submit phone="0812345678" | Returns error: "Nomor HP tidak valid" (must start with 62) | | |
| OS-005 | OS form: age < 17 rejected | 1. Submit age=16 | Returns error: "Usia minimal 17 tahun" | | |
| OS-006 | OS form: email validation | 1. Submit invalid email | Returns error: "Format email tidak valid" | | |
| OS-007 | OS form: outsource vendor required for Outsource type | 1. Submit with employee_type="Outsource" and empty vendor | Returns error: "Nama outsource vendor wajib diisi" | | |
| OS-008 | OS form: contract duration required for Outsource/PKWT | 1. Submit with employee_type="PKWT" and no duration | Returns error: "Durasi kontrak wajib dipilih" | | |
| OS-009 | OS form: salary must be numeric | 1. Submit salary="abc" | Returns error: "Gaji wajib diisi dengan angka" | | |
| OS-010 | OS: Employee ID generated | 1. Submit valid form | Employee ID (EMP-XXXXX) generated and stored | | |
| OS-011 | OS: Audit log written | 1. Submit valid form<br>2. Check Audit_Log | Entry with action="Employee Registered (OS)" | | |
| OS-012 | OS: salary stored as number | 1. Submit salary="5000000"<br>2. Check Employee sheet | Stored as number 5000000, not string | | |
| OS-013 | OS: phone stored with apostrophe (text) | 1. Submit phone="628123456789" | Stored as text with leading apostrophe | | |
| OS-014 | getOutsourceList: returns employee data | 1. Call getOutsourceList() | Returns array of employee objects with all fields | | |

---

## MODULE 13: MASTER DATA MANAGEMENT (backend/MasterData.gs + views/MasterData.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| MD-001 | Get master data: all categories | 1. Call getMasterDataList() | Returns {success, data: [...], categories: {...}} | | |
| MD-002 | Get master data: by category | 1. Call getMasterDataByCategory('department') | Returns only department items, sorted by order | | |
| MD-003 | Get master data: names only | 1. Call getMasterDataNamesByCategory('position') | Returns {success, data: ['Staff', 'Supervisor', ...]} | | |
| MD-004 | Get master data: summary | 1. Call getMasterDataSummary() | Returns summary object with count per category | | |
| MD-005 | Add master data item: valid | 1. Call addMasterDataItem('department', 'Legal') | Returns success; row added with auto-incremented order | | |
| MD-006 | Add master data item: duplicate name | 1. Add item with existing name in same category | Returns error: "Item sudah ada dalam kategori ini" | | |
| MD-007 | Add master data item: missing fields | 1. Call addMasterDataItem(null, null) | Returns error: "Kategori dan nama harus diisi" | | |
| MD-008 | Add master data item: invalid category | 1. Call addMasterDataItem('invalid', 'Test') | Returns error: "Kategori tidak valid" | | |
| MD-009 | Update master data item: name change | 1. Call updateMasterDataItem(id, {name: 'New Name'}) | Name updated, "Diubah" timestamp set | | |
| MD-010 | Update master data item: toggle active | 1. Call updateMasterDataItem(id, {active: false}) | "Aktif" set to FALSE | | |
| MD-011 | Update master data item: duplicate check | 1. Rename to existing name in same category | Returns error: duplicate name | | |
| MD-012 | Delete master data item: soft delete | 1. Call deleteMasterDataItem(id) | "Aktif" set to FALSE (not physically deleted) | | |
| MD-013 | Delete master data item: non-existent | 1. Call deleteMasterDataItem('MD-9999') | Returns error: "Item tidak ditemukan" | | |
| MD-014 | Reorder items | 1. Call reorderMasterDataItems([id3, id1, id2]) | "Urutan" column updated to new order | | |
| MD-015 | Default data initialized | 1. Check master_data sheet on first creation | Contains default items for all 6 categories | | |
| MD-016 | Only active items returned | 1. Soft delete an item<br>2. Call getMasterDataList() | Deactivated item NOT in results | | |

---

## MODULE 14: PORTAL SETTINGS (backend/PortalSettings.gs + views/Settings.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| PORTAL-001 | Get portal settings | 1. Call getPortalSettings() | Returns settings object with companyName, logo, tagline, etc. | | |
| PORTAL-002 | Save portal settings | 1. Call savePortalSettings({companyName: 'Test'}) | Settings saved to Properties Service | | |
| PORTAL-003 | Settings persistence | 1. Save settings<br>2. Call getPortalSettings() | Returned values match saved values | | |
| PORTAL-004 | Login page branding | 1. Call getPortalSettingsForLogin() | Returns only branding fields (companyName, logo, tagline) | | |

---

## MODULE 15: AUDIT LOG (backend/Audit.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| AUD-001 | Write audit log entry | 1. Trigger any status change | Row appended to Audit_Log with timestamp, user, action, old, new | | |
| AUD-002 | Audit log: sheet created automatically | 1. Delete Audit_Log sheet<br>2. Trigger action | Sheet recreated with headers and styling | | |
| AUD-003 | Audit log: timestamp in GMT+7 | 1. Check any audit entry | Timestamp in "yyyy-MM-dd HH:mm:ss" GMT+7 | | |
| AUD-004 | Audit log: user captured | 1. Perform action as logged-in user | User email shown in "User" column | | |
| AUD-005 | Get audit log for candidate: valid ID | 1. Call getAuditLogForCandidate(id) | Returns array of log entries sorted chronologically | | |
| AUD-006 | Get audit log: non-existent ID | 1. Call getAuditLogForCandidate('XXX-99999') | Returns empty array [] | | |
| AUD-007 | Get audit log: empty sheet | 1. Call getAuditLogForCandidate(id) on empty Audit_Log | Returns empty array [] | | |
| AUD-008 | Audit log captures all action types | 1. Create, Update Status, Hold, Blacklist, Accept, Delete candidate | Each action has corresponding audit entry with correct action string | | |

---

## MODULE 16: DATA INTEGRITY & SHEETS (backend/Config.gs, backend/Sheets.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| SHEET-001 | raw_kandidat sheet has correct headers | 1. Check sheet headers | 25 core headers match SHEET_HEADERS array in Config.gs | | |
| SHEET-002 | Extra headers appended correctly | 1. Check sheet headers | Hold Reason, Hold Follow Up Date, Blacklist Reason, etc. present at end | | |
| SHEET-003 | Employee sheet has correct headers | 1. Check Employee sheet | Headers match EMPLOYEE_HEADERS from Config.gs | | |
| SHEET-004 | Audit_Log sheet has correct headers | 1. Check Audit_Log sheet | Headers: Timestamp, User, Recruitment ID, Action, Old Value, New Value | | |
| SHEET-005 | Users sheet has correct headers | 1. Check Users sheet | Headers: Email, Full Name, Role, Status, Last Login, Created At, Updated At, Created By | | |
| SHEET-006 | Header styling: blue background, white text | 1. Check any sheet header row | Background #005BAC, font white, bold | | |
| SHEET-007 | First row frozen | 1. Check all sheets | First row frozen on raw_kandidat, Employee, Users, Audit_Log | | |
| SHEET-008 | NIK stored as text (leading zeros preserved) | 1. Save NIK "0012345678901234"<br>2. Read back | Full 16 digits preserved with leading zeros | | |
| SHEET-009 | Phone stored as text | 1. Save phone "6281234567890"<br>2. Read back | Full number preserved as string | | |
| SHEET-010 | Dates stored as formatted strings | 1. Check date columns | Format: "yyyy-MM-dd HH:mm:ss" in GMT+7 | | |
| SHEET-011 | Numbers stored as numbers | 1. Check age, salary columns | Stored as Number type, not String | | |

---

## MODULE 17: UI — DASHBOARD PAGE (views/Dashboard.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| UI-DASH-001 | Dashboard loads after login | 1. Login<br>2. Navigate to dashboard | Page loads with sidebar, topbar, main content | | |
| UI-DASH-002 | Sidebar navigation visible | 1. Check sidebar | Links to Dashboard, Kandidat, Karyawan, Master Data, User Management, Settings | | |
| UI-DASH-003 | Topbar shows user info | 1. Check topbar | Shows user name, email, photo, role | | |
| UI-DASH-004 | Stats cards render | 1. Check dashboard | Total candidates, by status counts displayed | | |
| UI-DASH-005 | Charts render | 1. Check dashboard | Status pie chart, position bar chart, source chart visible | | |
| UI-DASH-006 | Candidate table loads | 1. Check table | Table with columns: ID, Name, Position, Status, etc. | | |
| UI-DASH-007 | Candidate table: pagination | 1. Have > page_size candidates<br>2. Navigate pages | Pagination controls work correctly | | |
| UI-DASH-008 | Candidate table: sort by column | 1. Click column header | Table sorts by that column | | |
| UI-DASH-009 | Candidate table: search filter | 1. Type in search input | Table filters in real-time | | |
| UI-DASH-010 | Candidate detail drawer opens | 1. Click candidate row | Side panel opens with all candidate details | | |
| UI-DASH-011 | Status change from drawer | 1. Open drawer<br>2. Change status | Status updated, table refreshes | | |
| UI-DASH-012 | Audit timeline in drawer | 1. Open candidate detail | Activity timeline shows audit entries | | |
| UI-DASH-013 | Mobile responsive | 1. Resize browser to mobile<br>2. Check layout | Sidebar collapses, table scrolls horizontally | | |

---

## MODULE 18: UI — CANDIDATE REGISTRATION PAGE (FormPendaftaran.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| UI-REG-001 | Registration form loads | 1. Navigate to registration URL | Form displays with all fields | | |
| UI-REG-002 | Form fields: correct input types | 1. Check each field | Text fields, selects, date pickers, radio buttons render correctly | | |
| UI-REG-003 | Form validation: required fields | 1. Submit empty form | Error messages shown for required fields | | |
| UI-REG-004 | Form validation: NIK 16 digits | 1. Enter short NIK<br>2. Submit | Validation error for NIK | | |
| UI-REG-005 | Form validation: phone format | 1. Enter invalid phone<br>2. Submit | Validation error for phone | | |
| UI-REG-006 | Form submission: success message | 1. Submit valid form | Success message displayed; form resets | | |
| UI-REG-007 | Form submission: error message | 1. Submit with server error | Error message displayed | | |
| UI-REG-008 | Bootstrap 5 styling | 1. Check page | Bootstrap 5.3 classes used; correct colors (#005BAC primary) | | |
| UI-REG-009 | Mobile responsive | 1. Check on mobile viewport | Form stacks vertically, readable on small screens | | |
| UI-REG-010 | Dropdown options loaded from master data | 1. Check position, source dropdowns | Options match master_data entries | | |

---

## MODULE 19: UI — EMPLOYEE PAGE (views/Employee.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| UI-EMP-001 | Employee list loads | 1. Navigate to Employee page | Table with employee data displayed | | |
| UI-EMP-002 | Employee stats cards | 1. Check page header | Total, Active, Inactive counts shown | | |
| UI-EMP-003 | Add employee modal | 1. Click "Add" button | Modal opens with employee form | | |
| UI-EMP-004 | Edit employee modal | 1. Click edit on row | Modal opens with pre-filled data | | |
| UI-EMP-005 | Delete employee confirmation | 1. Click delete on row | Confirmation dialog appears | | |
| UI-EMP-006 | Employee search/filter | 1. Type in search box | Table filters by name/nik/email | | |
| UI-EMP-007 | Employee type filter | 1. Select employee type dropdown | Table filters accordingly | | |

---

## MODULE 20: UI — MASTER DATA PAGE (views/MasterData.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| UI-MD-001 | Master data page loads | 1. Navigate to Master Data page | Category tabs/panels displayed (6 categories) | | |
| UI-MD-002 | Category count badges | 1. Check each category | Correct item count shown | | |
| UI-MD-003 | Add item form | 1. Click "Add" in a category | Form with name, description fields | | |
| UI-MD-004 | Edit item | 1. Click edit on item | Inline edit or modal opens | | |
| UI-MD-005 | Deactivate item | 1. Toggle active switch | Item deactivated (soft delete) | | |
| UI-MD-006 | Duplicate prevention | 1. Add item with existing name | Error message shown | | |

---

## MODULE 21: UI — USER MANAGEMENT PAGE (views/UserManagement.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| UI-UM-001 | User list loads | 1. Navigate to User Management (Super Admin only) | Table with all users displayed | | |
| UI-UM-002 | Add user modal | 1. Click "Add User" | Modal with email, name, role fields | | |
| UI-UM-003 | Edit user role | 1. Click edit on user | Role dropdown editable | | |
| UI-UM-004 | Deactivate user | 1. Set status to Inactive | User blocked from login | | |
| UI-UM-005 | Delete user confirmation | 1. Click delete | Confirmation dialog; cannot delete self | | |
| UI-UM-006 | Role display badges | 1. Check table | Roles shown with appropriate color badges | | |

---

## MODULE 22: UI — LOGIN PAGE (views/Login.html)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| UI-LOGIN-001 | Login page loads | 1. Navigate without session | Login page displayed with Google SSO | | |
| UI-LOGIN-002 | First-run setup shown | 1. Access with empty Users sheet | Setup form for Super Admin displayed | | |
| UI-LOGIN-003 | Portal branding | 1. Check login page | Company name, logo, tagline from PortalSettings shown | | |
| UI-LOGIN-004 | Access Denied page | 1. Navigate with inactive user account | "Access Denied" page with reason | | |

---

## MODULE 23: CONFIGURATION & CONSTANTS (backend/Config.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| CFG-001 | SHEET_NAME constant | 1. Check Config.gs | SHEET_NAME = 'raw_kandidat' | | |
| CFG-002 | SHEET_HEADERS order preserved | 1. Count SHEET_HEADERS array | 25 core headers in defined order | | |
| CFG-003 | EXTRA_HEADERS not breaking | 1. Check ensureExtraHeaders_() adds columns | Extra columns appended at end without affecting core data | | |
| CFG-004 | EMPLOYEE_HEADERS count | 1. Count EMPLOYEE_HEADERS | 31 columns defined | | |
| CFG-005 | STATUS_COLUMN_NAME correct | 1. Check constant | STATUS_COLUMN_NAME = 'Status' | | |

---

## MODULE 24: SETTINGS & KECAMATAN (backend/Settings.gs)

| Test ID | Scenario | Steps | Expected Result | Actual Result | Status |
|---------|----------|-------|-----------------|---------------|--------|
| SET-001 | getKecamatan: valid city code | 1. Call getKecamatan('3171') | Returns array of {id, nama} kecamatan | | |
| SET-002 | getKecamatan: invalid/short code | 1. Call getKecamatan('12') | Returns empty array [] | | |
| SET-003 | getKecamatan: cache hit | 1. Call getKecamatan twice with same code | Second call returns cached result (no HTTP call) | | |
| SET-004 | getKecamatan: HTTP error handling | 1. Call with invalid code that returns 404 | Returns empty array, no crash | | |
| SET-005 | Cache TTL = 6 hours | 1. Check cache.put expiry | Cached for 21600 seconds (6 hours) | | |

---

## 🚨 RISKY AREAS REQUIRING ADDITIONAL MANUAL TESTING

### HIGH RISK

| Risk ID | Area | Risk Description | Test Recommendation |
|---------|------|------------------|---------------------|
| RISK-001 | **LockService Concurrency** | Multiple users editing same candidate simultaneously may cause data corruption | Open 2 browser tabs, edit same candidate status at the same time; verify no data corruption |
| RISK-002 | **Accept → Employee Data Transfer** | Data copying from raw_kandidat to Employee sheet with 31 columns — field mapping errors possible | Accept a candidate, then verify EVERY field in Employee sheet matches source data |
| RISK-003 | **NIK/Phone as Text** | Leading apostrophe storage for NIK/Phone may fail if Apps Script auto-formats | Submit NIK "0012345678901234", verify all 16 digits preserved with leading zeros |
| RISK-004 | **Bulk Operations** | Bulk delete/update with many rows may timeout (30s lock timeout) | Test with 50+ candidates in bulk operations; verify no partial failures |
| RISK-005 | **Outsource Form Field Mapping** | Outsource form writes to Employee sheet with different column order than acceptance flow | Register outsource employee, verify each column matches EMPLOYEE_HEADERS from Config.gs |

### MEDIUM RISK

| Risk ID | Area | Risk Description | Test Recommendation |
|---------|------|------------------|---------------------|
| RISK-006 | **ID Generation Collision** | Properties counter may reset if Properties Service is cleared | Generate 100+ IDs sequentially; verify no duplicates |
| RISK-007 | **CSV Import Large Files** | Importing 100+ rows may hit Apps Script execution time limit | Import 200 rows, verify all succeed or graceful error |
| RISK-008 | **Master Data Cascading** | Changing master data may affect dropdown options used in forms | Delete a department from master data; verify forms no longer show it |
| RISK-009 | **Audit Log Growth** | Audit_Log sheet may grow very large over time; performance degradation | Create 1000+ audit entries; verify getAuditLogForCandidate still performs |
| RISK-010 | **Date Formatting Across Timezones** | GMT+7 formatting may break if server timezone changes | Verify dates display correctly in both GMT+7 and UTC environments |
| RISK-011 | **User Session After Role Change** | User role changed while logged in — permission cache may be stale | Change user role to Viewer while logged in as HR Admin; verify permissions update on next action |

### LOW RISK

| Risk ID | Area | Risk Description | Test Recommendation |
|---------|------|------------------|---------------------|
| RISK-012 | **Mobile Responsive** | Complex tables/charts may not render well on small screens | Test Dashboard, Employee, Master Data pages on iPhone SE viewport |
| RISK-013 | **External API (Kecamatan)** | GitHub raw URL for kecamatan data may be unavailable | Test with network disabled; verify graceful fallback to empty array |
| RISK-014 | **Sheet Auto-Creation** | All sheets created on first access — race condition if two users trigger simultaneously | Clear all sheets, have two users access dashboard at same time |
| RISK-015 | **Empty State Handling** | All pages should handle zero-data gracefully | Test every page with empty sheets; verify no crashes or JS errors |

---

## EXECUTION SUMMARY

| Module | Total Tests | Passed | Failed | Blocked | Notes |
|--------|-------------|--------|--------|---------|-------|
| 1. Auth | 13 | | | | |
| 2. User Management | 17 | | | | |
| 3. Candidate Registration | 15 | | | | |
| 4. Recruitment List | 11 | | | | |
| 5. Status Management | 13 | | | | |
| 6. Accept to Employee | 8 | | | | |
| 7. HR Notes | 5 | | | | |
| 8. Candidate Deletion | 4 | | | | |
| 9. Bulk Actions | 12 | | | | |
| 10. CSV Import | 14 | | | | |
| 11. Employee Management | 19 | | | | |
| 12. Outsource Registration | 14 | | | | |
| 13. Master Data | 16 | | | | |
| 14. Portal Settings | 4 | | | | |
| 15. Audit Log | 8 | | | | |
| 16. Data Integrity | 11 | | | | |
| 17. UI Dashboard | 13 | | | | |
| 18. UI Registration | 10 | | | | |
| 19. UI Employee | 7 | | | | |
| 20. UI Master Data | 6 | | | | |
| 21. UI User Management | 6 | | | | |
| 22. UI Login | 4 | | | | |
| 23. Config | 5 | | | | |
| 24. Settings | 5 | | | | |
| **TOTAL** | **240** | | | | |

**Risk Items**: 15 (5 High, 6 Medium, 4 Low)

---

*Generated by QA Analysis — 2026-08-03*