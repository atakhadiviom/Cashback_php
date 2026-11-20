<?php

use App\Core\Csrf;
use App\Core\Jalali;

$festivals = $festivals ?? [];
$audiences = $audiences ?? [];
$segments = $segments ?? [];
$tiers = $tiers ?? [];
$tags = $tags ?? [];
$input = $input ?? [];
$errors = $errors ?? [];

$statusLabels = [
    'draft' => ['پیش‌نویس', 'secondary'],
    'scheduled' => ['زمان‌بندی‌شده', 'info'],
    'sending' => ['در حال ارسال', 'warning'],
    'sent' => ['ارسال‌شده', 'success'],
    'cancelled' => ['لغو‌شده', 'dark'],
];

$segmentOptions = array_map(static fn (array $s): array => ['value' => (string) $s['code'], 'label' => (string) $s['name']], $segments);
$tierOptions = array_map(static fn (array $t): array => ['value' => (string) $t['id'], 'label' => (string) $t['name']], $tiers);
$tagOptions = array_map(static fn (array $t): array => ['value' => (string) $t['id'], 'label' => (string) $t['name']], $tags);
$currentAudience = (string) ($input['audience'] ?? 'club_members');
$currentValue = (string) ($input['audience_value'] ?? '');
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">باشگاه مشتریان</div>
        <h1 class="h3 mb-0">جشنواره‌ها و تخفیفات ویژه</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/club-reminders')) ?>">قواعد یادآوری</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/club')) ?>">داشبورد باشگاه</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white fw-bold">جشنواره جدید</div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/admin/festivals')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">

            <div class="col-md-6">
                <label class="form-label">نام جشنواره</label>
                <input class="form-control" name="name" required value="<?= e((string) ($input['name'] ?? '')) ?>">
                <?php if (!empty($errors['name'])): ?><div class="form-text-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">توضیح داخلی (اختیاری)</label>
                <input class="form-control" name="description" value="<?= e((string) ($input['description'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">متن تخفیف</label>
                <input class="form-control" name="discount_text" placeholder="مثلاً ۳۰٪ تخفیف روی همه خدمات" value="<?= e((string) ($input['discount_text'] ?? '')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">درصد</label>
                <input class="form-control ltr" name="discount_percent" inputmode="decimal" placeholder="30" value="<?= e((string) ($input['discount_percent'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">کد تخفیف (اختیاری)</label>
                <input class="form-control ltr" name="discount_code" placeholder="YALDA30" value="<?= e((string) ($input['discount_code'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">مخاطب</label>
                <select class="form-select" name="audience" id="festival-audience">
                    <?php foreach ($audiences as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $currentAudience === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4" id="festival-audience-value-wrap">
                <label class="form-label">انتخاب مورد</label>
                <select class="form-select" name="audience_value" id="festival-audience-value"></select>
                <div class="form-text" id="festival-audience-count">تعداد گیرنده: —</div>
                <?php if (!empty($errors['audience_value'])): ?><div class="form-text-error"><?= e($errors['audience_value']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">شروع جشنواره (شمسی)</label>
                <input class="form-control ltr" type="text" name="starts_at" data-jdp data-jdp-only-date autocomplete="off" placeholder="1403/09/01" value="<?= e((string) ($input['starts_at'] ?? '')) ?>">
                <?php if (!empty($errors['starts_at'])): ?><div class="form-text-error"><?= e($errors['starts_at']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">پایان جشنواره (شمسی)</label>
                <input class="form-control ltr" type="text" name="ends_at" data-jdp data-jdp-only-date autocomplete="off" placeholder="1403/09/10" value="<?= e((string) ($input['ends_at'] ?? '')) ?>">
                <?php if (!empty($errors['ends_at'])): ?><div class="form-text-error"><?= e($errors['ends_at']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-3">
                <label class="form-label">تاریخ ارسال (شمسی، اختیاری)</label>
                <input class="form-control ltr" type="text" name="send_at" data-jdp data-jdp-only-date autocomplete="off" placeholder="خالی = پیش‌نویس" value="<?= e((string) ($input['send_at'] ?? '')) ?>">
                <?php if (!empty($errors['send_at'])): ?><div class="form-text-error"><?= e($errors['send_at']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-2">
                <label class="form-label">ساعت ارسال</label>
                <input class="form-control ltr" type="time" name="send_time" value="<?= e((string) ($input['send_time'] ?? '10:00')) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-center">
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" name="send_now" value="1" id="send-now" <?= !empty($input['send_now']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="send-now">همین حالا ارسال شود</label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">متن پیامک</label>
                <textarea class="form-control" name="template" rows="3" required><?= e((string) ($input['template'] ?? '{full_name} عزیز، جشنواره {festival_name} از {starts_at} تا {ends_at}؛ {discount_text} {discount_code}')) ?></textarea>
                <?php if (!empty($errors['template'])): ?><div class="form-text-error"><?= e($errors['template']) ?></div><?php endif; ?>
                <div class="form-text">
                    متغیرها: <code class="ltr">{full_name}</code> <code class="ltr">{festival_name}</code>
                    <code class="ltr">{discount_text}</code> <code class="ltr">{discount_percent}</code>
                    <code class="ltr">{discount_code}</code> <code class="ltr">{starts_at}</code> <code class="ltr">{ends_at}</code>
                    <code class="ltr">{wallet_balance}</code>
                </div>
            </div>

            <div class="col-12">
                <button class="btn btn-primary">ذخیره جشنواره</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-bold">جشنواره‌ها</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>بازه</th>
                    <th>تخفیف</th>
                    <th>مخاطب</th>
                    <th>ارسال</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($festivals as $festival): ?>
                <?php
                $status = (string) ($festival['status'] ?? 'draft');
                [$statusLabel, $statusColor] = $statusLabels[$status] ?? [$status, 'secondary'];
                $audienceLabel = $audiences[(string) $festival['audience']] ?? (string) $festival['audience'];
                ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= e((string) $festival['name']) ?></div>
                        <?php if (!empty($festival['description'])): ?><div class="small text-muted"><?= e((string) $festival['description']) ?></div><?php endif; ?>
                    </td>
                    <td class="small ltr">
                        <?= e(Jalali::formatDate(substr((string) $festival['starts_at'], 0, 10))) ?>
                        —
                        <?= e(Jalali::formatDate(substr((string) $festival['ends_at'], 0, 10))) ?>
                    </td>
                    <td class="small">
                        <?= e((string) ($festival['discount_text'] ?? '—')) ?>
                        <?php if (!empty($festival['discount_code'])): ?><div class="ltr text-muted"><?= e((string) $festival['discount_code']) ?></div><?php endif; ?>
                    </td>
                    <td class="small">
                        <?= e($audienceLabel) ?>
                        <?php if (!empty($festival['audience_value'])): ?><span class="ltr text-muted">(<?= e((string) $festival['audience_value']) ?>)</span><?php endif; ?>
                    </td>
                    <td class="small ltr">
                        <?= e((string) ($festival['send_at'] !== null ? Jalali::formatDateTime($festival['send_at']) : '—')) ?>
                        <div class="text-muted"><?= e((string) ($festival['recipients'] ?? 0)) ?> گیرنده</div>
                    </td>
                    <td><span class="badge text-bg-<?= e($statusColor) ?>"><?= e($statusLabel) ?></span></td>
                    <td class="text-nowrap">
                        <?php if (!in_array($status, ['sent', 'cancelled'], true)): ?>
                            <form method="post" action="<?= e(url('/admin/festivals/send')) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                                <input type="hidden" name="id" value="<?= e($festival['id']) ?>">
                                <input type="hidden" name="mode" value="preview">
                                <button class="btn btn-sm btn-outline-secondary">پیش‌نمایش</button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/festivals/send')) ?>" class="d-inline" data-confirm="پیامک‌ها واقعاً ارسال شوند؟">
                                <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                                <input type="hidden" name="id" value="<?= e($festival['id']) ?>">
                                <input type="hidden" name="mode" value="send">
                                <button class="btn btn-sm btn-primary">ارسال</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($status !== 'cancelled'): ?>
                            <form method="post" action="<?= e(url('/admin/festivals/cancel')) ?>" class="d-inline" data-confirm="جشنواره لغو شود؟">
                                <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                                <input type="hidden" name="id" value="<?= e($festival['id']) ?>">
                                <button class="btn btn-sm btn-outline-warning">لغو</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('/admin/festivals/delete')) ?>" class="d-inline" data-confirm="جشنواره و سابقه ارسال آن حذف شود؟">
                            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="id" value="<?= e($festival['id']) ?>">
                            <button class="btn btn-sm btn-outline-danger">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$festivals): ?><tr><td colspan="7" class="text-muted">جشنواره‌ای ثبت نشده است.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<link href="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.css')) ?>" rel="stylesheet">
<script src="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.js')) ?>"></script>
<script>
(function () {
    'use strict';
    var optionSets = {
        segment: <?= json_encode($segmentOptions, JSON_UNESCAPED_UNICODE) ?>,
        tier: <?= json_encode($tierOptions, JSON_UNESCAPED_UNICODE) ?>,
        tag: <?= json_encode($tagOptions, JSON_UNESCAPED_UNICODE) ?>
    };
    var currentValue = <?= json_encode($currentValue) ?>;
    var audience = document.getElementById('festival-audience');
    var valueSelect = document.getElementById('festival-audience-value');
    var wrap = document.getElementById('festival-audience-value-wrap');
    var countLabel = document.getElementById('festival-audience-count');
    var previewUrl = <?= json_encode(url('/admin/festivals/preview')) ?>;

    function refreshValues() {
        var type = audience.value;
        valueSelect.innerHTML = '';
        if (!optionSets[type]) {
            wrap.style.display = 'none';
            return;
        }
        wrap.style.display = '';
        var blank = document.createElement('option');
        blank.value = '';
        blank.textContent = 'انتخاب کنید';
        valueSelect.appendChild(blank);
        optionSets[type].forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label;
            if (item.value === currentValue) { option.selected = true; }
            valueSelect.appendChild(option);
        });
    }

    function refreshCount() {
        countLabel.textContent = 'تعداد گیرنده: …';
        var url = previewUrl + '?audience=' + encodeURIComponent(audience.value) + '&audience_value=' + encodeURIComponent(valueSelect.value);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                countLabel.textContent = 'تعداد گیرنده: ' + Number(data.count || 0).toLocaleString('fa-IR');
            })
            .catch(function () { countLabel.textContent = 'تعداد گیرنده: —'; });
    }

    audience.addEventListener('change', function () { currentValue = ''; refreshValues(); refreshCount(); });
    valueSelect.addEventListener('change', function () { currentValue = valueSelect.value; refreshCount(); });

    refreshValues();
    refreshCount();

    if (typeof jalaliDatepicker !== 'undefined') {
        jalaliDatepicker.startWatch({ separatorChars: { date: '/' }, persianDigits: true, autoShow: true, autoHide: true });
    }
})();
</script>
