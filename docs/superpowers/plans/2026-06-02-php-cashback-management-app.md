# PHP Cashback Management App Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete Persian RTL PHP/MySQL cashback management web app deployable to shared hosting/cPanel by uploading files, importing SQL, and configuring cron.

**Architecture:** Use a small custom PHP 8.1+ MVC-style structure with front-controller routing, PDO repositories, service classes for business rules, PHP templates for Bootstrap 5 RTL pages, and cPanel-compatible cron scripts. Store all dates in MySQL as Gregorian `DATE`/`DATETIME`, display them in Jalali/Persian format, and keep all secrets in `config/config.php` copied from `config/config.example.php`.

**Tech Stack:** PHP 8.1+, MySQL/MariaDB, PDO, Bootstrap 5 RTL CDN/assets, vanilla JavaScript only where needed, cPanel cron, ippanel HTTP API.

---

## File Structure

Create this structure from an empty repository:

```text
Cashback_php/
  app/
    Controllers/
      AuthController.php
      DashboardController.php
      CustomerController.php
      PurchaseController.php
      WalletController.php
      ReportController.php
      SmsController.php
      Admin/UserController.php
      Admin/ActivityLogController.php
      Admin/SettingsController.php
    Core/
      App.php
      Auth.php
      Csrf.php
      Database.php
      Flash.php
      Jalali.php
      Request.php
      Response.php
      Router.php
      Validator.php
      View.php
    Middleware/
      RequireAuth.php
      RequireRole.php
    Models/
      ActivityLog.php
      Customer.php
      Purchase.php
      SmsLog.php
      SmsSettings.php
      User.php
      WalletTransaction.php
    Repositories/
      ActivityLogRepository.php
      CustomerRepository.php
      PurchaseRepository.php
      ReportRepository.php
      SmsRepository.php
      UserRepository.php
      WalletRepository.php
    Services/
      ActivityLogger.php
      CustomerService.php
      IppanelSmsProvider.php
      PurchaseService.php
      SmsTemplateRenderer.php
      SmsService.php
      WalletService.php
    helpers.php
  bootstrap/
    app.php
  config/
    config.example.php
    config.php
  cron/
    send_birthday_sms.php
  database/
    schema.sql
    seed_admin.php
  public/
    index.php
    assets/
      css/app.css
      js/app.js
  resources/
    views/
      layouts/app.php
      auth/login.php
      dashboard/index.php
      customers/index.php
      customers/create.php
      customers/edit.php
      customers/show.php
      purchases/create.php
      wallet/reduce.php
      reports/index.php
      sms/logs.php
      admin/users/index.php
      admin/users/create.php
      admin/users/edit.php
      admin/activity_logs.php
      admin/sms_settings.php
  tests/
    manual-test-checklist.md
  README.md
```

Responsibilities:

- `public/index.php`: only web entrypoint, safe for `public_html`.
- `bootstrap/app.php`: loads config, session, helpers, routes.
- `app/Core/*`: framework-like primitives only.
- `app/Controllers/*`: request handling, authorization, validation orchestration.
- `app/Repositories/*`: PDO queries only.
- `app/Services/*`: business rules, transactions, SMS behavior, activity logging.
- `resources/views/*`: escaped Persian RTL templates.
- `database/schema.sql`: full MySQL schema and default settings rows.
- `database/seed_admin.php`: CLI/browser-safe first-admin creation helper.
- `cron/send_birthday_sms.php`: daily birthday SMS script for cPanel cron.

## Milestone 1: Project Bootstrap, Config, Routing, Layout

### Task 1: Create App Skeleton

**Files:**
- Create: all directories listed in File Structure
- Create: `config/config.example.php`
- Create: `config/config.php`
- Create: `app/helpers.php`
- Create: `bootstrap/app.php`
- Create: `public/index.php`
- Create: `public/assets/css/app.css`
- Create: `public/assets/js/app.js`
- Create: `README.md`

- [ ] **Step 1: Create directories**

Run:

```bash
mkdir -p app/{Controllers/Admin,Core,Middleware,Models,Repositories,Services} bootstrap config cron database public/assets/{css,js} resources/views/{layouts,auth,dashboard,customers,purchases,wallet,reports,sms,admin/users} tests
```

Expected: directories exist with no output.

- [ ] **Step 2: Add shared hosting config files**

`config/config.example.php` must contain:

```php
<?php
return [
    'app' => [
        'name' => 'سیستم مدیریت کش بک نوآوران زیبایی',
        'base_url' => '',
        'timezone' => 'Asia/Tehran',
        'debug' => false,
        'birthday_required' => false,
        'company_name' => 'نوآوران زیبایی',
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'cpanel_database_name',
        'user' => 'cpanel_database_user',
        'password' => 'cpanel_database_password',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'session_name' => 'cashback_session',
    ],
];
```

Copy it to `config/config.php` for local development.

- [ ] **Step 3: Add helper functions**

`app/helpers.php` must provide `config_value()`, `e()`, `redirect()`, `url()`, `money()`, `normalize_digits()`, and `current_datetime()`:

```php
<?php

function config_value(string $key, mixed $default = null): mixed
{
    $config = $GLOBALS['config'] ?? [];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($config) || !array_key_exists($segment, $config)) {
            return $default;
        }
        $config = $config[$segment];
    }
    return $config;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function url(string $path): string
{
    $base = rtrim((string) config_value('app.base_url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function money(int|float|string|null $amount): string
{
    return number_format((float) $amount, 0, '.', ',');
}

function normalize_digits(string $value): string
{
    return strtr($value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function current_datetime(): string
{
    return date('Y-m-d H:i:s');
}
```

- [ ] **Step 4: Add public front controller**

`public/index.php` must require `bootstrap/app.php` and dispatch the router.

- [ ] **Step 5: Verify syntax**

Run:

```bash
find app bootstrap config public -name '*.php' -print0 | xargs -0 -n1 php -l
```

Expected: every file reports `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add app bootstrap config public README.md
git commit -m "chore: bootstrap php cashback app"
```

### Task 2: Core Classes, Sessions, Flash, CSRF, Views

**Files:**
- Create: `app/Core/Database.php`
- Create: `app/Core/Router.php`
- Create: `app/Core/Request.php`
- Create: `app/Core/Response.php`
- Create: `app/Core/View.php`
- Create: `app/Core/Flash.php`
- Create: `app/Core/Csrf.php`
- Create: `app/Core/Auth.php`
- Modify: `bootstrap/app.php`
- Create: `resources/views/layouts/app.php`

- [ ] **Step 1: Implement PDO connection**

`Database::pdo()` must build a singleton PDO using `config_value('database.*')`, set `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, and `MYSQL_ATTR_INIT_COMMAND => SET NAMES utf8mb4`.

- [ ] **Step 2: Implement routing**

`Router` must support `get($path, $handler)`, `post($path, $handler)`, and `dispatch($method, $uri)`. Match literal paths for this project; dynamic IDs may be read from query parameters such as `/customers/show?id=1` to keep shared-host routing simple.

- [ ] **Step 3: Implement CSRF**

`Csrf::token()` stores a session token. `Csrf::validate($token)` uses `hash_equals`. All POST forms include:

```php
<input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
```

- [ ] **Step 4: Implement Persian RTL layout**

`resources/views/layouts/app.php` must set:

```html
<html lang="fa" dir="rtl">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
```

Navigation must include Dashboard, Customers, Add Customer, Search, Purchases, Reports, SMS Logs, and admin-only links for Operators, Activity Logs, and SMS Settings.

- [ ] **Step 5: Verify**

Run:

```bash
php -l app/Core/Database.php app/Core/Router.php app/Core/Csrf.php resources/views/layouts/app.php
```

Expected: no syntax errors.

- [ ] **Step 6: Commit**

```bash
git add app/Core bootstrap/app.php resources/views/layouts/app.php
git commit -m "feat: add core routing and layout"
```

## Milestone 2: Database Schema and Authentication

### Task 3: SQL Schema and Admin Seeder

**Files:**
- Create: `database/schema.sql`
- Create: `database/seed_admin.php`

- [ ] **Step 1: Create schema**

`database/schema.sql` must create these tables with `utf8mb4_unicode_ci` collation:

```sql
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','operator') NOT NULL DEFAULT 'operator',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  national_code CHAR(10) NOT NULL UNIQUE,
  phone_number CHAR(11) NOT NULL,
  birthday DATE NULL,
  wallet_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_customers_name (last_name, first_name),
  INDEX idx_customers_phone (phone_number),
  INDEX idx_customers_birthday (birthday),
  CONSTRAINT fk_customers_created_by FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE purchases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  cashback_amount DECIMAL(15,2) NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_purchases_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_purchases_created_by FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE wallet_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  type ENUM('cashback','reduction') NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  balance_after DECIMAL(15,2) NOT NULL,
  reason VARCHAR(255) NULL,
  purchase_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_wallet_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_wallet_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,
  CONSTRAINT fk_wallet_created_by FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE sms_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  api_token TEXT NULL,
  sender_number VARCHAR(50) NULL,
  sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  purchase_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  birthday_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  wallet_reduction_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  welcome_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  purchase_template TEXT NOT NULL,
  birthday_template TEXT NOT NULL,
  wallet_reduction_template TEXT NOT NULL,
  welcome_template TEXT NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE sms_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NULL,
  phone_number VARCHAR(20) NOT NULL,
  event_type ENUM('purchase','birthday','wallet_reduction','welcome','manual') NOT NULL,
  message TEXT NOT NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'ippanel',
  provider_response TEXT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_sms_logs_event (event_type),
  INDEX idx_sms_logs_status (status),
  CONSTRAINT fk_sms_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE birthday_sms_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  sent_year SMALLINT UNSIGNED NOT NULL,
  sms_log_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_birthday_customer_year (customer_id, sent_year),
  CONSTRAINT fk_birthday_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_birthday_sms_log FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE SET NULL
);

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  activity_type ENUM('login','logout','customer_create','customer_edit','purchase_create','wallet_reduction','operator_create','operator_edit','sms_sent','sms_failed','report_export') NOT NULL,
  description VARCHAR(500) NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_activity_type (activity_type),
  INDEX idx_activity_created_at (created_at),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_activity_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

INSERT INTO sms_settings (...) VALUES (...);
```

Fill the final insert with the four Persian templates from the spec and `id = 1`.

- [ ] **Step 2: Add admin seeder**

`database/seed_admin.php` must accept CLI arguments:

```bash
php database/seed_admin.php admin admin_password "مدیر سیستم"
```

It inserts an active admin with `password_hash($password, PASSWORD_DEFAULT)` and refuses to overwrite an existing username.

- [ ] **Step 3: Verify import**

Run locally after setting `config/config.php`:

```bash
mysql -u root -p cashback_php < database/schema.sql
php database/seed_admin.php admin ChangeMe123 "مدیر سیستم"
```

Expected: schema imports and seed script prints that admin user was created.

- [ ] **Step 4: Commit**

```bash
git add database/schema.sql database/seed_admin.php
git commit -m "feat: add database schema and admin seeder"
```

### Task 4: Authentication and Role Protection

**Files:**
- Create: `app/Repositories/UserRepository.php`
- Create: `app/Controllers/AuthController.php`
- Create: `app/Middleware/RequireAuth.php`
- Create: `app/Middleware/RequireRole.php`
- Create: `resources/views/auth/login.php`
- Modify: `app/Core/Auth.php`
- Modify: `bootstrap/app.php`
- Modify: `resources/views/layouts/app.php`

- [ ] **Step 1: Implement login**

`AuthController::login()` renders the form. `AuthController::authenticate()` normalizes username, fetches active user, validates `password_verify`, stores only user id/name/role in session, logs `login`, and redirects to `/dashboard`.

- [ ] **Step 2: Implement logout**

`AuthController::logout()` must log `logout`, destroy the session, regenerate the session id, and redirect to `/login`.

- [ ] **Step 3: Protect routes**

All routes except `/login` and POST `/login` must call `RequireAuth`. Admin-only pages must call `RequireRole('admin')`.

- [ ] **Step 4: Avoid repeated login logs**

Only POST `/login` may create a `login` activity log; page refreshes of `/dashboard` must not.

- [ ] **Step 5: Verify manually**

Open:

```text
http://localhost:8000/login
```

Expected: invalid credentials show Persian inline error, valid admin login reaches dashboard, logout returns to login, direct `/dashboard` while logged out redirects to login.

- [ ] **Step 6: Commit**

```bash
git add app/Controllers/AuthController.php app/Middleware app/Repositories/UserRepository.php app/Core/Auth.php resources/views/auth/login.php bootstrap/app.php
git commit -m "feat: add authentication and role guards"
```

## Milestone 3: Customers

### Task 5: Customer CRUD, Validation, Jalali Display

**Files:**
- Create: `app/Core/Validator.php`
- Create: `app/Core/Jalali.php`
- Create: `app/Models/Customer.php`
- Create: `app/Repositories/CustomerRepository.php`
- Create: `app/Services/CustomerService.php`
- Create: `app/Controllers/CustomerController.php`
- Create: `resources/views/customers/index.php`
- Create: `resources/views/customers/create.php`
- Create: `resources/views/customers/edit.php`
- Create: `resources/views/customers/show.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Implement validation helpers**

`Validator` must validate:

```php
national_code: normalize digits, exactly 10 ASCII digits, unique except current customer
phone_number: normalize digits, regex /^09\d{9}$/
birthday: required only when config app.birthday_required is true; otherwise nullable; accepted format YYYY-MM-DD
first_name/last_name: required, max 100 chars
```

- [ ] **Step 2: Implement customer create/edit/list/detail**

Customer create/edit must store Gregorian `birthday` as `DATE` or `NULL`, set `created_by`, and keep `wallet_balance` editable only by wallet services, not forms.

- [ ] **Step 3: Add search filters**

List query must support `q`, `national_code`, `first_name`, `last_name`, `phone_number`, `birthday`, `birthday_month`, and `birthday_day`. Birthday month/day filters use `MONTH(birthday)` and `DAY(birthday)`.

- [ ] **Step 4: Add Jalali display**

`Jalali::formatDate(?string $gregorianDate)` returns an escaped-display-ready Persian date string for list/detail pages. If implementing full conversion is too large for this task, include a tested pure-PHP Gregorian-to-Jalali conversion in `Jalali.php`; do not store Jalali dates in the database.

- [ ] **Step 5: Add CSV export**

`/customers/export` returns UTF-8 BOM CSV with first name, last name, national code, phone, birthday, wallet balance, created at. Log `report_export`.

- [ ] **Step 6: Verify manually**

Expected:

- Persian digits in national code and phone are accepted after normalization.
- Invalid national code, duplicate national code, invalid phone, and missing required birthday show inline errors.
- Detail page shows wallet, birthday, and empty purchase history area.
- CSV opens with readable Persian text.

- [ ] **Step 7: Commit**

```bash
git add app/Core/Validator.php app/Core/Jalali.php app/Models/Customer.php app/Repositories/CustomerRepository.php app/Services/CustomerService.php app/Controllers/CustomerController.php resources/views/customers bootstrap/app.php
git commit -m "feat: add customer management"
```

## Milestone 4: Purchases and Wallet

### Task 6: Purchase Creation with Single Cashback Credit

**Files:**
- Create: `app/Models/Purchase.php`
- Create: `app/Repositories/PurchaseRepository.php`
- Create: `app/Services/PurchaseService.php`
- Create: `app/Controllers/PurchaseController.php`
- Create: `resources/views/purchases/create.php`
- Modify: `resources/views/customers/show.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Implement transaction-safe purchase creation**

`PurchaseService::create($customerId, $amount, $createdBy)` must:

```php
if ($amount <= 0) reject with Persian validation error
cashback = round($amount * 0.05, 2)
begin transaction
insert purchase
update customers.wallet_balance = wallet_balance + cashback
insert wallet_transactions row with type cashback, positive amount, purchase_id, balance_after
commit
```

Never run wallet credit logic from a purchase edit path. If purchase editing is added later, it must not update wallet automatically.

- [ ] **Step 2: Add success message**

After create, flash:

```text
خرید ثبت شد و مبلغ {cashback_amount} ریال کش‌بک به کیف پول مشتری اضافه شد.
```

- [ ] **Step 3: Show purchase history**

Customer detail must list purchases with amount, cashback, operator, and created date.

- [ ] **Step 4: Verify manually**

Expected:

- Negative and zero purchase amounts are rejected.
- A 1,000,000 purchase adds exactly 50,000 cashback once.
- Refreshing the success page does not create another purchase or duplicate wallet credit.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Purchase.php app/Repositories/PurchaseRepository.php app/Services/PurchaseService.php app/Controllers/PurchaseController.php resources/views/purchases resources/views/customers/show.php bootstrap/app.php
git commit -m "feat: add purchases and cashback crediting"
```

### Task 7: Wallet Reductions and Transaction History

**Files:**
- Create: `app/Models/WalletTransaction.php`
- Create: `app/Repositories/WalletRepository.php`
- Create: `app/Services/WalletService.php`
- Create: `app/Controllers/WalletController.php`
- Create: `resources/views/wallet/reduce.php`
- Modify: `resources/views/customers/show.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Implement wallet reduction**

`WalletService::reduce($customerId, $amount, $reason, $createdBy)` must reject non-positive amounts, reject amounts greater than current wallet balance, update balance inside a transaction, and insert a `wallet_transactions` row with type `reduction`.

- [ ] **Step 2: Show wallet history**

Customer detail must show both cashback and reduction rows ordered newest first.

- [ ] **Step 3: Verify manually**

Expected:

- Reducing more than balance shows a Persian error.
- Successful reduction updates wallet and logs reason/operator/date.
- Wallet never becomes negative.

- [ ] **Step 4: Commit**

```bash
git add app/Models/WalletTransaction.php app/Repositories/WalletRepository.php app/Services/WalletService.php app/Controllers/WalletController.php resources/views/wallet resources/views/customers/show.php bootstrap/app.php
git commit -m "feat: add wallet reductions"
```

## Milestone 5: SMS and Cron

### Task 8: SMS Settings, Templates, Logs, ippanel Provider

**Files:**
- Create: `app/Models/SmsSettings.php`
- Create: `app/Models/SmsLog.php`
- Create: `app/Repositories/SmsRepository.php`
- Create: `app/Services/SmsTemplateRenderer.php`
- Create: `app/Services/IppanelSmsProvider.php`
- Create: `app/Services/SmsService.php`
- Create: `app/Controllers/SmsController.php`
- Create: `app/Controllers/Admin/SettingsController.php`
- Create: `resources/views/sms/logs.php`
- Create: `resources/views/admin/sms_settings.php`
- Modify: `app/Services/PurchaseService.php`
- Modify: `app/Services/WalletService.php`
- Modify: `app/Services/CustomerService.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Implement template rendering**

`SmsTemplateRenderer` must replace:

```text
{first_name}, {last_name}, {full_name}, {purchase_amount}, {cashback_amount}, {wallet_balance}, {birthday}, {date}, {company_name}
```

Missing values become empty strings. Money variables use `money()`.

- [ ] **Step 2: Implement ippanel provider**

`IppanelSmsProvider::send($phoneNumber, $message)` must read token and sender number from `sms_settings`, call ippanel through `curl`, return provider response text/status, and throw no fatal errors. Use a single method so the exact ippanel endpoint/payload can be adjusted in one place if account-specific API details differ.

- [ ] **Step 3: Implement SMS service**

`SmsService::sendEvent($eventType, $customer, $variables)` must:

- Check global and per-event enabled flags.
- Insert `sms_logs` as `pending`.
- Attempt send.
- Update status to `sent` with `sent_at` or `failed`.
- Log `sms_sent` or `sms_failed`.
- Return result without crashing customer, purchase, or wallet workflows.

- [ ] **Step 4: Trigger event SMS**

Trigger purchase SMS after purchase commit, wallet reduction SMS after wallet commit, and welcome SMS after customer creation if enabled.

- [ ] **Step 5: Admin settings page**

Admin can update token, sender number, enable flags, and all templates. Token field should not be displayed as plain text after save; show placeholder text and only update token when a new value is submitted.

- [ ] **Step 6: SMS logs page**

Show event type, customer, phone, status, sent date, created date, and provider response with filters for event/status/date.

- [ ] **Step 7: Verify manually**

Expected:

- Disabled SMS creates no external API call.
- Failed provider call logs failed status and does not block purchase creation.
- Admin-only settings page rejects operator access.

- [ ] **Step 8: Commit**

```bash
git add app/Models/SmsSettings.php app/Models/SmsLog.php app/Repositories/SmsRepository.php app/Services/SmsTemplateRenderer.php app/Services/IppanelSmsProvider.php app/Services/SmsService.php app/Controllers/SmsController.php app/Controllers/Admin/SettingsController.php resources/views/sms resources/views/admin/sms_settings.php app/Services/PurchaseService.php app/Services/WalletService.php app/Services/CustomerService.php bootstrap/app.php
git commit -m "feat: add ippanel sms integration"
```

### Task 9: Birthday SMS Cron

**Files:**
- Create: `cron/send_birthday_sms.php`
- Modify: `app/Repositories/CustomerRepository.php`
- Modify: `app/Repositories/SmsRepository.php`
- Modify: `app/Services/SmsService.php`
- Modify: `README.md`

- [ ] **Step 1: Add birthday customer query**

Repository method must return active customers whose `MONTH(birthday)` and `DAY(birthday)` equal today, excluding rows already present in `birthday_sms_history` for the current year.

- [ ] **Step 2: Add cron script**

`cron/send_birthday_sms.php` must:

```php
<?php
require __DIR__ . '/../bootstrap/app.php';

// Load settings, exit cleanly if birthday SMS disabled.
// For each birthday customer:
// - send birthday SMS through SmsService
// - insert birthday_sms_history with current Gregorian year
// - print one line per attempted customer
```

The script must be runnable as:

```bash
php cron/send_birthday_sms.php
```

- [ ] **Step 3: Verify duplicate prevention**

Run the cron twice on a test birthday customer.

Expected: first run attempts SMS and inserts history; second run prints that there are no eligible birthday customers and sends no duplicate.

- [ ] **Step 4: Commit**

```bash
git add cron/send_birthday_sms.php app/Repositories/CustomerRepository.php app/Repositories/SmsRepository.php app/Services/SmsService.php README.md
git commit -m "feat: add birthday sms cron"
```

## Milestone 6: Reports and Admin

### Task 10: Advanced Reports and CSV Export

**Files:**
- Create: `app/Repositories/ReportRepository.php`
- Create: `app/Controllers/ReportController.php`
- Create: `resources/views/reports/index.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Add filters**

Support query filters:

```text
date_from, date_to, first_name, last_name, national_code, phone_number, birthday, birthday_month, purchase_min, purchase_max, cashback_min, cashback_max, created_by, report_type
```

Use prepared statements for every filter.

- [ ] **Step 2: Add summary cards**

Reports page must display total customers, total purchases, total purchase amount, total cashback, average cashback, and total wallet balances.

- [ ] **Step 3: Add report sections**

Implement:

- Top 10 customers by total purchase amount.
- Top 10 customers by cashback earned.
- Customers with birthdays today.
- Customers with birthdays this week.
- Customers with birthdays this month.
- Purchases filtered by date and other filters.

- [ ] **Step 4: Add CSV export**

`/reports/export` outputs filtered rows as UTF-8 BOM CSV and logs `report_export`.

- [ ] **Step 5: Verify manually**

Expected:

- Filters combine correctly.
- Empty results render clean Persian messages.
- CSV export respects current filters.

- [ ] **Step 6: Commit**

```bash
git add app/Repositories/ReportRepository.php app/Controllers/ReportController.php resources/views/reports bootstrap/app.php
git commit -m "feat: add reports and exports"
```

### Task 11: Operator Management and Activity Logs

**Files:**
- Create: `app/Models/User.php`
- Create: `app/Models/ActivityLog.php`
- Create: `app/Repositories/ActivityLogRepository.php`
- Create: `app/Services/ActivityLogger.php`
- Create: `app/Controllers/Admin/UserController.php`
- Create: `app/Controllers/Admin/ActivityLogController.php`
- Create: `resources/views/admin/users/index.php`
- Create: `resources/views/admin/users/create.php`
- Create: `resources/views/admin/users/edit.php`
- Create: `resources/views/admin/activity_logs.php`
- Modify: existing controllers/services to call `ActivityLogger`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Implement activity logger**

Every required event must be logged with `user_id`, `activity_type`, `description`, optional `customer_id`, request IP, and current datetime:

```text
login, logout, customer_create, customer_edit, purchase_create, wallet_reduction, operator_create, operator_edit, sms_sent, sms_failed, report_export
```

- [ ] **Step 2: Implement operator CRUD**

Admin can create/edit active status for operator users. Password field on edit is optional; only update hash if a new password is provided.

- [ ] **Step 3: Enforce access**

Operators cannot access `/admin/users`, `/admin/activity-logs`, or `/admin/sms-settings`. Admin can access all.

- [ ] **Step 4: Add activity log filters**

Filter by date range, user, activity type, customer name, and national code.

- [ ] **Step 5: Verify manually**

Expected:

- Operator cannot manage operators or SMS settings.
- Admin can disable an operator.
- Disabled operator cannot log in.
- Activity log contains all workflow actions.

- [ ] **Step 6: Commit**

```bash
git add app/Models/User.php app/Models/ActivityLog.php app/Repositories/ActivityLogRepository.php app/Services/ActivityLogger.php app/Controllers/Admin/UserController.php app/Controllers/Admin/ActivityLogController.php resources/views/admin bootstrap/app.php
git commit -m "feat: add admin operator and activity log management"
```

## Milestone 7: UI Polish, Security, Deployment Docs

### Task 12: Dashboard and Persian UI Polish

**Files:**
- Create: `app/Controllers/DashboardController.php`
- Create: `resources/views/dashboard/index.php`
- Modify: `resources/views/layouts/app.php`
- Modify: `public/assets/css/app.css`
- Modify: all form/list views

- [ ] **Step 1: Add dashboard**

Dashboard shows compact stats: customers count, purchases count, total purchase amount, total cashback, total wallet balance, today's birthdays, and quick links.

- [ ] **Step 2: Polish RTL UI**

Ensure:

- `body` uses a Persian-friendly font stack.
- All forms show inline validation errors.
- Success/error flash messages are visible.
- Money values use thousands separators.
- Dates use Jalali display where appropriate.
- Navigation labels match the requested Persian interface.

- [ ] **Step 3: Verify mobile layout**

Check dashboard, customer list, customer detail, purchase form, reports, SMS settings, and admin users on a narrow viewport. Expected: no overlapping text and forms remain usable.

- [ ] **Step 4: Commit**

```bash
git add app/Controllers/DashboardController.php resources/views/dashboard resources/views public/assets/css/app.css
git commit -m "feat: polish dashboard and rtl interface"
```

### Task 13: Security Pass

**Files:**
- Modify: `app/Core/Auth.php`
- Modify: `app/Core/Csrf.php`
- Modify: `app/Core/Database.php`
- Modify: all controllers with POST handlers
- Modify: all templates

- [ ] **Step 1: Escape output**

Audit every `<?= ... ?>` in `resources/views`. All user-controlled values must use `e()`. Only trusted HTML snippets such as rendered flash containers may be unescaped.

- [ ] **Step 2: Enforce CSRF**

Every POST action must call `Csrf::validate()` before mutating data. Failed CSRF redirects with Persian error:

```text
درخواست نامعتبر است. لطفاً دوباره تلاش کنید.
```

- [ ] **Step 3: Harden sessions**

On login call `session_regenerate_id(true)`. Set cookie options `httponly`, `samesite=Lax`, and `secure` when HTTPS is active.

- [ ] **Step 4: Verify prepared statements**

Audit repositories. No SQL may include raw request values; every dynamic value must be bound through PDO placeholders.

- [ ] **Step 5: Commit**

```bash
git add app resources/views
git commit -m "chore: harden security controls"
```

### Task 14: Deployment Guide and Manual Test Checklist

**Files:**
- Create: `tests/manual-test-checklist.md`
- Modify: `README.md`
- Modify: `config/config.example.php`

- [ ] **Step 1: README installation instructions**

README must include:

- PHP 8.1+/8.2+ requirement.
- MySQL/MariaDB requirement.
- Upload contents to `public_html` with `public/index.php` as entrypoint.
- Alternative `.htaccess`/document-root guidance if host cannot point to `public`.
- Import `database/schema.sql`.
- Copy `config/config.example.php` to `config/config.php`.
- Fill database credentials.
- Create admin using `php database/seed_admin.php admin "password" "مدیر سیستم"`.
- Configure ippanel in Admin Settings.
- cPanel cron command:

```bash
/usr/local/bin/php /home/CPANEL_USER/public_html/cron/send_birthday_sms.php
```

- Troubleshooting for database connection, SMS failures, file paths, and timezone.

- [ ] **Step 2: Manual checklist**

`tests/manual-test-checklist.md` must cover login/logout, roles, customer CRUD, validation, CSV export, purchase cashback, wallet reduction, reports, SMS disabled/enabled behavior, birthday cron duplicate prevention, activity logs, and deployment smoke test.

- [ ] **Step 3: Commit**

```bash
git add README.md tests/manual-test-checklist.md config/config.example.php
git commit -m "docs: add deployment and test checklist"
```

## Final Acceptance Checklist

- [ ] App runs with `php -S localhost:8000 -t public`.
- [ ] App can be deployed by upload plus SQL import.
- [ ] No Node.js build step exists.
- [ ] All pages except login require authentication.
- [ ] Admin/operator role boundaries work.
- [ ] Passwords use `password_hash` and `password_verify`.
- [ ] National code and Iranian mobile validation work after Persian/Arabic digit normalization.
- [ ] Birthday storage is Gregorian `DATE`; display is Persian/Jalali.
- [ ] Purchase amount rejects zero/negative values.
- [ ] Cashback is 5% and credited once per created purchase.
- [ ] Wallet reduction cannot exceed balance.
- [ ] SMS failures are logged and never crash business workflows.
- [ ] Birthday cron prevents duplicate birthday SMS for the same customer/year.
- [ ] Activity log contains all required activity types.
- [ ] CSV exports include UTF-8 BOM and readable Persian text.
- [ ] README includes cPanel deployment and cron instructions.

## Suggested Implementation Order

1. Milestone 1: Bootstrap and layout.
2. Milestone 2: Schema and auth.
3. Milestone 3: Customers.
4. Milestone 4: Purchases and wallet.
5. Milestone 5: SMS and cron.
6. Milestone 6: Reports and admin.
7. Milestone 7: UI, security, deployment docs.

This order keeps the app runnable after each milestone and avoids building SMS/reporting before the customer, purchase, and wallet data model is stable.

