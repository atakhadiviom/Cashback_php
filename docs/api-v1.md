# API v1

کامل‌ترین راه دسترسی برنامه‌ای به پنل: خواندن و نوشتن برای مشتری، خرید، کیف پول، سطح/بخش‌بندی/برچسب، گزارش‌ها و یادآوری‌ها.

## احراز هویت

هر درخواست باید کلید API را در هدر بفرستد:

```text
X-API-Key: PREFIX.SECRET
Content-Type: application/json
```

کلید از منوی **کلید API** (مدیر) ساخته می‌شود و دسترسی هر کلید از همان صفحه با دو تیک «خواندن» و «نوشتن» تعیین می‌شود. مقدار ذخیره‌شده در ستون `scopes`:

- `read,write` (پیش‌فرض) — همه‌چیز
- `read` — فقط GET
- `*` — همه‌چیز (از طریق API قابل تنظیم است)

درخواست بدون کلید → `401`؛ کلید بدون دسترسی لازم → `403`.

## قواعد عمومی

- پاسخ‌ها JSON با `{"ok": true, ...}` یا `{"ok": false, "error": "..."}`.
- خطای اعتبارسنجی: `422` با `errors` (فیلد → پیام).
- صفحه‌بندی: `?limit=50&offset=0` (limit حداکثر ۲۰۰).
- تاریخ‌ها: میلادی `YYYY-MM-DD` یا شمسی `1403/06/15` هر دو پذیرفته می‌شوند.
- اگر هاست `PUT/PATCH/DELETE` را مسدود کند: `POST` بفرست و هدر `X-HTTP-Method-Override: DELETE` (یا فیلد `_method`) بگذار.
- مشتری حذف‌شده = حذف نرم (`deleted_at`).

## سلامت

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/v1/health` | نسخه، زمان، وضعیت دیتابیس |

## مشتریان

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/v1/customers` | فهرست + فیلترها: `q`, `first_name`, `last_name`, `company`, `phone_number`, `national_code`, `tier_id`, `segment`, `tag_id` |
| GET | `/api/v1/customers/by-phone?phone=09...` | پیدا کردن با موبایل (سازگار با نسخه قبلی) |
| GET | `/api/v1/customers/{id}` | جزئیات + سطح + بخش + برچسب‌ها + آمار خرید |
| POST | `/api/v1/customers` | ایجاد |
| PUT/PATCH | `/api/v1/customers/{id}` | ویرایش |
| DELETE | `/api/v1/customers/{id}` | حذف نرم |
| GET | `/api/v1/customers/{id}/purchases` | خریدهای مشتری |
| GET | `/api/v1/customers/{id}/wallet` | موجودی + ۱۰۰ تراکنش آخر |
| GET | `/api/v1/customers/{id}/tags` | برچسب‌های مشتری |
| PUT | `/api/v1/customers/{id}/tags` | جایگزینی برچسب‌ها با `{"tag_ids":[1,2]}` |

ایجاد مشتری:

```json
{"first_name":"علی","last_name":"احمدی","phone_number":"09123456789","birthday":"1370/01/01","company":"..."}
```

## خریدها

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/v1/purchases` | فیلتر: `customer_id`, `status` (`active|voided`), `invoice_ref`, `date_from`, `date_to` |
| GET | `/api/v1/purchases/{id}` | جزئیات |
| POST | `/api/v1/purchases` | ثبت خرید و کش‌بک |
| POST | `/api/v1/purchases/{id}/void` | ابطال با `{"reason":"..."}` |

ثبت خرید (مثل نسخه قبلی، به‌علاوه امکان استفاده از `phone_number` به‌جای `customer_id`):

```json
{"customer_id": 1, "amount": 1000000, "invoice_ref": "INV-1001", "confirm_duplicate": false}
```

پاسخ: `201` با `purchase_id`, `cashback`, `percent_applied`. خرید تکراری مشکوک → `409` و `errors.duplicate`.

## کیف پول

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/v1/wallet/{customerId}` | موجودی |
| GET | `/api/v1/wallet/{customerId}/transactions?limit=100` | تراکنش‌ها |
| POST | `/api/v1/wallet/reduce` | کسر |
| POST | `/api/v1/wallet/credit` | شارژ |

```json
{"customer_id": 1, "amount": 50000, "reason": "پرداخت فاکتور", "related_purchase_amount": 200000}
```

## سطح، بخش‌بندی و برچسب

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/v1/tiers` | فهرست سطح‌ها |
| POST | `/api/v1/tiers/recalculate` | بازمحاسبه سطح همه مشتریان |
| GET | `/api/v1/segments` | کاتالوگ + توزیع RFM + آستانه‌ها |
| POST | `/api/v1/segments/refresh` | بازمحاسبه بخش‌بندی |
| GET | `/api/v1/tags` | برچسب‌ها + تعداد مشتری |
| POST | `/api/v1/tags` | ساخت برچسب `{"name":"VIP"}` |
| PUT | `/api/v1/tags/{id}` | تغییر نام |
| DELETE | `/api/v1/tags/{id}` | حذف |

## گزارش‌ها

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/v1/reports/summary` | KPI داشبورد + بدهی کل کیف پول |
| GET | `/api/v1/reports/customers` | خرید هر مشتری با `date_from/date_to/tier_id/segment/tag_id/q/min_total` |
| GET | `/api/v1/reports/cashback` | صادر/مصرف در بازه + بدهی امتیاز + سری زمانی |
| GET | `/api/v1/reports/liability` | بدهی امتیاز و سطل‌های انقضا |

## یادآوری‌ها

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/v1/reminders/rules` | قواعد |
| POST | `/api/v1/reminders/rules` | ایجاد قاعده |
| PUT/PATCH | `/api/v1/reminders/rules/{id}` | ویرایش |
| DELETE | `/api/v1/reminders/rules/{id}` | حذف |
| GET | `/api/v1/reminders` | صف (`status`, `kind`, `customer_id`, `date_from`, `date_to`) |
| POST | `/api/v1/reminders/plan` | ساخت ردیف‌های موعددار |
| POST | `/api/v1/reminders/dispatch` | ارسال صف (`{"limit":100}`) |

ایجاد قاعده:

```json
{
  "kind": "winback",
  "label": "بازگشت ۶۰ روز",
  "enabled": true,
  "direction": "after",
  "offset_days": 60,
  "time_of_day": "11:00",
  "template": "{full_name} عزیز، امتیاز شما در کیف پول منتظر است.",
  "conditions": {"segment": "at_risk", "min_purchase": 1000000}
}
```

انواع: `birthday`, `winback`, `points_expiry`, `service_due`, `contract_renewal`, `custom`.

## جشنواره‌ها و تخفیفات ویژه

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/api/v1/festivals` | فهرست جشنواره‌ها + فهرست مخاطب‌ها |
| POST | `/api/v1/festivals` | ساخت جشنواره (و در صورت `send_now` ارسال فوری) |
| GET | `/api/v1/festivals/{id}` | جزئیات + تعداد مخاطب + تعداد ارسال‌شده |
| PUT/PATCH | `/api/v1/festivals/{id}` | فقط لغو: `{"status":"cancelled"}` |
| DELETE | `/api/v1/festivals/{id}` | حذف جشنواره و سابقه ارسال |
| POST | `/api/v1/festivals/{id}/send` | ارسال؛ `{"dry_run":true}` برای پیش‌نمایش، `{"limit":500}` برای اندازه دسته |

ساخت جشنواره:

```json
{
  "name": "جشنواره یلدا",
  "description": "کمپین پایان پاییز",
  "discount_text": "۳۰٪ تخفیف روی همه خدمات",
  "discount_percent": 30,
  "discount_code": "YALDA30",
  "starts_at": "1403/09/25",
  "ends_at": "1403/09/30",
  "send_at": "1403/09/24",
  "send_time": "10:00",
  "audience": "club_members",
  "template": "{full_name} عزیز، جشنواره {festival_name} از {starts_at} تا {ends_at}؛ {discount_text} کد: {discount_code}"
}
```

مخاطب‌ها: `all` (همه مشتریان)، `club_members` (اعضای باشگاه = دارای خرید)، `reminder_recipients` (کسانی که یادآوری دریافت کرده‌اند)، `segment` (با `audience_value` = کد بخش)، `tier` (شناسه سطح)، `tag` (شناسه برچسب).

متغیرهای پیام: `{full_name}`, `{festival_name}`, `{discount_text}`, `{discount_percent}`, `{discount_code}`, `{starts_at}`, `{ends_at}`, `{wallet_balance}`.

پاسخ ارسال:

```json
{"ok": true, "dry_run": false, "sent": 120, "skipped": 3, "failed": 0, "remaining": 0}
```

- هر مشتری فقط یک بار در هر جشنواره پیام می‌گیرد (جدول `club_festival_recipients`).
- ارسال دسته‌بندی‌شده و قابل ادامه است؛ اگر تعداد مخاطب از `limit` بیشتر باشد، وضعیت `sending` می‌شود و کرون `festival_send` ادامه می‌دهد.
- ارسال زمان‌بندی‌شده با `send_at` انجام می‌شود؛ بدون `send_at` جشنواره «پیش‌نویس» می‌ماند.

## نمونه‌های curl

```bash
curl -H "X-API-Key: PREFIX.SECRET" https://example.com/api/v1/health

curl -H "X-API-Key: PREFIX.SECRET" "https://example.com/api/v1/customers?q=احمدی&limit=20"

curl -X POST -H "X-API-Key: PREFIX.SECRET" -H "Content-Type: application/json" \
  -d '{"customer_id":1,"amount":1000000}' https://example.com/api/v1/purchases

curl -X POST -H "X-API-Key: PREFIX.SECRET" \
  https://example.com/api/v1/segments/refresh

curl -X DELETE -H "X-API-Key: PREFIX.SECRET" https://example.com/api/v1/tags/3
```

## کد وضعیت‌ها

| کد | معنی |
|---|---|
| 200 | موفق |
| 201 | ایجاد شد |
| 401 | کلید نامعتبر/نبود کلید |
| 403 | کلید این scope را ندارد |
| 404 | یافت نشد |
| 405 | متد برای این مسیر مجاز نیست |
| 409 | تعارض (تکراری بودن) |
| 422 | خطای اعتبارسنجی |
| 500 | خطای داخلی (جزئیات در `storage/logs` یا error log هاست) |
