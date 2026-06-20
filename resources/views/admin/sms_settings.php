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
        <?php foreach (['sms_enabled'=>'فعال‌سازی کلی', 'purchase_sms_enabled'=>'پیامک خرید', 'birthday_sms_enabled'=>'پیامک تولد', 'wallet_reduction_sms_enabled'=>'پیامک کسر کیف پول', 'welcome_sms_enabled'=>'پیامک خوشامد', 'service_sms_enabled'=>'پیامک ثبت سرویس', 'contract_renewal_sms_enabled'=>'پیامک تمدید قرارداد'] as $name=>$label): ?>
            <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="<?= e($name) ?>" name="<?= e($name) ?>" <?= !empty($settings[$name]) ? 'checked' : '' ?>><label class="form-check-label" for="<?= e($name) ?>"><?= e($label) ?></label></div></div>
        <?php endforeach; ?>
        <div class="col-12"><label class="form-label">قالب خرید</label><textarea class="form-control" name="purchase_template" rows="3"><?= e($settings['purchase_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب تولد</label><textarea class="form-control" name="birthday_template" rows="3"><?= e($settings['birthday_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب کسر کیف پول</label><textarea class="form-control" name="wallet_reduction_template" rows="3"><?= e($settings['wallet_reduction_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب خوشامد</label><textarea class="form-control" name="welcome_template" rows="3"><?= e($settings['welcome_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب ثبت سرویس</label><textarea class="form-control" name="service_template" rows="3"><?= e($settings['service_template'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label">قالب تمدید قرارداد</label><textarea class="form-control" name="contract_renewal_template" rows="3"><?= e($settings['contract_renewal_template'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary">ذخیره تنظیمات</button></div>
</form>
<div class="mt-3 text-muted small">متغیرها: {first_name} {last_name} {full_name} {purchase_amount} {cashback_amount} {wallet_balance} {birthday} {date} {company_name} {contract_number} {contract_ends_at} {service_type} {service_date} {paid_amount}</div>
</div></div>
