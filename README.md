<p align="center">
  <img src="mito-hris-laravel/public/assets/mito-white.png" alt="MITO Group" width="280">
</p>

# MITO Group HRIS

MITO HRIS is a proprietary internal Human Resource Information System for MITO
Group. The active application is a Laravel 12 monolith in
`mito-hris-laravel/`.

This repository remains the original shared Git repository. The legacy Google
Apps Script runtime has been removed. Google Sheets and Google Drive remain
integrated through Laravel's Google API services.

## Project Status

- Active application: Laravel 12
- PHP: 8.2+
- Frontend: Blade, JavaScript, SCSS, Bootstrap 5, Vite
- Data services: Google Sheets API and Google Drive API
- Authentication: Session-based HR and MPR portals with domain isolation
- Deployment: GitHub Actions, SSH, Composer, NPM/Vite, and Laravel Artisan
- License: Proprietary and internal; not open source and not distributed under
  the MIT License

See [SECURITY.md](SECURITY.md) for access, security, and vulnerability reporting
policy.

## Application Areas

### Public Career Portal

- Career landing page and candidate application
- Application status checking
- Candidate self-update flow
- Region and NIK lookup endpoints

### Public Outsource Portal

- Outsource registration
- Validation and document upload flow
- Laravel service and Google Sheets persistence

### HR Portal

- Recruitment dashboard and candidate status management
- Employee master data and spreadsheet imports
- Contracts, rotations, offboarding, and probation
- Master data, portal settings, users, and audit logs
- CSV, XLSX, and PDF exports
- HR document generation

### MPR Portal

- Requestor authentication
- Manpower request creation and history
- MPR PDF generation
- Isolated MPR session and authorization context

## Technology Stack

| Technology        | Role                         |
| ----------------- | ---------------------------- |
| Laravel 12        | Application framework        |
| PHP 8.2+          | Backend runtime              |
| Blade             | Server-rendered views        |
| Vite 6            | Frontend asset build         |
| SCSS              | Application styling          |
| Bootstrap 5       | UI framework                 |
| Google API Client | Sheets and Drive integration |
| Dompdf            | PDF generation               |
| PhpSpreadsheet    | Spreadsheet import/export    |
| PHPUnit           | Automated tests              |

## Repository Structure

```text
google-script-hr/
├── .github/workflows/
│   ├── ci.yml                    # Laravel test and frontend build workflow
│   └── deploy.yml                # SSH production deployment workflow
├── mito-hris-laravel/
│   ├── app/                      # Controllers, services, repositories, DTOs, and rules
│   ├── bootstrap/                # Laravel bootstrap and providers
│   ├── config/                   # Application and Google integration configuration
│   ├── database/data/            # Laravel-local region data
│   ├── public/                   # Web entrypoint, assets, data, and build output
│   ├── resources/                # Blade views, JavaScript, and SCSS
│   ├── routes/                   # Web, API, and console routes
│   ├── storage/                  # Runtime files and protected credentials
│   └── tests/                    # Feature and unit tests
├── AGENTS.md                    # Agent and development rules
├── ARCHITECTURE.md              # Runtime architecture
├── CHANGELOG.md                 # Project history
├── LICENSE                      # Proprietary license terms
├── PROJECT.md                   # Technical project documentation
└── SECURITY.md                  # Proprietary access and security policy
```

Root-level reference artifacts such as `data/`, `images/`, `templates/`, and
historical documents are separate from the Laravel runtime. Do not assume they
are application dependencies without tracing their actual use.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js 20 or compatible current Node.js release
- NPM
- Access to the configured Google Sheets and Google Drive resources for live
  integration checks
- A service-account credential file for Google API operations

## Local Setup

From the repository root:

```powershell
Set-Location mito-hris-laravel
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
```

Configure `.env` before using Google integrations:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

GOOGLE_APPLICATION_CREDENTIALS="${APP_BASE_PATH}/storage/app/google/service-account.json"
GOOGLE_SPREADSHEET_ID=your-spreadsheet-id
GOOGLE_DRIVE_DOCS_FOLDER_ID=your-docs-folder-id
GOOGLE_DRIVE_OFFBOARDING_FOLDER_ID=your-offboarding-folder-id
GOOGLE_SHEETS_TIMEZONE=Asia/Jakarta
```

Place credentials only in the protected local storage path. Never commit
`.env`, service-account JSON, tokens, or production data.

## Development

Run the application using the Laravel and Vite development processes as needed:

```powershell
php artisan serve
npm run dev
```

For the full local development process defined by Composer:

```powershell
composer run dev
```

## Validation

Run from `mito-hris-laravel/`:

```powershell
php artisan test --without-tty
php artisan view:cache
php artisan route:list
npm run build
```

For PHP-only changes, run `php -l` on touched files. For Google integration
changes, use targeted tests and mocks where possible; avoid destructive live
operations against production Sheets or Drive resources.

## CI/CD

The CI workflow in `.github/workflows/ci.yml` runs from
`mito-hris-laravel/` and performs:

1. PHP 8.3 and Node.js 20 setup
2. Composer and NPM dependency installation
3. Laravel test environment preparation
4. PHP syntax validation
5. Vite production build
6. PHPUnit test suite

The deployment workflow in `.github/workflows/deploy.yml` deploys over SSH to
`/home/ubuntu/hris/mito-hris-laravel`. It installs production dependencies,
builds assets, runs Laravel and Google Sheets diagnostics, caches Laravel, and
reloads PHP-FPM.

There is no active Apps Script or `clasp` deployment.

## Compatibility Rules

- Preserve public URLs and route names.
- Preserve HR/MPR authentication, authorization, RBAC, and domain isolation.
- Preserve Google Sheets sheet names, headers, column order, and identifier
  handling.
- Preserve Google Drive integration and credential configuration.
- Preserve `Asia/Jakarta` timestamp behavior.
- Keep user-facing product copy in Indonesian unless a product requirement says
  otherwise.

## Documentation

- [AGENTS.md](AGENTS.md) - Agent and development rules
- [ARCHITECTURE.md](ARCHITECTURE.md) - Runtime architecture and deployment design
- [PROJECT.md](PROJECT.md) - Technical project documentation
- [CHANGELOG.md](CHANGELOG.md) - Project history and cleanup record
- [LICENSE](LICENSE) - Proprietary license terms
- [SECURITY.md](SECURITY.md) - Proprietary access and security policy
- [mito-hris-laravel/docs/](mito-hris-laravel/docs/) - Application-specific notes

## License and Access

MITO HRIS is proprietary internal software. It is not open source and is not
licensed under the MIT License. Third-party packages may use their own open
source licenses, but those licenses do not grant rights to the MITO HRIS source,
business data, credentials, deployment configuration, or documentation.

See [LICENSE](LICENSE) for the full proprietary license terms.

## Author

**Mohammad Sultoni Powered by MITO Group**

- GitHub: (https://github.com/Sultonisky)
- Email: (muhsultonipml111@gmail.com)

---

_MITO HRIS - Proprietary Laravel application for MITO Group_
