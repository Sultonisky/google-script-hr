# AGENTS.md - AI Agent Instructions for MITO HRIS

## Project Overview

MITO HRIS is a Laravel application in `mito-hris-laravel/`. The repository is
the original shared Git repository, but the legacy Google Apps Script source has
been removed. Do not recreate the GAS application or introduce a second
repository.

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Blade, JavaScript, SCSS, Bootstrap 5, Vite
- **Data services:** Google Sheets API and Google Drive API through Laravel services
- **Authentication:** Session-based HR and MPR authentication with domain isolation
- **Deployment:** GitHub Actions, SSH, Composer, NPM/Vite, and Laravel Artisan
- **User-facing language:** Indonesian unless existing product copy requires otherwise

## Repository Boundaries

- `mito-hris-laravel/` is the active application and must be preserved.
- `.github/workflows/` contains the active Laravel CI/CD and must not be changed
  unless a concrete deployment defect is demonstrated.
- `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`,
  `routes/`, `tests/`, `composer.json`, `composer.lock`, `package.json`, and
  `vite.config.js` are protected application surfaces.
- Root historical/reference files may be reviewed separately, but do not assume
  that a document, spreadsheet, image, or template is disposable.
- Do not recreate deleted GAS files such as `Kode.gs`, `backend/`, `views/`,
  `partials/`, `css/`, or `js/`.

## Development Rules

### Laravel and PHP

- Follow existing Laravel controllers, services, repositories, DTOs, requests,
  middleware, policies, and Blade conventions before adding abstractions.
- Keep authentication, authorization, RBAC, domain middleware, and session
  isolation intact.
- Keep public URLs, route names, request contracts, sheet schemas, and business
  workflows backward compatible unless the task explicitly changes them.
- Use strict validation at request boundaries and preserve existing Indonesian
  error messages and response shapes.
- Keep PHP formatting consistent with the surrounding file. Do not perform
  broad formatting or unrelated refactors.

### Google Sheets and Drive Integration

- Google Sheets and Google Drive are valid Laravel integrations, not Apps Script.
- Preserve `google/apiclient`, `config/google.php`, Google client factories,
  Sheets repositories/services, Drive services, and credential configuration.
- Do not rename existing spreadsheet tabs or headers without explicit approval.
- Preserve canonical sheet column order and identifier handling, especially NIK,
  phone numbers, employee IDs, and recruitment IDs.
- Store timestamps using the existing `Asia/Jakarta` convention.
- Never expose service-account credentials, `.env` values, or uploaded secrets.

### Frontend

- Use the existing Blade and Vite entrypoints in `resources/`.
- Preserve Bootstrap 5, Bootstrap Icons, existing SCSS variables, responsive
  behavior, CSP hardening, and domain-specific layouts.
- Do not reintroduce the removed GAS HTML include pattern or `google.script.run`.
- Keep public career/outsource portals and HR/MPR dashboards isolated as the
  current middleware and view structure requires.

### File Modification and Git Safety

- Make the smallest change that solves the request.
- Never rewrite Git history, change remotes, create repositories, force-push, or
  change branches unless explicitly requested.
- Do not run destructive commands such as `git reset --hard`, `git clean -fd`,
  history filters, or broad deletion commands.
- Before editing, inspect `git status` and preserve unrelated user changes.
- Do not commit changes unless explicitly requested.

## Validation

Use the narrowest relevant checks, then broaden when the change warrants it.
Typical Laravel checks are:

```powershell
php artisan test --without-tty
php artisan view:cache
php artisan route:list
npm run build
```

For PHP-only changes, run PHP syntax checks on the touched slice. For changes
to Google integration, use targeted tests and avoid live destructive operations
against production Sheets or Drive.

## Important Commands

CI runs from `mito-hris-laravel/` and installs Composer/NPM dependencies, checks
PHP syntax, builds Vite assets, and runs Laravel tests. Deployment runs from the
same application directory over SSH and executes Laravel Artisan diagnostics,
Google Sheets health/schema checks, sync, caching, and PHP-FPM reload.

Do not add `clasp`, Apps Script deployment, or GAS-specific CI tasks.

## Response Requirements

When completing a coding task, report:

1. What changed and why.
2. Modified files.
3. Dependencies added or changed, or state that none were added.
4. Validation actually run and its result.
5. Breaking changes, explicitly stating `Breaking Changes: None` when applicable.

Do not claim tests, deployment, or external service checks passed unless they
were actually executed.

## Last Updated

2026-09-05 - Updated for the Laravel application after legacy GAS cleanup.
