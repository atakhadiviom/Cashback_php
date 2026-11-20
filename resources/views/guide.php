<?php

use App\Core\Jalali;

$today = Jalali::formatDate(date('Y-m-d'));
$exampleBirthday = Jalali::formatDate(date('Y-m-d', strtotime('+3 days')));
$exampleWinback = Jalali::formatDate(date('Y-m-d', strtotime('-60 days')));
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">راهنما</div>
        <h1 class="h3 mb-0">آموزش باشگاه مشتریان با مثال</h1>
        <div class="small text-muted mt-1">همهٔ مراحل با اعداد نمونه؛ امروز: <?= e($today) ?></div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/club')) ?>">داشبورد باشگاه</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/festivals')) ?>">جشنواره‌ها</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-3">
        <div class="card position-sticky" style="top:1rem">
            <div class="card-header bg-white fw-bold">فهرست</div>
            <div class="list-group list-group-flush small">
                <a class="list-group-item list-group-item-action" href="#g-start">۱) شروع سریع باشگاه</a>
                <a class="list-group-item list-group-item-action" href="#g-levels">۲) سطح‌بندی خودکار</a>
                <a class="list-group-item list-group-item-action" href="#g-rfm">۳) بخش‌بندی RFM</a>
                <a class="list-group-item list-group-item-action" href="#g-reminders">۴) یادآوری زمان‌بندی‌شده</a>
                <a class="list-group-item list-group-item-action" href="#g-campaign">۵) کمپین گروهی</a>
                <a class="list-group-item list-group-item-action" href="#g-festival">۶) جشنواره و تخفیف ویژه</a>
                <a class="list-group-item list-group-item-action" href="#g-sms">۷) تنظیم پیامک‌ها</a>
                <a class="list-group-item list-group-item-action" href="#g-api">۸) API با مثال</a>
                <a class="list-group-item list-group-item-action" href="#g-trouble">۹) عیب‌یابی</a>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="card mb-3" id="g-start">
            <div class="card-header bg-white fw-bold">۱) شروع سریع باشگاه مشتریان</div>
            <div class="card-body">
                <ol class="mb-2">
                    <li><a href="<?= e(url('/admin/club')) ?>">تنظیمات باشگاه</a>: کلید «باشگاه فعال» را روشن کن، مبنای سطح (کل خرید یا ۱۲ ماه اخیر) و مهلت تنزل را انتخاب کن.</li>
                    <li><a href="<?= e(url('/admin/loyalty')) ?>">سطوح و پروموشن</a>: حداقل‌های خرید و درصد کش‌بک هر سطح را بررسی کن (برنزی، نقره‌ای، طلایی).</li>
                    <li><a href="<?= e(url('/club/segments')) ?>">بخش‌بندی مشتریان</a>: دکمهٔ «بازمحاسبه بخش‌بندی» را بزن.</li>
                    <li><a href="<?= e(url('/admin/loyalty')) ?>">همان صفحه سطوح</a>: «بازمحاسبه سطح همه» را بزن.</li>
                    <li><a href="<?= e(url('/admin/club-reminders')) ?>">قواعد یادآوری</a>: هر قاعده‌ای که لازم داری روشن کن (پیش‌فرض خاموش است).</li>
                </ol>
                <div class="alert alert-secondary small mb-0">
                    مثال: مشتری «علی» بعد از خرید ۸٬۰۰۰٬۰۰۰ ریالی از سطح برنزی به نقره‌ای می‌رود (حداقل نقره‌ای ۵۰٬۰۰۰٬۰۰۰ ریال) → این مقایسه در بازمحاسبه انجام می‌شود، پس آستانه‌ها را با مبالغ واقعی خودت تنظیم کن.
                </div>
            </div>
        </div>

        <div class="card mb-3" id="g-levels">
            <div class="card-header bg-white fw-bold">۲) سطح‌بندی خودکار</div>
            <div class="card-body">
                <p>بعد از هر «ثبت خرید» یا «ابطال خرید»، سطح مشتری خودکار بازمحاسبه می‌شود و در صفحهٔ مشتری ثبت می‌شود.</p>
                <div class="row g-2 small mb-2">
                    <div class="col-md-4"><div class="border rounded p-2 h-100">مبنا: مجموع خرید فعال</div></div>
                    <div class="col-md-4"><div class="border rounded p-2 h-100">تنزل: فقط بعد از مهلت (پیش‌فرض ۳۰ روز)</div></div>
                    <div class="col-md-4"><div class="border rounded p-2 h-100">پیامک تغییر سطح: از تنظیمات باشگاه</div></div>
                </div>
                <p class="mb-0 small text-muted">مثال عملی: مشتری با ۵۰٬۰۰۰٬۰۰۰ خرید باید نقره‌ای شود؛ اگر آستانهٔ نقره‌ای ۳۰٬۰۰۰٬۰۰۰ باشد با همان خرید اول نقره‌ای می‌شود.</p>
            </div>
        </div>

        <div class="card mb-3" id="g-rfm">
            <div class="card-header bg-white fw-bold">۳) بخش‌بندی RFM</div>
            <div class="card-body">
                <p class="mb-2">سه معیار و امتیاز ۱ تا ۵:</p>
                <ul class="small">
                    <li><strong>R</strong> فاصلهٔ آخرین خرید: خرید ۱۰ روز پیش → امتیاز ۵ (اگر آستانهٔ اول ۱۵ روز باشد)</li>
                    <li><strong>F</strong> تعداد خرید: ۶ خرید → با آستانه‌های ۱/۲/۴/۸ → امتیاز ۴</li>
                    <li><strong>M</strong> مجموع خرید: ۷٬۰۰۰٬۰۰۰ ریال → با آستانه‌های ۱/۵/۲۰/۵۰ میلیون → امتیاز ۳</li>
                </ul>
                <div class="alert alert-secondary small mb-2">
                    نتیجه: R5 F4 M3 → بخش «وفادار». اگر همین مشتری ۲۰۰ روز خرید نکند: R1 F4 M3 → «در خطر».
                </div>
                <p class="mb-0 small">آستانه‌ها در <a href="<?= e(url('/admin/club')) ?>">تنظیمات باشگاه</a> است؛ دکمهٔ «پیشنهاد از داده» آن‌ها را از صدک‌های واقعی پر می‌کند. مشتریان هر بخش با یک کلیک در فهرست مشتریان فیلتر می‌شوند.</p>
            </div>
        </div>

        <div class="card mb-3" id="g-reminders">
            <div class="card-header bg-white fw-bold">۴) یادآوری زمان‌بندی‌شده (با ساعت)</div>
            <div class="card-body">
                <p class="mb-2">در <a href="<?= e(url('/admin/club-reminders')) ?>">قواعد یادآوری</a> هر قاعده این اجزا را دارد: نوع، جهت (قبل/بعد)، تعداد روز، ساعت ارسال، متن پیامک و شرط (بخش/سطح/حداقل خرید).</p>
                <table class="table table-sm small">
                    <thead><tr><th>نوع</th><th>جهت</th><th>روز</th><th>ساعت</th><th>مثال خروجی</th></tr></thead>
                    <tbody>
                        <tr><td>بازگشت مشتری</td><td>بعد از آخرین خرید</td><td>۶۰</td><td>۱۱:۰۰</td><td>آخرین خرید <?= e($exampleWinback) ?> → ارسال امروز</td></tr>
                        <tr><td>تبریک تولد</td><td>قبل از تولد</td><td>۳</td><td>۱۰:۰۰</td><td>تولد <?= e($exampleBirthday) ?> → ارسال امروز</td></tr>
                        <tr><td>انقضای امتیاز</td><td>قبل از انقضا</td><td>۷</td><td>۰۹:۰۰</td><td>اعتبار تا ۷ روز آینده → هشدار</td></tr>
                    </tbody>
                </table>
                <p class="small mb-2">چرخه: کرون روزانه «برنامه‌ریز» ردیف‌های موعددار را می‌سازد و کرون ۱۵ دقیقه‌ای «ارسال» پیامک می‌فرستد. اگر مشتری قبل از ارسال خرید کند، یادآوری «رد‌شده» می‌شود و پیامک نمی‌رود.</p>
                <div class="alert alert-secondary small mb-0">
                    مثال تست: یک قاعده با آفست ۰ روز و ساعت یک ساعت بعد بساز، «اجرای برنامه‌ریز» و بعد «ارسال صف» را بزن؛ در جدول صف باید وضعیت «ارسال‌شده» ببینی و در <a href="<?= e(url('/sms/logs')) ?>">لاگ پیامک</a> رکورد ثبت شود.
                </div>
            </div>
        </div>

        <div class="card mb-3" id="g-campaign">
            <div class="card-header bg-white fw-bold">۵) کمپین گروهی</div>
            <div class="card-body">
                <p class="mb-2">در همان صفحهٔ یادآوری‌ها، پایین فرم: یک بخش/سطح/جستجو را انتخاب کن، متن را بنویس و اول «پیش‌نمایش تعداد» را بزن؛ سپس «ارسال واقعی».</p>
                <ul class="small mb-0">
                    <li>سقف ارسال روزانه و حداقل فاصله بین دو پیام به یک مشتری در تنظیمات باشگاه است (پیش‌فرض ۲۰۰ و ۷ روز).</li>
                    <li>مثال: بخش «در خطر» + پیام «{full_name} عزیز، امتیاز شما در کیف پول منتظر است» → پیش‌نمایش ۳۸ گیرنده → ارسال.</li>
                </ul>
            </div>
        </div>

        <div class="card mb-3" id="g-festival">
            <div class="card-header bg-white fw-bold">۶) جشنواره و تخفیف ویژه</div>
            <div class="card-body">
                <p class="mb-2">مسیر: <strong>مدیریت ← جشنواره و تخفیف ویژه</strong> (<a href="<?= e(url('/admin/festivals')) ?>">/admin/festivals</a>).</p>
                <p class="mb-2">در فرم «جشنواره جدید»:</p>
                <div class="table-responsive">
                    <table class="table table-sm small">
                        <tbody>
                            <tr><td class="text-muted">نام</td><td>جشنواره یلدا</td></tr>
                            <tr><td class="text-muted">متن تخفیف</td><td>۳۰٪ تخفیف روی همه خدمات</td></tr>
                            <tr><td class="text-muted">درصد / کد</td><td>۳۰ / YALDA30</td></tr>
                            <tr><td class="text-muted">بازه</td><td><?= e($exampleBirthday) ?> تا <?= e(Jalali::formatDate(date('Y-m-d', strtotime('+10 days')))) ?></td></tr>
                            <tr><td class="text-muted">ارسال</td><td>یک روز قبل، ساعت ۱۰:۰۰ (یا «همین حالا»)</td></tr>
                            <tr><td class="text-muted">مخاطب</td><td>اعضای باشگاه (دارای خرید)</td></tr>
                        </tbody>
                    </table>
                </div>
                <pre class="bg-light border rounded p-2 small ltr mb-2" dir="ltr">{full_name} عزیز، جشنواره {festival_name} از {starts_at} تا {ends_at}؛ {discount_text}. کد: {discount_code}</pre>
                <p class="small mb-2">با تغییر مخاطب، تعداد گیرنده همان‌جا نمایش داده می‌شود. دکمهٔ «پیش‌نمایش» بدون ارسال تست می‌کند و «ارسال» واقعی است. هر مشتری فقط یک‌بار پیام می‌گیرد و ارسال دسته‌ای قابل ادامه است (کرون هر ۱۵ دقیقه ادامه می‌دهد).</p>
                <div class="alert alert-secondary small mb-0">
                    مخاطب‌ها: همه مشتریان / اعضای باشگاه (دارای خرید) / دریافت‌کنندگان یادآوری / یک بخش RFM / یک سطح / یک برچسب.
                </div>
            </div>
        </div>

        <div class="card mb-3" id="g-sms">
            <div class="card-header bg-white fw-bold">۷) تنظیم پیامک‌ها</div>
            <div class="card-body">
                <p class="mb-2">در <a href="<?= e(url('/admin/sms-settings')) ?>">تنظیمات پیامک</a>: توکن ippanel، شماره فرستنده، کلید «فعال‌سازی کلی» و تیک هر رویداد.</p>
                <ul class="small mb-2">
                    <li>رویدادها: خرید، ابطال خرید، تولد، خوشامد، کسر کیف پول، شارژ کیف پول، ثبت سرویس، تمدید قرارداد، ثبت/یادآوری سررسید، انقضای کش‌بک.</li>
                    <li>تغییر سطح، یادآوری‌ها، کمپین و جشنواره در کارت «پیامک‌های باشگاه مشتریان» همان صفحه لینک شده‌اند.</li>
                </ul>
                <p class="small mb-0 text-muted">اگر «فعال‌سازی کلی» خاموش باشد هیچ پیامکی ارسال نمی‌شود، حتی اگر تیک همهٔ رویدادها روشن باشد.</p>
            </div>
        </div>

        <div class="card mb-3" id="g-api">
            <div class="card-header bg-white fw-bold">۸) API با مثال</div>
            <div class="card-body">
                <p class="small mb-2">کلید API از <a href="<?= e(url('/admin/api-keys')) ?>">کلیدهای API</a> ساخته می‌شود (دسترسی «خواندن» و «نوشتن»). هدرها:</p>
                <pre class="bg-light border rounded p-2 small ltr mb-2" dir="ltr">X-API-Key: PREFIX.SECRET
Content-Type: application/json</pre>
                <pre class="bg-light border rounded p-2 small ltr mb-0" dir="ltr"># سلامت
curl -H "X-API-Key: KEY" https://example.com/api/v1/health

# فهرست مشتریان
curl -H "X-API-Key: KEY" "https://example.com/api/v1/customers?q=احمدی&limit=20"

# ثبت خرید
curl -X POST -H "X-API-Key: KEY" -H "Content-Type: application/json" \
  -d '{"customer_id":12,"amount":1000000,"invoice_ref":"INV-1001"}' \
  https://example.com/api/v1/purchases

# شارژ کیف پول
curl -X POST -H "X-API-Key: KEY" -H "Content-Type: application/json" \
  -d '{"customer_id":12,"amount":500000,"reason":"هدیه"}' \
  https://example.com/api/v1/wallet/credit

# ساخت جشنواره و ارسال آزمایشی
curl -X POST -H "X-API-Key: KEY" -H "Content-Type: application/json" \
  -d '{"name":"یلدا","starts_at":"1403/09/25","ends_at":"1403/09/30",
       "discount_text":"۳۰٪ تخفیف","audience":"club_members",
       "template":"{full_name} عزیز، {discount_text}"}' \
  https://example.com/api/v1/festivals
curl -X POST -H "X-API-Key: KEY" -H "Content-Type: application/json" \
  -d '{"dry_run":true}' https://example.com/api/v1/festivals/1/send</pre>
                <div class="form-text">مستند کامل: <code class="ltr">docs/api-v1.md</code> در ریشهٔ پروژه.</div>
            </div>
        </div>

        <div class="card" id="g-trouble">
            <div class="card-header bg-white fw-bold">۹) عیب‌یابی</div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li><strong>پیامکی نمی‌رود:</strong> «فعال‌سازی کلی» + تیک آن رویداد + توکن/شماره فرستنده را چک کن؛ نتیجه در <a href="<?= e(url('/sms/logs')) ?>">لاگ پیامک</a> است.</li>
                    <li><strong>تعداد گیرنده صفر است:</strong> مشتری باید شمارهٔ موبایل داشته باشد؛ برای «اعضای باشگاه» باید حداقل یک خرید فعال داشته باشد.</li>
                    <li><strong>بخش‌بندی خالی است:</strong> در <a href="<?= e(url('/club/segments')) ?>">بخش‌بندی</a> دکمهٔ بازمحاسبه را بزن (کرون روزانه هم انجام می‌دهد).</li>
                    <li><strong>یادآوری ارسال نشد:</strong> قاعده باید «فعال» باشد و موعدش رسیده باشد؛ «برنامه‌ریز» و «ارسال صف» را دستی بزن تا صف را ببینی.</li>
                    <li><strong>ارسال جشنواره شروع نشد:</strong> یا «همین حالا» را نزده‌ای یا تاریخ ارسال آینده است؛ دکمهٔ «ارسال» در جدول، ارسال فوری است.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
