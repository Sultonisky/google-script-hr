# MITO HRIS - Technical Project Documentation

## Project Information

**Project Name:** MITO HRIS
**Active Application:** `mito-hris-laravel/`
**Framework:** Laravel 12
**PHP:** 8.2+
**Company:** MITO Group
**Purpose:** Recruitment management, employee administration, probation, MPR, and public career/outsource portals.

The repository is the original shared Git repository. Laravel is the active application. The former Google Apps Script source has been removed; do not recreate it or split Laravel into another repository.

## Technology Stack

### Backend

- Laravel 12
- PHP 8.2+
- Session-based authentication for HR and MPR portals
- Domain middleware and RBAC authorization
- Form Requests, DTOs, enums, services, repositories, events, and listeners

### Frontend

- Blade templates under `resources/views/`
- JavaScript under `resources/js/`
- SCSS under `resources/scss/`
- Bootstrap 5 and Bootstrap Icons
- Vite 6 with `laravel-vite-plugin`
- Chart.js, Axios, SweetAlert2, and PhpSpreadsheet integrations where used

### External Data Services

- Google Sheets API through `google/apiclient`
- Google Drive API through Laravel Google services
- Google Sheets remains the business data source; this is a Laravel API integration, not Google Apps Script

### Documents

- Dompdf for generated PDFs
- Blade PDF templates for offering letters, contracts, BPJS letters, paklaring, performance reviews, and related HR documents

## Application Structure

```text
mito-hris-laravel/
├── app/
│   ├── Console/Commands/       # Setup, diagnostics, sync, and seed commands
│   ├── DTOs/                   # Typed candidate, employee, and MPR data
│   ├── Enums/                  # Domain statuses and decisions
│   ├── Events/Listeners/       # Domain events and side effects
│   ├── Http/
│   │   ├── Controllers/        # HTTP and API orchestration
│   │   ├── Middleware/         # Auth, domain, role, and security boundaries
│   │   ├── Requests/            # Input validation
│   │   └── Resources/           # Response resources where applicable
│   ├── Repositories/           # Repository contracts and Sheets adapters
│   ├── Rules/                  # Reusable validation rules
│   ├── Services/               # Domain, Google API, PDF, and utility services
│   └── Support/                # Shared authorization support
├── bootstrap/                  # Laravel bootstrap and providers
├── config/                     # Framework, HRIS, Google, MPR, and SEO config
├── database/data/              # Laravel-local region JSON data
├── public/assets/              # Public images and generated frontend assets
├── resources/
│   ├── js/                     # Vite JavaScript entrypoints and modules
│   ├── scss/                   # Public and HR stylesheets
│   └── views/                  # Blade layouts, pages, components, and PDFs
├── routes/                     # Web, API, and console routes
├── storage/                    # Runtime files and protected credentials
└── tests/                      # Feature and unit tests
```

Root-level `data/`, `images/`, `templates/`, and `docs/` are separate reference or historical artifacts. Laravel does not load them by name; its runtime paths are inside `mito-hris-laravel/`.

## Portal Modules

### Public Career Portal

- Candidate career landing page
- Candidate application form
- Application status checking
- Candidate self-update flow
- Region and NIK lookup endpoints

### Public Outsource Portal

- Outsource registration form
- Validation and document upload flow
- Outsource candidate persistence through the Laravel service layer

### HR Portal

- Recruitment dashboard and candidate status management
- Employee master data and imports
- Employee rotation, offboarding, and contract workflows
- Probation evaluation and decision workflows
- Master data and portal settings
- User management and audit log access
- CSV/XLSX/PDF exports and HR document generation

### MPR Portal

- MPR requestor authentication
- Manpower request creation and history
- MPR PDF generation
- Separate MPR session and domain authorization context

## Request and Data Flow

```text
Browser
  -> public/index.php
  -> Laravel bootstrap and middleware
  -> domain-aware route
  -> controller and Form Request
  -> domain service
  -> repository contract
  -> Google Sheets or Google Drive service
  -> Blade, JSON, or PDF response
```

HR and MPR sessions remain isolated even when an identity exists in both account stores. Middleware and server-side authorization are the security boundary; UI visibility alone is not authorization.

## Google Sheets Contracts

Configured sheet tabs include:

- `data_kandidat`
- `kandidat_hold`
- `kandidat_blacklist`
- `kandidat_accepted`
- `kandidat_probation`
- `Employee`
- `Audit_Log`
- `Users`
- `MPR`
- `mpr_requestor`

Sheet names, header names, column order, identifier formats, and timestamps are compatibility contracts. Preserve them unless a task explicitly changes the schema and includes the required migration/validation plan.

NIK, phone numbers, recruitment IDs, and employee IDs must preserve text semantics and leading zeroes. Timestamps use the existing `Asia/Jakarta` convention.

## Configuration and Secrets

- Google configuration is defined in `config/google.php`.
- Credential paths and spreadsheet/Drive IDs come from environment variables.
- `.env`, service-account JSON, uploaded files, and generated secrets must never be committed or exposed in responses.
- Do not run destructive setup, sync, or schema-fix commands against production services during local validation.

## CI/CD and Deployment

`.github/workflows/ci.yml` runs from `mito-hris-laravel/` and performs PHP setup, Composer installation, environment preparation, PHP syntax validation, Vite build, and Laravel tests.

`.github/workflows/deploy.yml` deploys over SSH to the existing Laravel checkout at `/home/ubuntu/hris/mito-hris-laravel`. It installs production dependencies, builds assets, runs Laravel diagnostics and Google Sheets checks, caches Laravel, and reloads PHP-FPM.

There is no active Apps Script or `clasp` deployment.

## Development Commands

Run from `mito-hris-laravel/`:

```powershell
composer install
npm install
php artisan test --without-tty
php artisan view:cache
php artisan route:list
npm run build
```

For PHP-only changes, run `php -l` on the touched files. For Google integration changes, prefer targeted tests and mocks; avoid live destructive operations.

## Compatibility Rules

- Preserve public URLs and route names.
- Preserve authentication, authorization, RBAC, and domain isolation.
- Preserve Google Sheets schemas and Google Drive integration.
- Preserve Indonesian user-facing copy unless a product change requires otherwise.
- Use existing controllers, services, repositories, requests, and Blade patterns.
- Keep changes focused and avoid broad formatting or unrelated refactors.

## Related Documentation

- `AGENTS.md` - Agent and development rules
- `ARCHITECTURE.md` - Runtime architecture and deployment design
- `mito-hris-laravel/README.md` - Laravel framework reference
- `mito-hris-laravel/docs/` - Application-specific technical notes
- `.github/workflows/` - Active CI/CD workflows

## Last Updated

2026-09-05 - Updated for the active Laravel application after legacy GAS cleanup.
