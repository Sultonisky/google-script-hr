# DEPLOYMENT.md — Deployment Guide

Complete guide for deploying and managing the Mahakarya HRIS application.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [First-Time Setup](#first-time-setup)
3. [Deploying with Clasp](#deploying-with-clasp)
4. [Deployment Modes](#deployment-modes)
5. [Environment Configuration](#environment-configuration)
6. [Updating the Application](#updating-the-application)
7. [Rollback Procedure](#rollback-procedure)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before deploying, ensure you have:

- [ ] Google account with access to Google Apps Script
- [ ] Google Spreadsheet created (will be linked as database)
- [ ] Node.js 18+ installed (for clasp CLI)
- [ ] Clasp CLI installed (`npm install -g @google/clasp`)
- [ ] Git installed
- [ ] Access to the project repository

### Verify Clasp Installation

```bash
clasp --version
# Should output: 2.x.x
```

### Verify Authentication

```bash
clasp login --check
# Or login if not authenticated:
clasp login
```

---

## First-Time Setup

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd HRIS
```

### Step 2: Create Google Spreadsheet

1. Go to [Google Sheets](https://sheets.google.com)
2. Create a new spreadsheet
3. Copy the Spreadsheet ID from the URL:
   ```
   https://docs.google.com/spreadsheets/d/[SPREADSHEET_ID]/edit
   ```

### Step 3: Create Apps Script Project

Option A: Create new via clasp
```bash
clasp create --title "Mahakarya HRIS" --type sheets
# This creates .clasp.json with the script ID
```

Option B: Link existing project
```bash
# If you already have an Apps Script project:
# 1. Open the spreadsheet
# 2. Extensions > Apps Script
# 3. Copy the Script ID from the URL
# 4. Create .clasp.json manually:
```

Create `.clasp.json`:
```json
{
  "scriptId": "YOUR_SCRIPT_ID_HERE",
  "projectId": "YOUR_PROJECT_ID_HERE"
}
```

### Step 4: Link Spreadsheet to Script

1. Open the spreadsheet
2. Extensions > Apps Script
3. The script editor opens
4. This establishes the link between spreadsheet and script

### Step 5: Configure Project Properties

In the Apps Script editor:
1. Project Settings (gear icon)
2. Script Properties
3. Add the following properties:

| Property | Value |
|----------|-------|
| `APP_NAME` | Mahakarya HRIS |
| `VERSION` | 0.6.0 |
| `TIMEZONE` | Asia/Jakarta |

### Step 6: Deploy as Web App

1. In Apps Script editor, click **Deploy** > **New deployment**
2. Select type: **Web app**
3. Configure:
   - **Description**: `Mahakarya HRIS v0.6.0`
   - **Execute as**: `Me` (your account)
   - **Who has access**: `Anyone` (for public registration) or `Anyone with Google account` (for internal only)
4. Click **Deploy**
5. Copy the Web App URL

### Step 7: Update Deployment URL

Update `README.md` with the actual deployment URL.

---

## Deploying with Clasp

### Push Code to Apps Script

```bash
# Push all files
clasp push

# Push with version creation
clasp push --force
```

### Create a New Version

After pushing, create a versioned deployment:

```bash
# Create version via CLI
clasp version "v0.6.0 - Stabilization Release"

# Or create deployment directly
clasp deploy --description "v0.6.0 Production"
```

### List Existing Deployments

```bash
clasp deployments
```

### List Existing Versions

```bash
clasp versions
```

---

## Deployment Modes

### Development (Head)

- **URL**: `https://script.google.com/macros/s/[SCRIPT_ID]/dev`
- **Purpose**: Testing during development
- **Updates**: Instant (after `clasp push`)
- **Access**: Only the script owner

### Production (Versioned)

- **URL**: `https://script.google.com/macros/s/[SCRIPT_ID]/exec`
- **Purpose**: Live production use
- **Updates**: Requires new version + deployment
- **Access**: Configured during deployment

### Staging (Optional)

Create a separate deployment for testing:

```bash
clasp deploy --description "Staging - v0.6.0-rc1"
```

---

## Environment Configuration

### Google Apps Script Project Properties

Set these in Apps Script editor > Project Settings > Script Properties:

| Property | Description | Example |
|----------|-------------|---------|
| `APP_NAME` | Application name | `Mahakarya HRIS` |
| `VERSION` | Current version | `0.6.0` |
| `TIMEZONE` | Default timezone | `Asia/Jakarta` |
| `COUNTER_KEY` | Daily counter prefix | `REC` |
| `EMP_COUNTER_KEY` | Yearly employee counter | `EMP` |

### Spreadsheet Structure

The application expects these sheets (auto-created on first run):

| Sheet Name | Purpose | Columns |
|------------|---------|---------|
| `data_kandidat` | Candidate data | 31 columns |
| `Employee` | Employee master | 10 columns |
| `Audit_Log` | Activity tracking | 6 columns |
| `settings` | App settings | Key-Value pairs |
| `User` | User accounts | Email, Role, Name |
| `Ref_Education` | Education reference | Code, Label |
| `Ref_Profession` | Profession reference | Code, Label |
| `Ref_Source` | Source reference | Code, Label |
| `Ref_City` | City reference | Code, Label |
| `Ref_Status` | Status reference | Code, Label |
| `Ref_Position` | Position reference | Code, Label |
| `Ref_Division` | Division reference | Code, Label |
| `Ref_Religion` | Religion reference | Code, Label |
| `Ref_Marital` | Marital status ref | Code, Label |

### Master Data Seeding

On first deployment, master data is automatically seeded. To manually re-seed:

```javascript
// In Apps Script editor, run this function:
seedAllMasterData();
```

---

## Updating the Application

### Standard Update Process

1. **Pull latest changes**:
   ```bash
   git pull origin main
   ```

2. **Review changes**:
   ```bash
   git log --oneline -5
   git diff HEAD~1
   ```

3. **Push to Apps Script**:
   ```bash
   clasp push
   ```

4. **Test in development**:
   - Open the dev URL
   - Test all affected functionality
   - Check browser console for errors

5. **Create version** (if changes are good):
   ```bash
   clasp version "v0.6.1 - Bug fixes"
   ```

6. **Deploy to production**:
   ```bash
   clasp deploy --description "v0.6.1 Production"
   ```

### Critical Update Checklist

Before deploying updates:

- [ ] All `console.log` statements removed or converted to `Logger.log`
- [ ] No hardcoded script IDs or spreadsheet IDs
- [ ] All functions handle errors gracefully
- [ ] Sheet names match constants in code
- [ ] Column order preserved (never reorder existing columns)
- [ ] LockService used for concurrent operations
- [ ] Indonesian language used for all user-facing text
- [ ] Tested with existing spreadsheet data

### Database Schema Changes

If adding new columns:

1. **DO NOT** reorder existing columns
2. **DO NOT** rename existing columns
3. **ADD** new columns at the end of the sheet
4. **UPDATE** `SHEET_HEADERS` constant in code
5. **UPDATE** `ensureExtraHeaders_()` function if needed
6. **TEST** with existing data to ensure no breakage

---

## Rollback Procedure

### If New Deployment Causes Issues

1. **Immediate rollback** via clasp:
   ```bash
   # List deployments
   clasp deployments
   
   # The web app URL always points to latest version
   # To rollback, deploy a previous version:
   clasp deploy [VERSION_NUMBER] --description "Rollback to v0.6.0"
   ```

2. **Or via Apps Script Editor**:
   - Go to Deploy > Manage deployments
   - Find the active deployment
   - Click Edit (pencil icon)
   - Select a previous version
   - Save

### If Code Push Breaks Script

1. **Quick fix** via Apps Script Editor:
   - Open the script directly at script.google.com
   - Fix the issue in the editor
   - Save and test

2. **Or rollback code** via git:
   ```bash
   git revert HEAD
   clasp push
   ```

---

## Troubleshooting

### Common Issues

#### "Script ID not found"
- **Cause**: `.clasp.json` has wrong script ID
- **Fix**: Verify script ID in Apps Script URL

#### "Authorization required"
- **Cause**: Script needs OAuth scopes
- **Fix**: Run any function in Apps Script editor to trigger authorization

#### "Deployment URL not working"
- **Cause**: Wrong deployment type or access settings
- **Fix**: Check deployment settings, ensure "Execute as" is correct

#### "Cannot edit source"
- **Cause**: Editing a deployed version
- **Fix**: Use `clasp push` to update source, or open script.google.com

#### "Rate limit exceeded"
- **Cause**: Too many API calls
- **Fix**: Wait and retry, or batch operations in code

#### "Spreadsheet not found"
- **Cause**: Spreadsheet was deleted or access revoked
- **Fix**: Verify spreadsheet exists and script has access

#### Master data not showing
- **Cause**: Master data sheets not created or seeded
- **Fix**: Run `seedAllMasterData()` in Apps Script editor

#### "MEMO required" error
- **Cause**: Apps Script requires MEMO for database operations
- **Fix**: Run the script once via `clasp open` and execute manually

### Debug Mode

To enable detailed logging:

1. In Apps Script editor, go to Project Settings
2. Enable "Executions" logging
3. Check **Executions** tab for detailed logs

### Check Execution Logs

1. Go to Apps Script editor
2. Click **Executions** (clock icon) in left sidebar
3. View recent executions and their status
4. Click on an execution to see logs

---

## URL Structure

### Web App URLs

| Type | URL Pattern |
|------|-------------|
| Dev | `https://script.google.com/macros/s/[ID]/dev` |
| Production | `https://script.google.com/macros/s/[ID]/exec` |

### Page URLs (via query parameters)

| Page | URL |
|------|-----|
| Landing | `[BASE_URL]` |
| Dashboard | `[BASE_URL]?page=dashboard` |
| Employee | `[BASE_URL]?page=employee` |
| Master Data | `[BASE_URL]?page=masterdata` |
| Settings | `[BASE_URL]?page=settings` |
| User Management | `[BASE_URL]?page=users` |
| Login | `[BASE_URL]?page=login` |
| Registration | `[BASE_URL]?type=kandidat` |
| Candidate Status | `[BASE_URL]?type=status` |

---

## Security Checklist

Before production deployment:

- [ ] `.clasp.json` is in `.gitignore` (never commit script ID)
- [ ] No API keys or secrets in code
- [ ] All user inputs validated server-side
- [ ] SQL injection prevention (use `getRange()` with proper parameters)
- [ ] XSS prevention (sanitize all HTML output)
- [ ] CSRF protection (validate form tokens)
- [ ] Rate limiting on public endpoints
- [ ] Audit logging enabled for all status changes

---

## Post-Deployment Verification

After deploying, verify:

1. **Public pages accessible**:
   - Landing page loads
   - Registration form works
   - Candidate status page works

2. **Authentication works**:
   - Login page loads
   - Google OAuth flow completes
   - Role-based redirect works

3. **Dashboard loads**:
   - Statistics cards show data
   - Charts render correctly
   - Data table loads candidates

4. **CRUD operations work**:
   - Create new candidate
   - Edit candidate details
   - Update candidate status
   - Delete candidate (if authorized)

5. **Export works**:
   - CSV export downloads
   - Excel export downloads
   - PDF export downloads

6. **Audit trail works**:
   - Status changes logged
   - Activity timeline shows entries

---

## Last Updated
2026-08-03 - v0.6.0 initial deployment documentation