# Implementation Plan — Fix Audit_Log Sheet Headers & Column Data

## [Overview]

Fix the Audit_Log sheet in the HRIS Google Apps Script project so that both the header row and the data rows are correctly aligned. The root cause is that the dummy data generator (`GenerateDummyData.gs`) writes rows in the wrong column order (`recId, action, field, oldValue, newValue, user, timestamp`), and the backend write function (`Audit.gs`) uses only 6 local headers instead of the 7 defined in the `AUDIT_LOG_HEADERS` constant. This causes data to appear under wrong column headers in the spreadsheet.

## [Types]

No new types or interfaces are needed. The existing data structure is:

```
AUDIT_LOG_HEADERS (7 columns):
  [0] Timestamp    — When the audit entry was created (yyyy-MM-dd HH:mm:ss, GMT+7)
  [1] User         — Who performed the action (email or username string)
  [2] Recruitment ID — The recruitment ID being tracked (e.g., REC-20260601-000001)
  [3] Action       — The action type (CREATE, STATUS_CHANGE, HOLD, BLACKLIST, ACCEPT_TO_EMPLOYEE, HR_NOTES_UPDATE)
  [4] Old Value    — Previous value before the change (can be empty string)
  [5] New Value    — New value after the change
  [6] TimeStamp    — Secondary timestamp (same as Timestamp, kept for backward compatibility)
```

Audit log row data format (7 values, must match header order):

```
[timestamp, user, recruitmentId, action, oldValue, newValue, timestamp]
```

## [Files]

### Files to modify:

1. **`backend/Audit.gs`** — Fix `writeAuditLog_()` to use 7 columns matching `AUDIT_LOG_HEADERS` constant instead of 6 local headers. Fix `getAuditLogForCandidate_()` to read all 7 columns.
2. **`GenerateDummyData.gs`** — Fix `generateAuditLogs_()` to write rows in the correct column order: `[timestamp, user, recId, action, oldValue, newValue, timestamp]`.

### Files unchanged (already correct):

- `backend/Sheets.gs` — `AUDIT_LOG_HEADERS` constant is already correct (7 columns)
- `backend/Config.gs` — `AUDIT_LOG_HEADERS` constant is already correct (7 columns)
- `js/auditLog.html` — Frontend already handles dynamic column count

## [Functions]

### Modified Functions:

1. **`writeAuditLog_()` in `backend/Audit.gs`**
   - Current: Uses local `var headers = ['Timestamp', 'User', 'Recruitment ID', 'Action', 'Old Value', 'New Value'];` (6 items) and writes `[[now, user, recruitmentId, action, oldValue, newValue]]` (6 values)
   - Fix: Use `AUDIT_LOG_HEADERS` constant (7 items) and write `[[now, user, recruitmentId, action, oldValue, newValue, now]]` (7 values)

2. **`getAuditLogForCandidate_()` in `backend/Audit.gs`**
   - Current: Maps 6 columns: `return { timestamp: row[0], user: row[1], recruitmentId: row[2], action: row[3], oldValue: row[4], newValue: row[5] };`
   - Fix: Map 7 columns: `return { timestamp: row[0], user: row[1], recruitmentId: row[2], action: row[3], oldValue: row[4], newValue: row[5], timeStamp: row[6] };`

3. **`generateAuditLogs_()` in `GenerateDummyData.gs`**
   - Current: Writes rows in order `[recId, action, field, oldValue, newValue, user, timestamp]` — all ~7 auditRows.push() calls have this wrong order
   - Fix: Reorder all push calls to `[timestamp, user, recId, action, oldValue, newValue, timestamp]`

## [Classes]

No class changes needed.

## [Dependencies]

No new dependencies. The fix uses existing constants from `backend/Sheets.gs` (or `backend/Config.gs`):

- `AUDIT_LOG_HEADERS` — already defined with correct 7-column layout

## [Testing]

- Run `generateAllHRISDemoData()` from Apps Script editor after clearing Audit_Log data
- Verify headers in sheet match: `Timestamp | User | Recruitment ID | Action | Old Value | New Value | TimeStamp`
- Verify data rows are correctly aligned (no shifted columns)
- Test `getAuditLogForCandidate('REC-20260601-000001')` returns all fields correctly
- Test `writeAuditLog_('CREATE', '', 'Accepted', 'test@test.com')` writes 7 values correctly
- Verify frontend audit log timeline in dashboard still renders correctly

## [Implementation Order]

1. Fix `writeAuditLog_()` in `backend/Audit.gs` — change local headers to use `AUDIT_LOG_HEADERS` constant, write 7 values instead of 6
2. Fix `getAuditLogForCandidate_()` in `backend/Audit.gs` — map 7 columns instead of 6
3. Fix all `auditRows.push()` calls in `generateAuditLogs_()` in `GenerateDummyData.gs` — reorder from `[recId, action, field, oldValue, newValue, user, timestamp]` to `[timestamp, user, recId, action, oldValue, newValue, timestamp]`
