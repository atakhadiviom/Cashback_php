<?php

use App\Core\Csrf;
use App\Core\Jalali;
use App\Services\ReminderSchedule;

$rules = $rules ?? [];
$kinds = $kinds ?? ReminderSchedule::KINDS;
$queue = $queue ?? [];
$filters = $filters ?? ['status' => 'pending', 'kind' => '', 'date_from' => '', 'date_to' => ''];
$stats = $stats ?? [];
$segments = $segments ?? [];
$tiers = $tiers ?? [];
$placeholders = $placeholders ?? [];

$statusLabels = [
    'pending' => 'در انتظار',
    'sent' => 'ارسال‌شده',
    'skipped' => 'رد‌شده',
    'failed' => 'ناموفق',
];
$statusColors = [
    'pending' => 'warning',
    'sent' => 'success',
    'skipped' => 'secondary',
    'failed' => 'danger',
];
$prefillSegment = (string) ($_GET['segment'] ?? '');
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="text-muted small">باشگاه مشتریان</div>
        <h1 class="h3 mb-0">یادآوری‌های زمان‌بندی‌شده</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= e(url('/club')) ?>">داشبورد باشگاه</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/club')) ?>">تنظیمات باشگاه</a>
        <form method="post" action="<?= e(url('/admin/club-reminders/plan')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <button class="btn btn-outline-primary"><i class="bi bi-calendar-plus"></i> اجرای برنامه‌ریز</button>
        </form>
        <form method="post" action="<?= e(url('/admin/club-reminders/dispatch')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <button class="btn btn-primary"><i class="bi bi-send"></i> ارسال صف</button>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    <?php foreach ($statusLabels as $key => $label): ?>
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><?= e($label) ?></span>
                    <span class="badge text-bg-<?= e($statusColors[$key] ?? 'secondary') ?> ltr"><?= e(number_format((int) ($stats[$key] ?? 0))) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="alert alert-info small">
    ترتیب کار: کرون «برنامه‌ریز» (روزی ۰۱:۰۰) ردیف‌های موعددار را می‌سازد و کرون «ارسال» (هر ۱۵ دقیقه) پیامک‌ها را می‌فرستد.
    قواعد پیش‌فرض خاموش هستند تا بدون تصمیم شما پیامکی ارسال نشود. متغیرهای مجاز:
    <?php foreach ($placeholders as $placeholder): ?><code class="ltr"><?= e($placeholder) ?></code> <?php endforeach; ?>
</div>

<div class="card mb-4">
    <div class="card-header bg-white fw-bold">قواعد یادآوری</div>
    <div class="card-body">
        <?php foreach ($rules as $rule): ?>
            <?php $conditions = json_decode((string) ($rule['conditions'] ?? ''), true) ?: []; ?>
            <div class="border rounded p-3 mb-3">
                <form method="post" action="<?= e(url('/admin/club-reminders/update')) ?>" class="row g-2 align-items-end">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                    <input type="hidden" name="id" value="<?= e($rule['id']) ?>">
                    <input type="hidden" name="kind" value="<?= e($rule['kind']) ?>">

                    <div class="col-md-3">
                        <label class="form-label small mb-0">عنوان</label>
                        <input class="form-control form-control-sm" name="label" value="<?= e((string) $rule['label']) ?>">
                        <span class="text-muted small">نوع: <?= e(ReminderSchedule::kindLabel((string) $rule['kind'])) ?></span>
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label small mb-0">جهت</label>
                        <select class="form-select form-select-sm" name="direction">
                            <option value="after" <?= $rule['direction'] === 'after' ? 'selected' : '' ?>>بعد</option>
                            <option value="before" <?= $rule['direction'] === 'before' ? 'selected' : '' ?>>قبل</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label small mb-0">روز</label>
                        <input class="form-control form-control-sm ltr" name="offset_days" inputmode="numeric" value="<?= e((string) $rule['offset_days']) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-0">ساعت ارسال</label>
                        <input class="form-control form-control-sm ltr" type="time" name="time_of_day" value="<?= e(substr((string) $rule['time_of_day'], 0, 5)) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-0">حداقل خرید (اختیاری)</label>
                        <input class="form-control form-control-sm ltr" name="min_purchase" data-money inputmode="numeric" value="<?= e(isset($conditions['min_purchase']) ? number_format((float) $conditions['min_purchase'], 0, '.', '') : '0') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">فقط بخش</label>
                        <select class="form-select form-select-sm" name="segment">
                            <option value="">همه مشتریان</option>
                            <?php foreach ($segments as $segment): ?>
                                <option value="<?= e((string) $segment['code']) ?>" <?= ($conditions['segment'] ?? '') === $segment['code'] ? 'selected' : '' ?>><?= e((string) $segment['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-0">متن پیامک</label>
                        <textarea class="form-control form-control-sm" name="template" rows="2"><?= e((string) $rule['template']) ?></textarea>
                    </div>
                    <div class="col-12 d-flex gap-2 align-items-center flex-wrap">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="rule-enabled-<?= e($rule['id']) ?>" <?= (int) $rule['enabled'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="rule-enabled-<?= e($rule['id']) ?>">فعال</label>
                        </div>
                        <button class="btn btn-sm btn-primary">ذخیره</button>
                        <span class="text-muted small">ترتیب: <?= e((string) $rule['sort_order']) ?></span>
                    </div>
                </form>
                <form method="post" action="<?= e(url('/admin/club-reminders/delete')) ?>" class="mt-1" data-confirm="این قاعده حذف شود؟">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                    <input type="hidden" name="id" value="<?= e($rule['id']) ?>">
                    <button class="btn btn-sm btn-link text-danger p-0">حذف قاعده</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (!$rules): ?>
            <div class="text-muted">قاعده‌ای وجود ندارد؛ مهاجرت ۰۷۴ را اجرا کنید یا قاعده جدید بسازید.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white fw-bold">قاعده جدید</div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/admin/club-reminders/store')) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <div class="col-md-3">
                <label class="form-label small mb-0">نوع</label>
                <select class="form-select form-select-sm" name="kind">
                    <?php foreach ($kinds as $kind => $label): ?>
                        <option value="<?= e($kind) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">عنوان</label>
                <input class="form-control form-control-sm" name="label" placeholder="مثلاً یادآوری ۹۰ روز">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small mb-0">جهت</label>
                <select class="form-select form-select-sm" name="direction">
                    <option value="after">بعد</option>
                    <option value="before">قبل</option>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small mb-0">روز</label>
                <input class="form-control form-control-sm ltr" name="offset_days" inputmode="numeric" value="30">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">ساعت</label>
                <input class="form-control form-control-sm ltr" type="time" name="time_of_day" value="10:00">
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="enabled" value="1" id="new-rule-enabled">
                    <label class="form-check-label small" for="new-rule-enabled">فعال</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label small mb-0">متن پیامک</label>
                <textarea class="form-control form-control-sm" name="template" rows="2" placeholder="{full_name} عزیز، ..."></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-sm btn-primary">افزودن قاعده</button>
            </div>
        </form>
    </div>
</div>

<form method="get" action="<?= e(url('/admin/club-reminders')) ?>" class="row g-2 mb-2 align-items-end">
    <div class="col-6 col-md-2">
        <label class="form-label small mb-0">وضعیت</label>
        <select class="form-select form-select-sm" name="status">
            <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label small mb-0">نوع</label>
        <select class="form-select form-select-sm" name="kind">
            <option value="">همه</option>
            <?php foreach ($kinds as $kind => $label): ?>
                <option value="<?= e($kind) ?>" <?= $filters['kind'] === $kind ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-4 col-md-2">
        <button class="btn btn-sm btn-secondary w-100">فیلتر</button>
    </div>
</form>

<div class="card mb-4">
    <div class="card-header bg-white fw-bold">صف یادآوری‌ها</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>مشتری</th><th>نوع</th><th>موعد</th><th>وضعیت</th><th>پیام</th></tr></thead>
            <tbody>
            <?php foreach ($queue as $row): ?>
                <tr>
                    <td>
                        <a href="<?= e(url('/customers/show?id=' . (int) $row['customer_id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a>
                        <span class="text-muted small ltr d-block"><?= e((string) $row['phone_number']) ?></span>
                    </td>
                    <td class="small"><?= e(ReminderSchedule::kindLabel((string) $row['kind'])) ?></td>
                    <td class="small ltr"><?= e(Jalali::formatDateTime($row['due_at'])) ?></td>
                    <td><span class="badge text-bg-<?= e($statusColors[$row['status']] ?? 'secondary') ?>"><?= e($statusLabels[$row['status']] ?? $row['status']) ?></span></td>
                    <td class="small text-muted"><?= e(mb_substr((string) ($row['message'] ?? ''), 0, 80)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$queue): ?><tr><td colspan="5" class="text-muted">ردیفی با این فیلتر نیست.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">کمپین پیامکی گروهی</div>
            <div class="card-body">
                <form method="post" action="<?= e(url('/admin/club-reminders/campaign')) ?>" class="row g-2">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                    <div class="col-md-6">
                        <label class="form-label small mb-0">بخش مشتریان</label>
                        <select class="form-select form-select-sm" name="segment">
                            <option value="">همه مشتریان</option>
                            <?php foreach ($segments as $segment): ?>
                                <option value="<?= e((string) $segment['code']) ?>" <?= $prefillSegment === $segment['code'] ? 'selected' : '' ?>><?= e((string) $segment['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-0">سطح</label>
                        <select class="form-select form-select-sm" name="tier_id">
                            <option value="">همه سطح‌ها</option>
                            <?php foreach ($tiers as $tier): ?>
                                <option value="<?= e($tier['id']) ?>"><?= e((string) $tier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-0">جستجو (نام/موبایل)</label>
                        <input class="form-control form-control-sm" name="q" placeholder="اختیاری">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-0">ماه تولد (۱ تا ۱۲)</label>
                        <input class="form-control form-control-sm ltr" name="birthday_month" inputmode="numeric" placeholder="اختیاری">
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-0">متن پیامک</label>
                        <textarea class="form-control form-control-sm" name="message" rows="3" placeholder="متن کمپین…"></textarea>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" name="mode" value="preview">پیش‌نمایش تعداد</button>
                        <button class="btn btn-sm btn-primary" name="mode" value="send" data-confirm="پیامک‌ها واقعاً ارسال شوند؟">ارسال واقعی</button>
                    </div>
                </form>
                <div class="form-text mt-2">به سقف ارسال روزانه و حداقل فاصله بین پیام‌ها (تنظیمات باشگاه) احترام گذاشته می‌شود.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">ارسال آزمایشی</div>
            <div class="card-body">
                <form method="post" action="<?= e(url('/admin/club-reminders/test')) ?>" class="row g-2">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                    <div class="col-12">
                        <label class="form-label small mb-0">موبایل</label>
                        <input class="form-control form-control-sm ltr" name="phone" placeholder="09xxxxxxxxx" inputmode="numeric">
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-0">متن</label>
                        <input class="form-control form-control-sm" name="message" value="پیام آزمایشی باشگاه مشتریان">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-sm btn-outline-dark">ارسال آزمایشی</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
