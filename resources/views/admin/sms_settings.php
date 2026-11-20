<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">تنظیمات پیامک ippanel</h1>
<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/admin/sms-settings')) ?>">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">توکن API</label>
            <input class="form-control ltr" name="api_token" placeholder="<?= !empty($settings['api_token']) ? 'توکن ذخیره شده است؛ برای تغییر مقدار جدید وارد کنید' : 'توکن ippanel را وارد کنید' ?>" autocomplete="off">
            <div class="form-text"><?= !empty($settings['api_token']) ? 'توکن ippanel در دیتابیس ذخیره شده است.' : 'هنوز توکنی در دیتابیس ذخیره نشده است.' ?></div>
        </div>
        <div class="col-md-6"><label class="form-label">شماره فرستنده</label><input class="form-control ltr" name="sender_number" value="<?= e($settings['sender_number'] ?? '') ?>" placeholder="مثلاً 3000..." autocomplete="off"></div>
        <?php foreach (['sms_enabled'=>'فعال‌سازی کلی', 'purchase_sms_enabled'=>'پیامک خرید', 'birthday_sms_enabled'=>'پیامک تولد', 'wallet_reduction_sms_enabled'=>'پیامک کسر کیف پول', 'wallet_credit_sms_enabled'=>'پیامک شارژ کیف پول', 'welcome_sms_enabled'=>'پیامک خوشامد', 'service_sms_enabled'=>'پیامک ثبت سرویس', 'contract_renewal_sms_enabled'=>'پیامک تمدید قرارداد', 'due_date_sms_enabled'=>'پیامک ثبت سررسید', 'due_date_reminder_sms_enabled'=>'یادآوری سررسید', 'cashback_expiry_sms_enabled'=>'هشدار انقضای کش‌بک'] as $name=>$label): ?>
            <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="<?= e($name) ?>" name="<?= e($name) ?>" <?= !empty($settings[$name]) ? 'checked' : '' ?>><label class="form-check-label" for="<?= e($name) ?>"><?= e($label) ?></label></div></div>
        <?php endforeach; ?>
        <div class="col-12"><label class="form-label">قالب خرید</label><textarea class="form-control" name="purchase_template" rows="3"><?= e($settings['purchase_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب تولد</label><textarea class="form-control" name="birthday_template" rows="3"><?= e($settings['birthday_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب کسر کیف پول</label><textarea class="form-control" name="wallet_reduction_template" rows="3"><?= e($settings['wallet_reduction_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب شارژ کیف پول</label><textarea class="form-control" name="wallet_credit_template" rows="3"><?= e($settings['wallet_credit_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب خوشامد</label><textarea class="form-control" name="welcome_template" rows="3"><?= e($settings['welcome_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب ثبت سرویس</label><textarea class="form-control" name="service_template" rows="3"><?= e($settings['service_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب تمدید قرارداد</label><textarea class="form-control" name="contract_renewal_template" rows="3"><?= e($settings['contract_renewal_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب ثبت سررسید</label><textarea class="form-control" name="due_date_template" rows="4"><?= e($settings['due_date_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب یادآوری سررسید</label><textarea class="form-control" name="due_date_reminder_template" rows="4"><?= e($settings['due_date_reminder_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب هشدار انقضای کش‌بک</label><textarea class="form-control" name="cashback_expiry_template" rows="4"><?= e($settings['cashback_expiry_template'] ?? '') ?></textarea><div class="form-text">یک ماه (یا بازه تنظیم‌شده) پیش از انقضای کش‌بک ارسال می‌شود.</div></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary">ذخیره تنظیمات</button></div>
</form>
<div class="mt-3 text-muted small">متغیرها: {first_name} {last_name} {full_name} {purchase_amount} {cashback_amount} {wallet_balance} {birthday} {date} {company_name} {contract_number} {contract_ends_at} {service_type} {service_date} {paid_amount} {due_amount} {due_date} {reference_number} {due_type_label} {expiring_amount} {expiry_date}</div>
</div></div>

<div class="card mt-3">
    <div class="card-header bg-white fw-bold">پیامک‌های باشگاه مشتریان</div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-6">
                <div class="border rounded p-2 h-100">
                    <div class="fw-bold mb-1">تغییر سطح مشتری</div>
                    <div class="text-muted">روشن/خاموش و متن پیام در تنظیمات باشگاه است.</div>
                    <a href="<?= e(url('/admin/club')) ?>">تنظیمات باشگاه ←</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-2 h-100">
                    <div class="fw-bold mb-1">یادآوری‌های زمان‌بندی‌شده و کمپین</div>
                    <div class="text-muted">هر قاعده قالب و ساعت ارسال خودش را دارد.</div>
                    <a href="<?= e(url('/admin/club-reminders')) ?>">قواعد یادآوری ←</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-2 h-100">
                    <div class="fw-bold mb-1">جشنواره و تخفیف ویژه</div>
                    <div class="text-muted">قالب پیام هر جشنواره جداگانه تعریف می‌شود.</div>
                    <a href="<?= e(url('/admin/festivals')) ?>">جشنواره‌ها ←</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-2 h-100">
                    <div class="fw-bold mb-1">ردیابی همه پیامک‌ها</div>
                    <div class="text-muted">وضعیت ارسال، خطا و تلاش مجدد.</div>
                    <a href="<?= e(url('/sms/logs')) ?>">لاگ پیامک ←</a>
                </div>
            </div>
        </div>
        <div class="form-text mt-2">همهٔ رویدادها با کلید «فعال‌سازی کلی» بالا کار می‌کنند؛ اگر پیامک کلی خاموش باشد هیچ‌کدام ارسال نمی‌شوند.</div>
    </div>
</div>
