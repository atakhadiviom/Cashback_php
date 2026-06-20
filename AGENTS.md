# Repository Guidelines

## Project Structure & Module Organization

This is a framework-free PHP 8.1+ application with PSR-4 autoloading (`App\` maps to `app/`).

- `app/Controllers`, `app/Services`, `app/Repositories`, and `app/Models` contain request handling and business/data logic.
- `app/Core` provides shared infrastructure such as routing, authentication, CSRF, database access, and views.
- `resources/views` contains PHP templates; `public/assets` contains the application CSS and JavaScript.
- `routes.php` defines web and API routes. `public/index.php` is the web entry point.
- `database/schema.sql`, `database/migrations`, and `database/seed_admin.php` manage database setup.
- `tests/Unit` contains PHPUnit tests; `tests/manual-test-checklist.md` covers integration and deployment checks.
- `cron` contains scheduled SMS jobs.

## Build, Test, and Development Commands

```bash
composer install
cp config/config.example.php config/config.php
php -S localhost:8000 -t public public/index.php
vendor/bin/phpunit
php database/migrate.php
```

`composer install` installs PHPUnit and generates the autoloader. Configure MySQL/MariaDB before starting the local server. Run `php database/seed_admin.php admin "StrongPassword" "Admin Name"` for a new database. Use `composer dump-autoload` after adding or moving namespaced classes.

## Coding Style & Naming Conventions

Use `declare(strict_types=1);`, four-space indentation, and PSR-12-style formatting. Classes are `PascalCase`, methods and variables are `camelCase`, and SQL migration files use a zero-padded sequence such as `009_feature_name.sql`. Keep controllers thin; place business rules in services and database operations in repositories. Escape rendered output, use prepared PDO statements, and require CSRF validation for state-changing browser requests. No formatter or linter is configured, so match adjacent code carefully.

## Testing Guidelines

Add PHPUnit tests under `tests/Unit` using `*Test.php` filenames and `testBehaviorName()` methods. Run `vendor/bin/phpunit` before every PR. For changes involving authentication, purchases, wallet balances, SMS, API behavior, migrations, or deployment, also execute the relevant items in `tests/manual-test-checklist.md`.

## Commit & Pull Request Guidelines

Recent commits use short, imperative, sentence-case subjects, for example `Polish purchase customer dropdown styling`. Keep commits focused and include migrations or asset cache-version updates when required. PRs should explain the user-visible behavior, database/configuration impact, and tests performed; link related issues and include screenshots for UI changes.

## Security & Configuration

Never commit `config/config.php`, credentials, API tokens, installation locks, logs, or database dumps. Preserve deployment-specific configuration during upgrades and remove or lock `public/install.php` in production.
