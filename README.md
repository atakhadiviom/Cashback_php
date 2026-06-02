# سیستم مدیریت کش بک نوآوران زیبایی

یک برنامه PHP/MySQL برای مدیریت مشتریان، خریدها، کش‌بک، کیف پول، گزارش‌ها، پیامک ippanel و لاگ فعالیت‌ها. برنامه بدون Node.js و بدون نیاز به daemon اجرا می‌شود و برای shared hosting/cPanel طراحی شده است.

## نیازمندی‌ها

- PHP 8.1 یا PHP 8.2 به بالا
- MySQL یا MariaDB
- افزونه‌های PHP: `pdo_mysql`, `curl`, `mbstring`
- امکان اجرای cron در cPanel

## نصب محلی

```bash
cp config/config.example.php config/config.php
```

اطلاعات دیتابیس را در `config/config.php` وارد کنید، سپس SQL را import کنید:

```bash
mysql -u USER -p DATABASE_NAME < database/schema.sql
php database/seed_admin.php admin "StrongPassword123" "مدیر سیستم"
php -S localhost:8000 -t public public/index.php
```

آدرس ورود:

```text
http://localhost:8000/login
```

## نصب روی cPanel

1. فایل‌ها را در هاست آپلود کنید.
2. بهترین حالت این است که document root دامنه روی پوشه `public` تنظیم شود.
3. اگر هاست فقط `public_html` دارد، کل پروژه را در `public_html` قرار دهید. فایل `.htaccess` ریشه درخواست‌ها را به `public/index.php` هدایت و دسترسی مستقیم به پوشه‌های خصوصی را مسدود می‌کند.
4. قبل از نصب، پوشه‌های `config` و `storage` باید برای PHP قابل نوشتن باشند.
5. در مرورگر آدرس زیر را باز کنید:

```text
https://YOUR_DOMAIN/install.php
```

6. اطلاعات دیتابیس، نام شرکت، تنظیمات برنامه و مدیر اولیه را وارد کنید. نام شرکت در عنوان برنامه و قالب‌های پیامک استفاده می‌شود.
7. نصب‌کننده فایل `config/config.php` را با عنوان اختصاصی برنامه می‌سازد، SQL را import می‌کند، مدیر اولیه را ایجاد می‌کند و فایل `storage/installed.lock` را می‌سازد.
8. بعد از نصب وارد `/login` شوید.
9. برای تولید، فایل `public/install.php` را حذف کنید یا حداقل مطمئن شوید فایل `storage/installed.lock` وجود دارد.
10. بعد از نصب، سطح دسترسی `config/config.php` را تا حد امکان محدود کنید؛ معمولاً `0640` یا `0600` اگر هاست اجازه بدهد.

اگر نمی‌خواهید از نصب‌کننده مرورگری استفاده کنید، نصب دستی هم ممکن است:

1. در MySQL Database Wizard یک دیتابیس و یوزر بسازید.
2. فایل `database/schema.sql` را از phpMyAdmin import کنید.
3. `config/config.example.php` را به `config/config.php` کپی کنید و مشخصات دیتابیس را وارد کنید.
4. ادمین اولیه را با SSH یا Terminal cPanel بسازید:

```bash
php database/seed_admin.php admin "StrongPassword123" "مدیر سیستم"
```

برای اجرای دوباره نصب‌کننده باید فایل `storage/installed.lock` را دستی حذف کنید. این کار فقط زمانی انجام شود که مطمئن هستید می‌خواهید نصب را از نو انجام دهید.

## فایل‌های حساس

فایل‌های زیر نباید در مخزن عمومی یا بکاپ قابل دانلود وب قرار بگیرند:

```text
config/config.php
storage/installed.lock
```

این فایل‌ها در `.gitignore` قرار داده شده‌اند. پوشه‌های خصوصی نیز با `.htaccess` محافظت شده‌اند.

## Cron تولد

در cPanel > Cron Jobs یک job روزانه بسازید:

```bash
/usr/local/bin/php /home/CPANEL_USER/public_html/cron/send_birthday_sms.php
```

اگر مسیر PHP متفاوت است، از پشتیبانی هاست مسیر درست را بگیرید.

## بکاپ پیشنهادی

حداقل روزانه از دیتابیس بکاپ بگیرید. نمونه دستور در cPanel Terminal:

```bash
mysqldump -u DB_USER -p DB_NAME > ~/backups/cashback-$(date +%F).sql
```

پوشه بکاپ نباید داخل مسیر عمومی وب باشد.

## تنظیم ippanel

پس از ورود با ادمین:

1. وارد `تنظیمات پیامک` شوید.
2. توکن API و شماره فرستنده را وارد کنید.
3. فعال‌سازی کلی پیامک و رویدادهای مورد نیاز را روشن کنید.
4. قالب‌ها از متغیرهای زیر پشتیبانی می‌کنند:

```text
{first_name} {last_name} {full_name} {purchase_amount} {cashback_amount} {wallet_balance} {birthday} {date} {company_name}
```

خطای SMS باعث توقف ثبت مشتری، خرید یا کیف پول نمی‌شود و در `لاگ پیامک` ثبت می‌شود.

## نکات امنیتی

- همه queryها با PDO prepared statements اجرا می‌شوند.
- فرم‌های POST دارای CSRF token هستند.
- خروجی‌های UI escape می‌شوند.
- رمزها با `password_hash` ذخیره و با `password_verify` بررسی می‌شوند.
- صفحات به جز login نیاز به ورود دارند.
- صفحات مدیریت کاربران، تنظیمات پیامک و لاگ فعالیت‌ها فقط برای admin هستند.

## عیب‌یابی

- خطای دیتابیس: مقادیر `config/config.php` و دسترسی user دیتابیس را بررسی کنید.
- صفحه سفید: `app.debug` را موقتاً `true` کنید و error log هاست را ببینید.
- SMS ارسال نمی‌شود: فعال بودن SMS، توکن، sender number و لاگ‌های `sms_logs` را بررسی کنید.
- cron اجرا نمی‌شود: مسیر PHP و مسیر کامل فایل cron را در cPanel بررسی کنید.

## چک نهایی قبل از تولید

- نصب روی PHP و MySQL واقعی هاست تست شود.
- ورود ادمین، ایجاد اپراتور، ایجاد مشتری، ثبت خرید، کسر کیف پول، گزارش‌ها و خروجی CSV تست شود.
- ارسال پیامک با توکن و شماره فرستنده واقعی ippanel تست شود.
- cron تولد یک بار دستی اجرا و لاگ آن بررسی شود.
- `public/install.php` حذف یا قفل نصب بررسی شود.
- دسترسی مستقیم به `config/`, `database/`, `app/`, `storage/` در مرورگر باید 403 یا 404 بدهد.
