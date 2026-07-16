# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Framework-free PHP 8.1+ cashback/CRM management system for a beauty-industry business (customers, purchases, cashback, wallet, SMS reminders via ippanel, activity logs). Built for shared cPanel hosting — no Node.js, no daemons required. UI and commit messages are in Persian/Farsi; code identifiers are in English.

## Commands

```bash
composer install                                    # install deps (phpoffice/phpspreadsheet, phpunit) + autoloader
cp config/config.example.php config/config.php       # local config
php -S localhost:8000 -t public public/index.php     # run dev server
vendor/bin/phpunit                                    # run all tests
vendor/bin/phpunit tests/Unit/MigrationRunnerTest.php # run a single test file
vendor/bin/phpunit --filter testBehaviorName          # run a single test method
php database/migrate.php                              # apply pending SQL migrations
php database/seed_admin.php admin "StrongPassword" "Admin Name"  # create first admin
composer dump-autoload                                 # after adding/moving namespaced classes
```

No linter/formatter is configured — match the style of surrounding code (PSR-12-ish, 4-space indent, `declare(strict_types=1);`, PascalCase classes, camelCase methods/vars).

## Architecture

**Request flow:** `public/index.php` → `bootstrap/app.php` → `routes.php` → `App\Core\Router::dispatch()` → Controller → Service/Repository → `App\Core\Database` (PDO).

- `app/Core` — infrastructure: `Router` (route table + auth/role/permission gate + `/api/v1` sub-dispatch), `Auth`, `Csrf`, `Database` (PDO singleton), `View`, `Validator`, `Flash`, `Jalali` (Persian calendar helper).
- `app/Controllers` (+ `Admin/`, `Api/`) — thin HTTP handlers; `Api/V1Controller` serves the POS API (`/api/v1/*`, authenticated via `X-Api-Key`, see `ApiAuthService`).
- `app/Services` — business logic (SMS sending/retry, cashback calc, updater, migrations, schema health, etc).
- `app/Repositories` — data access (prepared PDO statements only).
- `app/Models` — plain data structures.
- `app/Middleware` — cross-cutting request handling.
- `resources/views` — PHP templates rendered by `App\Core\View`; `public/assets` — CSS/JS (`app.js` has a cache-busting version param — bump it when changed).
- `routes.php` — single source of truth for all web + API routes; each route declares `auth` (bool), `role` (e.g. `admin`), and `permission` string checked by the Router before dispatch.

**Config resolution** (`bootstrap/app.php`): looks for config in order — `<root>/cashback_config.php` → `<parent-of-root>/cashback_config.php` (WordPress-style, survives ZIP redeploys) → `config/config.php` → `config/config.example.php` (triggers redirect to installer if used in production). Never assume `config/config.php` is the active config when debugging — check which of these exists.

**Migrations** (`database/migrations/*.sql`, sequence `NNN_description.sql`): tracked in a `schema_migrations` table, applied via `App\Services\MigrationRunner` (checksummed; can detect/repair drifted files). Run through `php database/migrate.php`, not raw SQL, so the tracking table stays authoritative. `App\Services\SchemaHealthService` cross-checks that expected tables from specific migrations actually exist (used by the admin System Status page) — add new checks there when adding migrations that new features depend on.

**Auth model:** session-based for staff (`App\Core\Auth`, role + fine-grained `permission` strings checked per-route), separate OTP/SMS-based auth for the public customer `/portal`, and API-key auth (`X-Api-Key: PREFIX.SECRET`, optional `X-Idempotency-Key`) for the POS API.

**Self-update:** admins can pull and apply the latest `main` branch from GitHub via `AppUpdaterService` (backs up to `storage/backups` first; never overwrites `cashback_config.php`, `config/config.php`, `storage`, `.env`, `.git`, `vendor`).

**Cron:** `cron/run.php {birthday|contract_renewal|sms_retry|all}` invoked by cPanel cron, or via `/internal/cron?task=all&token=...` (web-token fallback for hosts without cron access), or opportunistically when an admin loads the dashboard.

## Security conventions (enforced throughout, keep consistent)

- All queries via PDO prepared statements — never interpolate user input into SQL.
- All state-changing POST forms require CSRF tokens (`App\Core\Csrf`).
- All rendered output is escaped in views.
- Passwords via `password_hash`/`password_verify` only.
- Never commit `config/config.php`, `cashback_config.php`, install locks, logs, or DB dumps (see `.gitignore`).

## Testing

`tests/Unit/*Test.php`, PHPUnit 10, bootstrap at `tests/bootstrap.php`. For changes touching auth, purchases, wallet balances, SMS, the API, migrations, or deployment, also walk the relevant items in `tests/manual-test-checklist.md` — these areas are under-covered by automated tests.
