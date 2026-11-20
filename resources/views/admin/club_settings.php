<?php

use App\Core\Csrf;

$options = $options ?? [];
$rfm = $options['rfm'] ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="text-muted small">مدیریت</div>
        <h1 class="h3 mb-0">تنظیمات باشگاه مشتریان</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('/club')) ?>">داشبورد باشگاه</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/loyalty')) ?>">سطوح و پروموشن</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/club-reminders')) ?>">قواعد یادآوری</a>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/club')) ?>">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">

    <div class="card mb-3">
        <div class="card-header bg-white fw-bold">وضعیت و سطح‌بندی</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="enabled" id="club-enabled" value="1" <?= !empty($options['enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="club-enabled">باشگاه مشتریان فعال باشد</label>
                </div>
                <div class="form-text">با غیرفعال شدن، یادآوری تکی و کمپین ارسال نمی‌شود (کش‌بک خرید و سطح‌ها دست‌نخورده می‌ماند).</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">مبنای محاسبه سطح</label>
                <select class="form-select" name="tier_basis" id="tier-basis">
                    <option value="lifetime" <?= ($options['tier_basis'] ?? 'lifetime') === 'lifetime' ? 'selected' : '' ?>>مجموع خرید از ابتدا (پیش‌فرض)</option>
                    <option value="rolling" <?= ($options['tier_basis'] ?? '') === 'rolling' ? 'selected' : '' ?>>خرید در بازه غلتان</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">طول بازه (ماه)</label>
                <input class="form-control ltr" name="tier_window_months" inputmode="numeric" value="<?= e((string) ($options['tier_window_months'] ?? '12')) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">مهلت تنزل سطح (روز)</label>
                <input class="form-control ltr" name="tier_downgrade_grace_days" inputmode="numeric" value="<?= e((string) ($options['tier_downgrade_grace_days'] ?? '30')) ?>">
            </div>

            <div class="col-md-8">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="tier_change_sms" id="tier-change-sms" value="1" <?= !empty($options['tier_change_sms']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="tier-change-sms">ارسال پیامک هنگام تغییر سطح مشتری</label>
                </div>
                <label class="form-label">قالب پیامک تغییر سطح</label>
                <input class="form-control" name="tier_change_template" value="<?= e((string) ($options['tier_change_template'] ?? '')) ?>">
                <div class="form-text">متغیرها: <code class="ltr">{full_name}</code> <code class="ltr">{tier_name}</code> <code class="ltr">{old_tier_name}</code></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">آستانه‌های بخش‌بندی RFM</span>
            <button class="btn btn-sm btn-outline-primary" type="button" id="suggest-thresholds">پیشنهاد از داده</button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">R — فاصله آخرین خرید (روز)</label>
                    <div class="d-flex gap-2">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <input class="form-control ltr" name="rfm_r<?= $i ?>" data-rfm="r<?= $i ?>" inputmode="numeric" value="<?= e((string) round((float) ($rfm['r'][$i - 1] ?? 0))) ?>">
                        <?php endfor; ?>
                    </div>
                    <div class="form-text">صعودی؛ کمترین فاصله بهترین امتیاز</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">F — تعداد خرید</label>
                    <div class="d-flex gap-2">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <input class="form-control ltr" name="rfm_f<?= $i ?>" data-rfm="f<?= $i ?>" inputmode="numeric" value="<?= e((string) round((float) ($rfm['f'][$i - 1] ?? 0))) ?>">
                        <?php endfor; ?>
                    </div>
                    <div class="form-text">صعودی</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">M — مبلغ کل خرید (ریال)</label>
                    <div class="d-flex gap-2">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <input class="form-control ltr" name="rfm_m<?= $i ?>" data-rfm="m<?= $i ?>" data-money inputmode="numeric" value="<?= e(number_format((float) ($rfm['m'][$i - 1] ?? 0), 0, '.', '')) ?>">
                        <?php endfor; ?>
                    </div>
                    <div class="form-text">صعودی</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">فاصله بازمحاسبه (ساعت)</label>
                    <input class="form-control ltr" name="rfm_refresh_hours" inputmode="numeric" value="<?= e((string) ($rfm['refresh_hours'] ?? '24')) ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white fw-bold">کمپین پیامکی</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">سقف ارسال روزانه</label>
                <input class="form-control ltr" name="campaign_daily_limit" inputmode="numeric" value="<?= e((string) ($options['campaign_daily_limit'] ?? '200')) ?>">
                <div class="form-text">۰ = بدون سقف (توصیه نمی‌شود)</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">حداقل فاصله بین دو پیام به یک مشتری (روز)</label>
                <input class="form-control ltr" name="campaign_min_interval_days" inputmode="numeric" value="<?= e((string) ($options['campaign_min_interval_days'] ?? '7')) ?>">
            </div>
        </div>
    </div>

    <button class="btn btn-primary">ذخیره تنظیمات</button>
</form>

<script>
(function () {
    'use strict';
    var button = document.getElementById('suggest-thresholds');
    if (!button) { return; }
    button.addEventListener('click', function () {
        button.disabled = true;
        fetch(<?= json_encode(url('/admin/club/suggest-thresholds')) ?>, { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                ['r', 'f', 'm'].forEach(function (band) {
                    (data[band] || []).forEach(function (value, index) {
                        var input = document.querySelector('[data-rfm="' + band + (index + 1) + '"]');
                        if (!input) { return; }
                        var number = Math.round(Number(value) || 0);
                        input.value = band === 'm' ? number.toLocaleString('en-US') : String(number);
                    });
                });
            })
            .catch(function () {})
            .then(function () { button.disabled = false; });
    });
})();
</script>
