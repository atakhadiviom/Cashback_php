# سامانه پیگیری فروش و باشگاه مشتریان و کش بک

یک برنامه PHP/MySQL برای مدیریت مشتریان، خریدها، کش‌بک، کیف پول، گزارش‌ها، پیامک ippanel و لاگ فعالیت‌ها. برنامه بدون Node.js و بدون نیاز به daemon اجرا می‌شود و برای shared hosting/cPanel طراحی شده است.

## تغییرات نسخه ۱.۱.۲۹

- **نام سامانه قابل ویرایش**: فیلد «نام سامانه» در `تنظیمات کش‌بک` (`/admin/cashback-settings`). این نام در نوار کناری، عنوان مرورگر، سربرگ چاپ و صفحهٔ ورود نمایش داده می‌شود.
- نام پیش‌فرض روی «سامانه پیگیری فروش و باشگاه مشتریان و کش بک» تنظیم شد (مهاجرت `030`، ستون `cashback_settings.panel_title`).
- اگر فیلد را خالی بگذاری، نام از فایل تنظیمات (`cashback_config.php`) خوانده می‌شود؛ پس دیگر برای تغییر نام نیازی به دست‌زدن به فایل نیست.

## تغییرات نسخه ۱.۱.۲۸

- **آموزش درون‌برنامه‌ای با مثال**: صفحهٔ `/guide` (منوی «آموزش و مثال‌ها») شامل ۹ بخش: شروع سریع باشگاه، سطح‌بندی خودکار، بخش‌بندی RFM با مثال امتیاز، یادآوری زمان‌بندی‌شده با نمونه تاریخ/ساعت، کمپین گروهی، جشنواره و تخفیف ویژه با نمونهٔ کامل، تنظیم پیامک‌ها، نمونه‌های curl برای API و عیب‌یابی.
- لینک «آموزش با مثال» در داشبورد باشگاه و صفحهٔ جشنواره‌ها.

## تغییرات نسخه ۱.۱.۲۷

- **پیامک شارژ کیف پول**: رویداد جدید `wallet_credit` با کلید روشن/خاموش و قالب جداگانه در `تنظیمات پیامک`؛ هنگام شارژ کیف پول (از پنل یا API) پیامک ارسال می‌شود.
- **یک‌جا دیدن همهٔ پیامک‌ها**: کارت «پیامک‌های باشگاه مشتریان» در صفحهٔ تنظیمات پیامک با لینک به تغییر سطح، یادآوری‌ها/کمپین، جشنواره‌ها و لاگ پیامک.
- مهاجرت `029` (دو ستون جدید در `sms_settings`؛ پیش‌فرض خاموش تا ناخواسته پیامک نرود).
- همهٔ رویدادهای دیگر از قبل پیامک داشتند: خرید، ابطال خرید، تولد، خوشامد (ثبت مشتری)، کسر کیف پول، ثبت سرویس، تمدید قرارداد، سررسید و یادآوری آن، انقضای کش‌بک، رفرال، تغییر سطح، یادآوری‌های باشگاه و جشنواره‌ها.

## تغییرات نسخه ۱.۱.۲۶

- **جشنواره‌ها و تخفیفات ویژه**: ساخت جشنواره با بازهٔ شروع/پایان، متن و درصد و کد تخفیف، انتخاب مخاطب (همه مشتریان، اعضای باشگاه، دریافت‌کنندگان یادآوری، یک بخش RFM، یک سطح، یک برچسب)، پیش‌نمایش تعداد گیرنده، ارسال فوری یا زمان‌بندی‌شده، ارسال دسته‌بندی‌شده و قابل ادامه با کرون `festival_send`، و جلوگیری از ارسال تکراری برای هر مشتری.
- صفحهٔ مدیریت: `مدیریت ← جشنواره و تخفیف ویژه` (`/admin/festivals`).
- API: `GET/POST /api/v1/festivals`, `GET/PUT/DELETE /api/v1/festivals/{id}`, `POST /api/v1/festivals/{id}/send`.
- مهاجرت `028` (دو جدول جدید `club_festivals` و `club_festival_recipients`).

## تغییرات نسخه ۱.۱.۲۵

- **باشگاه مشتریان**: بخش‌بندی RFM با ۱۰ بخش، برچسب مشتری، داشبورد باشگاه، سطح‌بندی خودکار بعد از خرید/ابطال با تاریخچه و مهلت تنزل، یادآوری زمان‌بندی‌شده با پیامک + صف + کمپین + کرون (`club_plan`, `club_reminders`, `tier_refresh`) و شش گزارش با خروجی CSV.
- **API کامل (خواندن و نوشتن)**: مشتریان، خریدها، کیف پول (شامل شارژ)، سطح‌ها، بخش‌بندی، برچسب‌ها، گزارش‌ها و یادآوری‌ها؛ هر کلید API دسترسی «خواندن/نوشتن» مستقل دارد و از همان صفحهٔ کلیدها قابل تغییر است.
- **مهاجرت‌ها**: `023` تا `027` — فقط جدول/ستون جدید، بدون تغییر در داده‌های موجود.
- مستندات: [`docs/api-v1.md`](docs/api-v1.md)

## نیازمندی‌ها

- PHP 8.1 یا PHP 8.2 به بالا
- MySQL یا MariaDB
- افزونه‌های PHP: `pdo_mysql`, `curl`, `mbstring`
- برای آپدیت خودکار از GitHub: افزونه‌های `curl` و `zip`
- امکان اجرای cron در cPanel

## نصب محلی

```bash
cp config/config.example.php config/config.php
```

اطلاعات دیتابیس را در `config/config.php` وارد کنید، سپس SQL را import کنید. برای نصب روی هاست بهتر است از نصب‌کننده مرورگری استفاده کنید تا فایل پایدار `../cashback_config.php` ساخته شود.

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
3. اگر هاست فقط `public_html` دارد (یا برای ساب‌دامین اجازه تغییر docroot ندارید)، کل پروژه را در docroot قرار دهید. برنامه هم در حالت **Rewrite فعال** و هم در حالت **Rewrite غیرفعال** قابل استفاده است:
   - Rewrite فعال (Apache/LiteSpeed با `.htaccess`): آدرس‌های تمیز مثل `/login` کار می‌کند.
   - Rewrite غیرفعال (Nginx-only یا AllowOverride بسته): برنامه را از مسیر `/public/...` باز کنید (مثلاً `/public/login`) و در نصب‌کننده Base URL را روی `/public` بگذارید.
4. قبل از نصب، پوشه‌های `config` و `storage` باید برای PHP قابل نوشتن باشند. همچنین نصب‌کننده سعی می‌کند فایل‌های زیر را در **ریشه پروژه (مثل WordPress)** بسازد:
   - `cashback_config.php`
   - `cashback_installed.lock`
   اگر روی هاست شما این مسیرها قابل نوشتن نبود، نصب‌کننده خودکار به مسیرهای جایگزین (`config/config.php` و `storage/installed.lock`) برمی‌گردد.
5. در مرورگر آدرس زیر را باز کنید:

```text
https://YOUR_DOMAIN/install.php
```

6. اطلاعات دیتابیس، نام شرکت، تنظیمات برنامه و مدیر اولیه را وارد کنید. نام شرکت در عنوان برنامه و قالب‌های پیامک استفاده می‌شود.
7. اگر آدرس‌های شما شامل `/public` است، در فرم نصب **Base URL = `/public`** را تنظیم کنید (در غیر این صورت خالی بگذارید).
8. بعد از نصب وارد `/login` شوید (یا اگر Rewrite غیرفعال است: `/public/login`).
9. بعد از نصب وارد `/login` شوید.
10. برای تولید، فایل `public/install.php` را حذف کنید یا حداقل مطمئن شوید فایل قفل نصب وجود دارد.
11. بعد از نصب، سطح دسترسی فایل تنظیمات را تا حد امکان محدود کنید؛ معمولاً `0640` یا `0600` اگر هاست اجازه بدهد.

اگر نمی‌خواهید از نصب‌کننده مرورگری استفاده کنید، نصب دستی هم ممکن است:

1. در MySQL Database Wizard یک دیتابیس و یوزر بسازید.
2. فایل `database/schema.sql` را از phpMyAdmin import کنید.
3. `config/config.example.php` را به `config/config.php` کپی کنید و مشخصات دیتابیس را وارد کنید.
4. ادمین اولیه را با SSH یا Terminal cPanel بسازید:

```bash
php database/seed_admin.php admin "StrongPassword123" "مدیر سیستم"
```

برای اجرای دوباره نصب‌کننده باید فایل قفل نصب را دستی حذف کنید: `../cashback_installed.lock`. اگر از نسخه قدیمی استفاده کرده بودید، ممکن است فایل قدیمی `storage/installed.lock` هم وجود داشته باشد. این کار فقط زمانی انجام شود که مطمئن هستید می‌خواهید نصب را از نو انجام دهید.

## فایل‌های حساس

فایل‌های زیر نباید در مخزن عمومی یا بکاپ قابل دانلود وب قرار بگیرند:

```text
cashback_config.php
cashback_installed.lock
../cashback_config.php
../cashback_installed.lock
config/config.php
storage/installed.lock
```

در نسخه‌های جدید، نصب‌کننده تنظیمات و قفل نصب را بیرون از پوشه برنامه ذخیره می‌کند تا هنگام آپلود ZIP جدید یا جایگزینی فایل‌های برنامه حذف نشوند. فایل‌های قدیمی داخل پروژه فقط برای مهاجرت خوانده می‌شوند و در `.gitignore` قرار دارند. پوشه‌های خصوصی با `.htaccess` محافظت شده‌اند.

## روش امن آپدیت با ZIP

اگر برنامه قبلاً نصب شده است:

1. فایل‌های `../cashback_config.php` و `../cashback_installed.lock` را نگه دارید و حذف نکنید.
2. ZIP جدید را داخل پوشه برنامه extract کنید.
3. اگر هاست شما از فایل‌های قدیمی داخل پروژه استفاده می‌کرد، قبل از آپدیت از `config/config.php` و `storage/installed.lock` بکاپ بگیرید.
4. بعد از آپدیت `/login` را باز کنید. نصب‌کننده نباید باز شود.

## آپدیت خودکار از GitHub

ادمین می‌تواند از مسیر `مدیریت > به‌روزرسانی` آخرین نسخه شاخه `main` را از GitHub دریافت و نصب کند. این قابلیت روی هاست‌های اشتراکی cPanel/DirectAdmin بدون نیاز به SSH کار می‌کند، ولی باید افزونه‌های PHP `curl` و `zip` فعال باشند.

برای فعال‌سازی، در فایل تنظیمات پایدار هاست (`../cashback_config.php` یا `cashback_config.php`) بخش زیر را اضافه یا ویرایش کنید:

```php
'updater' => [
    'enabled' => true,
    'github_owner' => 'atakhadiviom',
    'github_repo' => 'Cashback_php',
    'branch' => 'main',
    'github_token' => '',
],
```

اگر مخزن خصوصی است، یک GitHub token با دسترسی خواندن مخزن در `github_token` قرار دهید. آپدیتر قبل از جایگزینی فایل‌ها در `storage/backups` بکاپ می‌سازد و مسیرهای محلی مثل `cashback_config.php`، `config/config.php`، `storage`، `.env`، `.git` و `vendor` را overwrite نمی‌کند.

## Cron

در cPanel > Cron Jobs (مسیر پروژه: `/home/CPANEL_USER/cashback.persiannetco.ir`):

| کار | زمان‌بندی | دستور |
|-----|-----------|--------|
| پیامک تولد | روزانه ۰۸:۰۰ | `/usr/local/bin/ea-php84 /home/CPANEL_USER/cashback.persiannetco.ir/cron/run.php birthday` |
| یادآوری تمدید قرارداد | روزانه ۰۹:۰۰ | `/usr/local/bin/ea-php84 /home/CPANEL_USER/cashback.persiannetco.ir/cron/run.php contract_renewal` |
| تلاش مجدد پیامک | هر ۱۵ دقیقه | `/usr/local/bin/ea-php84 /home/CPANEL_USER/cashback.persiannetco.ir/cron/run.php sms_retry` |

همه کارها با یک دستور: `.../cron/run.php all`

اگر `ea-php84` روی هاست شما نیست، از MultiPHP Manager نسخه PHP را ببینید (مثلاً `ea-php81`) یا از پشتیبانی مسیر `php` CLI را بگیرید.

**بدون cron سرور:** با باز کردن داشبورد توسط مدیر، کارهای روزانه خودکار اجرا می‌شوند. همچنین می‌توانید `cron.web_token` را در `cashback_config.php` تنظیم کنید و آدرس `/internal/cron?task=all&token=...` را در cron-job.org ثبت کنید.

## ارتقاء دیتابیس (نصب قبلی)

```bash
php database/migrate.php
```

## پرتال مشتری

آدرس عمومی (بدون ورود کارمند): `/portal` — مشتری با OTP پیامکی موجودی کیف پول را می‌بیند.

## API (خواندن و نوشتن)

هدرها:

```text
X-API-Key: PREFIX.SECRET
X-Idempotency-Key: unique-key-optional
Content-Type: application/json
```

کلید از منوی **کلید API** (مدیر) ساخته می‌شود و هر کلید `scopes` دارد: `read,write` (پیش‌فرض)، `read` یا `*`.

پوشش کامل: مشتریان (فهرست/جزئیات/ایجاد/ویرایش/حذف/برچسب/کیف پول/خریدها)، خریدها (فهرست/جزئیات/ثبت/ابطال)، کیف پول (موجودی/تراکنش/کسر/شارژ)، سطح‌ها و بخش‌بندی RFM، برچسب‌ها، گزارش‌ها و یادآوری‌ها.

نمونه‌ها:

```text
GET    /api/v1/health
GET    /api/v1/customers?q=احمدی&limit=20
POST   /api/v1/customers
PUT    /api/v1/customers/12
GET    /api/v1/purchases?customer_id=12&date_from=1403/01/01
POST   /api/v1/purchases        {"customer_id":1,"amount":1000000}
POST   /api/v1/purchases/55/void {"reason":"اشتباه ثبت شد"}
GET    /api/v1/wallet/12
POST   /api/v1/wallet/credit     {"customer_id":12,"amount":500000,"reason":"هدیه"}
POST   /api/v1/wallet/reduce     {"customer_id":12,"amount":50000,"reason":"پرداخت"}
GET    /api/v1/segments
POST   /api/v1/segments/refresh
GET    /api/v1/reports/summary
GET    /api/v1/reminders/rules
POST   /api/v1/reminders/dispatch {"limit":100}
```

مسیرهای قدیمی (`POST /api/v1/purchases`, `GET /api/v1/customers/by-phone`, `POST /api/v1/wallet/reduce`) سازگار مانده‌اند. مستند کامل: [`docs/api-v1.md`](docs/api-v1.md)

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

- خطای دیتابیس: مقادیر `../cashback_config.php` و دسترسی user دیتابیس را بررسی کنید. اگر از نسخه قدیمی مهاجرت نکرده‌اید، ممکن است تنظیمات قبلی در `config/config.php` باشد.
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
