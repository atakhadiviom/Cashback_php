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
- Confirm national code must be 10 digits, company national ID can be 11 digits, and the identifier is unique.
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
