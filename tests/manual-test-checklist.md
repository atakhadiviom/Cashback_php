# Manual Test Checklist

## Auth and Roles

- Log in with the seeded admin user.
- Confirm invalid credentials show a Persian error.
- Confirm logout returns to `/login`.
- Create an operator, log in as operator, and confirm admin pages are blocked.
- Disable an operator and confirm the operator cannot log in.
- Confirm login lockout after repeated failed attempts.
- Confirm operator permissions (e.g. void purchase) respect checkboxes on user form.

## Customers

- Create a customer with Persian digits in national code and phone.
- Confirm only name and phone are mandatory; national code can be empty, 10 digits, or an 11-digit company ID and is unique when provided.
- Confirm Iranian phone must match `09xxxxxxxxx`.
- Confirm birthday is stored as Gregorian `DATE` and displayed as Jalali.
- Search by name, national code, phone, birthday date, birthday month, and birthday day.
- Export customers to CSV and confirm Persian text opens correctly.
- Import customers via admin CSV and XLSX (preview then import).
- Invalid national code checksum is rejected.

## Purchases and Wallet

- Change cashback percent in admin settings and confirm new purchases use it.
- Register a positive purchase and confirm cashback matches configured percent.
- Duplicate invoice_ref is rejected; similar purchase within window shows confirm checkbox.
- Admin can void a purchase; wallet decreases and purchase shows voided.
- Try zero and negative purchase amounts and confirm they are rejected.
- Refresh after successful purchase and confirm no duplicate cashback appears.
- Reduce wallet by a valid amount and confirm the balance decreases.
- Try reducing more than wallet balance and confirm it is rejected.
- Confirm customer detail shows lifetime earned vs wallet balance.
- Redemption below min or above percent cap is rejected.

## Reports

- Filter reports by date, customer fields, purchase amount range, cashback range, birthday month, and operator.
- Confirm summary cards, top 10 purchase customers, top 10 cashback customers, birthday lists, and filtered purchases render.
- Export reports to CSV.
- Liability issued vs redeemed and inactive customers sections render.

## Due Dates (Payment Deadlines)

- Run migration `018_payment_due_dates.sql` via `php database/migrate.php`.
- Register a due date with customer, Jalali due date, amount, type (check/installment/invoice), and status.
- Link an invoice from purchases (`invoice_ref`) and confirm reference number auto-fills.
- Filter list by customer name, reference number, amount, date range, status, and quick scopes (today, next 7 days, overdue).
- Edit and delete a due date; confirm activity log entries.
- Export CSV and open print view (save as PDF from browser).
- With SMS enabled, confirm registration SMS is sent once; run `php cron/run.php due_date_reminders` for reminder cron.
- Dashboard cards show today/tomorrow/overdue counts and amounts with links to filtered lists.

## Portal and API

- Customer portal OTP flow at `/portal` shows balance after verify.
- Create API key; `POST /api/v1/purchases` with `X-Api-Key` succeeds; idempotency key prevents duplicate.

## SMS and Cron

- Leave SMS disabled and confirm purchase/customer flows do not fail.
- Enable SMS with invalid ippanel credentials and confirm failures are logged but workflows continue.
- Configure valid ippanel settings and send purchase/welcome/wallet messages.
- Set a test customer birthday to today, run `php cron/send_birthday_sms.php`, then run it again.
- Confirm the second cron run does not send a duplicate for the same year.
- Run `php cron/retry_failed_sms.php` after a failed send and confirm retry.

## Deployment Smoke Test

- Import `database/schema.sql` into MySQL/MariaDB.
- Copy `config/config.example.php` to `config/config.php` and set credentials.
- Run `php database/seed_admin.php admin "StrongPassword" "مدیر سیستم"`.
- Upload files to cPanel and point the web root to `public`.
- Run `php database/migrate.php` on upgraded installs.
- Configure daily cPanel cron for `cron/send_birthday_sms.php` and retry cron for `cron/retry_failed_sms.php`.
- Run `composer install` and `vendor/bin/phpunit` for automated unit tests.

## CRM (Phases 1-5)

- Create a customer and register a follow-up with status "در حال مذاکره".
- Add next contact date + reminder time; verify a reminder appears in /reminders.
- Mark reminder as seen, then done; confirm status changes and activity log entries.
- Finalize a follow-up as "فروش نهایی" and confirm:
  - A purchase is auto-created with the invoice amount.
  - Customer tier is recalculated based on lifetime spend.
  - Activity log shows "followup_won" and "purchase_create".
- Finalize a follow-up as "لغو فروش" and confirm lost_reason is required and recorded.
- View /reports/crm with filters (operator, status, date range, tier); verify counts and list.
- Export CRM report to Excel (CSV) and confirm Persian text and numbers.
- Print CRM report and verify layout (no login required for the print view).
- In /admin/loyalty, add a new tier with min/max lifetime spend and verify it appears in reports and tier recalculation.
- Run `php database/migrate.php` and confirm migrations 013-016 are applied without error.

## Cashback Expiry (Migration 019)

- Run `php database/migrate.php` and confirm `019_cashback_expiry.sql` applies without error; run it a second time and confirm nothing changes.
- Confirm the shipped default is «مدت اعتبار کش‌بک» = 0, i.e. nothing expires until an admin sets a period.
- After the migration, confirm every customer with a positive wallet balance has one `opening` lot in `cashback_lots`; with the default of 0 its `expires_at` is NULL.
- On an install that already ran 019 with the old 12-month default, confirm `020_cashback_expiry_default_off.sql` resets the setting to 0 and clears `expires_at`/`warned_at` on existing lots — and that an install where an admin already chose a period keeps that choice.
- In /admin/cashback-settings set "مدت اعتبار کش‌بک" and "ارسال هشدار چند روز قبل از انقضا"; save and confirm both values persist.
- Register a purchase and confirm a new lot appears with `expires_at` = now + configured months.
- Use part of the wallet and confirm the soonest-to-expire lot is drained first (`consumed_amount`), not the newest one.
- Confirm `wallet_balance` always equals `SUM(amount - consumed_amount - expired_amount)` for that customer.
- Void a purchase whose cashback is unspent, and separately one whose cashback was already spent; confirm the invariant above still holds in both cases.
- Set a lot's `expires_at` to yesterday, run `php cron/run.php cashback_expiry`, and confirm: only the unspent part is deducted, a wallet transaction of type «انقضا» is written, and the activity log records `cashback_expiry`.
- Run the same cron again and confirm nothing is deducted twice.
- Enable «هشدار انقضای کش‌بک» in /admin/sms-settings, set a lot to expire inside the warning window, run the cron, and confirm one SMS is logged with the correct amount and Jalali date.
- Run the cron again and confirm no duplicate warning is sent for the same expiry month.
- Set "مدت اعتبار کش‌بک" to 0, register a purchase, and confirm the new lot has no expiry date and is never touched by the expiry cron.
- Confirm /admin/system-status lists the «انقضای کش‌بک و هشدار آن» cron row and that the schema health check for `cashback_lots` passes.
