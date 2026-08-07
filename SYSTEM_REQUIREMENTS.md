# SYSTEM_REQUIREMENTS.md — System Requirements

Complete list of system requirements for developing, deploying, and using MITO HRIS.

---

## Table of Contents

1. [For Developers](#for-developers)
2. [For End Users](#for-end-users)
3. [For System Administrators](#for-system-administrators)
4. [Browser Requirements](#browser-requirements)
5. [Google Workspace Requirements](#google-workspace-requirements)
6. [Development Tools](#development-tools)

---

## For Developers

### Minimum Requirements

| Requirement | Version | Purpose |
|-------------|---------|---------|
| Node.js | 18.0+ | Running clasp CLI |
| npm | 9.0+ | Installing clasp |
| Git | 2.40+ | Version control |
| Clasp | 2.4+ | Apps Script deployment |
| Google Account | - | Apps Script access |

### Recommended Setup

| Tool | Version | Purpose |
|------|---------|---------|
| Node.js | 20 LTS | Latest stable |
| npm | 10+ | Package management |
| VS Code | Latest | Code editor |
| Git | 2.44+ | Latest features |
| Clasp | 2.7+ | Latest features |

### VS Code Extensions (Recommended)

| Extension | Purpose |
|-----------|---------|
| Google Apps Script | Syntax highlighting |
| ES6 String HTML | Template literals in HTML |
| Prettier | Code formatting |
| GitLens | Git integration |
| Todo Tree | TODO comment tracking |

---

## For End Users

### Browser Requirements

| Browser | Minimum Version | Recommended |
|---------|-----------------|-------------|
| Google Chrome | 90+ | Latest |
| Mozilla Firefox | 88+ | Latest |
| Microsoft Edge | 90+ | Latest |
| Safari | 14+ | Latest |

**Note**: Internet Explorer is NOT supported.

### Screen Resolution

| Device | Minimum | Recommended |
|--------|---------|-------------|
| Desktop | 1280×720 | 1920×1080 |
| Tablet | 768×1024 | 1024×768 |
| Mobile | 320×568 | 375×667 |

### Network Requirements

- Stable internet connection
- Minimum bandwidth: 1 Mbps
- Access to Google services (accounts.google.com, script.google.com)

### Google Account

- Valid Google account (Gmail or Google Workspace)
- JavaScript enabled in browser
- Cookies enabled for Google services

---

## For System Administrators

### Google Workspace Requirements

| Requirement | Details |
|-------------|---------|
| Google Account | Individual or Workspace account |
| Apps Script Access | Enabled for the domain |
| Google Sheets | Create and edit access |
| Google Drive | Read access (for file operations) |
| OAuth Scopes | Standard web app scopes |

### Required OAuth Scopes

The application requires these Google OAuth scopes:

```
https://www.googleapis.com/auth/spreadsheets
https://www.googleapis.com/auth/script.external_request
https://www.googleapis.com/auth/userinfo.email
https://www.googleapis.com/auth/userinfo.profile
```

### Spreadsheet Permissions

| Action | Permission Required |
|--------|---------------------|
| Read data | Editor access to spreadsheet |
| Write data | Editor access to spreadsheet |
| Create sheets | Owner or Editor access |
| Delete data | Owner access (recommended) |

### Deployment Access Levels

| Level | Who Can Access | Use Case |
|-------|----------------|----------|
| `Anyone` | Public (no login) | Public registration |
| `Anyone with Google account` | Google users only | Internal use |
| `Anyone in organization` | Domain users only | Workspace domains |
| `Only myself` | Script owner only | Testing |

---

## Browser Requirements

### JavaScript Features Required

The application uses these JavaScript features:

- ES6+ (let, const, arrow functions, template literals)
- Fetch API
- Promise/async-await
- Array methods (map, filter, reduce, find)
- LocalStorage
- Blob API (for file downloads)
- URL.createObjectURL
- Bootstrap 5 JavaScript components

### CSS Features Required

- CSS Grid
- CSS Flexbox
- CSS Custom Properties (variables)
- CSS Media Queries
- CSS Transitions/Animations

### Browser API Support

| API | Required | Polyfill Available |
|-----|----------|-------------------|
| Fetch API | Yes | Yes (for older browsers) |
| LocalStorage | Yes | No |
| Blob | Yes | No |
| URL.createObjectURL | Yes | No |
| Intersection Observer | No | Yes |
| Web Animations API | No | No |

---

## Google Workspace Requirements

### Google Sheets

- **Version**: Latest (Google Sheets, not classic)
- **Permissions**: Edit access to the spreadsheet
- **Sharing**: Can be shared with team members
- **Location**: Any Google Drive location

### Google Apps Script

- **Access**: Enabled for the Google account
- **Quotas**: Standard Apps Script quotas apply:
  - 6 minutes per execution
  - 90 minutes total runtime per day (consumer)
  - 6 hours total runtime per day (Workspace)
  - 50,000 URL fetch calls per day
  - 100 GB total bytes transferred per day

### Google OAuth

- **Type**: OAuth 2.0
- **Flow**: Implicit or Authorization Code
- **Token Storage**: Server-side (Apps Script)
- **Session Duration**: Until browser session ends

---

## Development Tools

### Required Tools

| Tool | Version | Install Command |
|------|---------|-----------------|
| Node.js | 18+ | Download from nodejs.org |
| npm | 9+ | Included with Node.js |
| Git | 2.40+ | Download from git-scm.com |
| Clasp | 2.4+ | `npm install -g @google/clasp` |

### Install All Development Tools

```bash
# 1. Install Node.js (Windows)
winget install OpenJS.NodeJS.LTS

# 2. Install Node.js (macOS)
brew install node

# 3. Install Node.js (Linux - Ubuntu)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# 4. Install Clasp globally
npm install -g @google/clasp

# 5. Verify installation
node --version
npm --version
clasp --version
git --version
```

### Authentication Setup

```bash
# Login to Google via clasp
clasp login

# This opens browser for Google OAuth consent
# After consent, credentials are stored locally
```

---

## Platform-Specific Requirements

### Windows

- Windows 10 or later
- PowerShell 5.1+ or Command Prompt
- Git for Windows (includes Git Bash)

### macOS

- macOS 12 or later
- Terminal app
- Xcode Command Line Tools (for Git)

### Linux

- Ubuntu 20.04+ or equivalent
- Bash shell
- Standard build tools

---

## Network Requirements

### Outbound Connections

| Host | Port | Purpose |
|------|------|---------|
| accounts.google.com | 443 | OAuth authentication |
| script.google.com | 443 | Apps Script deployment |
| docs.google.com | 443 | Spreadsheet access |
| www.googleapis.com | 443 | Google APIs |
| cdn.jsdelivr.net | 443 | Bootstrap, Chart.js CDN |
| unpkg.com | 443 | Bootstrap Icons CDN |

### Firewall Rules

Ensure outbound HTTPS (port 443) is allowed to Google domains.

---

## Performance Requirements

### Client-Side

| Metric | Target |
|--------|--------|
| Initial Load | < 3 seconds |
| Chart Rendering | < 2 seconds |
| Table Pagination | < 500ms |
| Filter Application | < 1 second |
| Export (CSV) | < 2 seconds |
| Export (PDF) | < 5 seconds |

### Server-Side (Apps Script)

| Metric | Limit |
|--------|-------|
| Execution Time | < 6 minutes per call |
| Memory | 256 MB per execution |
| Response Size | < 50 MB |

---

## Security Requirements

### Authentication

- Google OAuth 2.0 required for authenticated pages
- Session management via Apps Script
- Role-based access control (RBAC)

### Data Protection

- All data stored in Google Spreadsheet
- HTTPS encryption for all communications
- Input validation on server-side
- Output sanitization for XSS prevention

### Access Control

| Role | Access Level |
|------|--------------|
| Admin | Full access (all modules) |
| HRD | Recruitment + Employee (limited) |
| User | Read-only (limited modules) |

---

## Last Updated
2026-08-03 - v0.6.0 system requirements documentation
