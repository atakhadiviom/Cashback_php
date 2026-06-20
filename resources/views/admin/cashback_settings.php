<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">تنظیمات کش‌بک و کیف پول</h1>
<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/admin/cashback-settings')) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <div class="col-md-4"><label class="form-label">درصد کش‌بک پایه</label><input class="form-control ltr" name="cashback_percent" value="<?= e($settings['cashback_percent'] ?? '5') ?>" required></div>
    <div class="col-md-4"><label class="form-label">حداقل مبلغ خرید (ریال)</label><input class="form-control ltr" name="min_purchase_amount" value="<?= e(money_input_value($settings['min_purchase_amount'] ?? null)) ?>" inputmode="numeric" data-money></div>
    <div class="col-md-4"><label class="form-label">سقف کش‌بک هر خرید</label><input class="form-control ltr" name="max_cashback_per_purchase" value="<?= e(money_input_value($settings['max_cashback_per_purchase'] ?? null, '')) ?>" inputmode="numeric" data-money placeholder="اختیاری"></div>
    <div class="col-md-4"><label class="form-label">حداقل کسر از کیف پول</label><input class="form-control ltr" name="min_redemption_amount" value="<?= e(money_input_value($settings['min_redemption_amount'] ?? null, '')) ?>" inputmode="numeric" data-money placeholder="اختیاری"></div>
    <div class="col-md-4"><label class="form-label">حداکثر درصد استفاده از کیف پول</label><input class="form-control ltr" name="max_redemption_percent_of_purchase" value="<?= e($settings['max_redemption_percent_of_purchase'] ?? '') ?>" placeholder="مثلاً 50"></div>
    <div class="col-md-4"><label class="form-label">آستانه کسر بزرگ (فقط مدیر)</label><input class="form-control ltr" name="large_redemption_threshold" value="<?= e(money_input_value($settings['large_redemption_threshold'] ?? null, '')) ?>" inputmode="numeric" data-money placeholder="اختیاری"></div>
    <div class="col-md-4"><label class="form-label">پاداش تولد (ریال)</label><input class="form-control ltr" name="birthday_bonus_amount" value="<?= e(money_input_value($settings['birthday_bonus_amount'] ?? null)) ?>" inputmode="numeric" data-money></div>
    <div class="col-md-4"><label class="form-label">پاداش معرفی (ریال)</label><input class="form-control ltr" name="referral_bonus_amount" value="<?= e(money_input_value($settings['referral_bonus_amount'] ?? null)) ?>" inputmode="numeric" data-money></div>
    <div class="col-md-4"><label class="form-label">پنجره هشدار خرید تکراری (دقیقه)</label><input class="form-control ltr" name="duplicate_purchase_window_minutes" value="<?= e($settings['duplicate_purchase_window_minutes'] ?? '5') ?>"></div>
    <div class="col-12"><button class="btn btn-primary">ذخیره تنظیمات</button></div>
</form>
</div></div>
