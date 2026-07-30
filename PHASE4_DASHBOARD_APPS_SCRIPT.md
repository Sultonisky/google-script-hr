# Phase 4: HR Dashboard — Required Apps Script Functions

## Overview

The HR Dashboard requires the following Apps Script functions to be added to `Kode.gs`.
These functions do NOT modify existing candidate registration flow.

## Functions Already Available (No Changes Needed)

| Function | Status | Used By |
|----------|--------|---------|
| `getRecruitmentList()` | ✅ Exists | Dashboard.js — loads all candidates |
| `updateCandidateStatus(recruitmentId, newStatus, hrNotes)` | ⚠️ Needs update | Dashboard.js — changes status |

## Functions to Add to Kode.gs

### 1. Update `updateCandidateStatus` — Expand Allowed Statuses

The existing function only allows: `["Pending", "Accepted", "Hold", "Blacklist"]`

The dashboard uses these new statuses:
- Applied
- Screening
- HR Interview
- User Interview
- Offering
- Hired
- Rejected

**Required Change:** Add new statuses to the `allowedStatus` array.

```javascript
// UPDATED: Expand allowed statuses for dashboard
function updateCandidateStatus(recruitmentId, newStatus, hrNotes) {
  var allowedStatus = [
    "Pending", "Accepted", "Hold", "Blacklist",
    "Applied", "Screening", "HR Interview", "User Interview",
    "Offering", "Hired", "Rejected"
  ];
  if (allowedStatus.indexOf(newStatus) === -1) {
    return { success: false, message: "Status tidak valid: " + newStatus };
  }

  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      return { success: false, message: "Sheet data kandidat tidak ditemukan." };
    }

    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) {
      return { success: false, message: "Recruitment ID tidak ditemukan: " + recruitmentId };
    }

    var oldStatus = found.values[found.colIndex[STATUS_COLUMN_NAME]];

    setCell_(sheet, found, STATUS_COLUMN_NAME, newStatus);
    if (hrNotes !== undefined && hrNotes !== null) {
      setCell_(sheet, found, NOTES_COLUMN_NAME, hrNotes);
    }
    touchUpdatedAt_(sheet, found);

    writeAuditLog_(recruitmentId, "Update Status", oldStatus, newStatus);

    return { success: true, recruitmentId: recruitmentId, newStatus: newStatus };

  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
```

### 2. Add `deleteCandidate` — New Function

```javascript
// ============================================================
// DASHBOARD — DELETE CANDIDATE
// ============================================================
function deleteCandidate(recruitmentId) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);

  try {
    var sheet = getDashboardSheet_();
    if (!sheet || sheet.getLastRow() < 2) {
      return { success: false, message: "Sheet data kandidat tidak ditemukan." };
    }

    var found = findCandidateRow_(sheet, recruitmentId);
    if (!found) {
      return { success: false, message: "Recruitment ID tidak ditemukan: " + recruitmentId };
    }

    // Delete the row
    sheet.deleteRow(found.rowNumber);

    // Audit log
    writeAuditLog_(recruitmentId, "Deleted", "-", "Deleted");

    return { success: true, recruitmentId: recruitmentId };

  } catch (err) {
    return { success: false, message: err.message };
  } finally {
    lock.releaseLock();
  }
}
```

### 3. Add `getDashboardStats` — Optional (Dashboard.js calculates stats client-side)

```javascript
// ============================================================
// DASHBOARD — GET STATISTICS (optional, dashboard.js calculates client-side)
// ============================================================
function getDashboardStats() {
  var candidates = getRecruitmentList();
  
  var today = new Date().toDateString();
  var thisMonth = new Date();
  
  var stats = {
    total: candidates.length,
    today: 0,
    thisMonth: 0,
    applied: 0,
    interview: 0,
    hired: 0,
    rejected: 0
  };
  
  candidates.forEach(function(c) {
    // Today
    if (c.createdDate) {
      var parts = c.createdDate.split(' ');
      var dateParts = parts[0].split('/');
      if (dateParts.length === 3) {
        var d = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
        if (d.toDateString() === today) stats.today++;
        if (d.getMonth() === thisMonth.getMonth() && d.getFullYear() === thisMonth.getFullYear()) stats.thisMonth++;
      }
    }
    
    // Status counts
    if (c.status === 'Applied' || c.status === 'Pending') stats.applied++;
    if (c.status === 'HR Interview' || c.status === 'User Interview' || c.status === 'Screening') stats.interview++;
    if (c.status === 'Hired' || c.status === 'Accepted') stats.hired++;
    if (c.status === 'Rejected' || c.status === 'Blacklist') stats.rejected++;
  });
  
  return stats;
}
```

## How to Apply

1. Open `Kode.gs` in the Apps Script editor
2. Update the `allowedStatus` array in `updateCandidateStatus()` (add new statuses)
3. Add the `deleteCandidate()` function at the end of the file
4. Optionally add `getDashboardStats()` function
5. Save the project
6. The dashboard will now work with full functionality

## Files Created

| File | Purpose |
|------|---------|
| `Dashboard.html` | HR Dashboard page (sidebar, topbar, pages, modals) |
| `Dashboard.css` | Dashboard styling (corporate, responsive) |
| `Dashboard.js` | Dashboard logic (data loading, filtering, status updates, modals) |

## Files NOT Modified

| File | Status |
|------|--------|
| `FormPendaftaran.html` | ✅ Not modified |
| `Kode.gs` | ✅ Not modified (functions provided as documentation) |
| `data/master_wilayah.json` | ✅ Not modified |

## Breaking Changes: None

The dashboard uses existing `getRecruitmentList()` for data loading.
The `updateCandidateStatus()` expansion is backward-compatible (old statuses still work).
The `deleteCandidate()` function is new and does not affect existing flows.