<?php

use App\Core\Jalali;
use App\Services\RfmCalculator;

$rows = $rows ?? [];
$summary = $summary ?? [];
$filters = $filters ?? [];
$tiers = $tiers ?? [];
$segments = $segments ?? [];

$query = static function (array $extra = []): string {
    return url('/reports/club/purchases?' . http_build_query(array_merge($_GET, $extra)));
};
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">گزارش‌ها / باشگاه مشتریان</div>
        <h1 class="h3 mb-0">خرید مشتریان</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-success" href="<?= e(url('/reports/club/export?' . http_build_query(array_merge($_GET, ['type' => 'purchases'])))) ?>">خروجی CSV</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/club')) ?>">داشبورد باشگاه</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get" action="<?= e(url('/reports/club/purchases')) ?>">
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="از تاریخ" name="date_from" value="<?= e(Jalali::formatDate($from)) ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="تا تاریخ" name="date_to" value="<?= e(Jalali::formatDate($to)) ?>"></div>
        <div class="col-md-2">
            <select class="form-select" name="tier_id">
                <option value="">همه سطح‌ها</option>
                <option value="none" <?= ($filters['tier_id'] ?? '') === 'none' ? 'selected' : '' ?>>بدون سطح</option>
                <?php foreach ($tiers as $tier): ?>
                    <option value="<?= e($tier['id']) ?>" <?= (string) ($filters['tier_id'] ?? '') === (string) $tier['id'] ? 'selected' : '' ?>><?= e((string) $tier['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="segment">
                <option value="">همه بخش‌ها</option>
                <?php foreach ($segments as $segment): ?>
                    <option value="<?= e((string) $segment['code']) ?>" <?= ($filters['segment'] ?? '') === $segment['code'] ? 'selected' : '' ?>><?= e((string) $segment['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><input class="form-control" name="q" placeholder="نام یا موبایل" value="<?= e((string) ($filters['q'] ?? '')) ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="min_total" data-money placeholder="حداقل مبلغ خرید" value="<?= e((string) ($filters['min_total'] ?? '')) ?>"></div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">اعمال</button></div>
    </form>
</div></div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'مشتریان خریدار' => number_format((int) ($summary['customers'] ?? 0)),
        'تعداد خرید' => number_format((int) ($summary['purchases'] ?? 0)),
        'مبلغ کل خرید' => money((float) ($summary['total'] ?? 0)),
        'کش‌بک اعطایی' => money((float) ($summary['cashback'] ?? 0)),
        'میانگین هر خرید' => money((float) ($summary['average'] ?? 0)),
    ] as $label => $value): ?>
        <div class="col-md-4 col-xl-2"><div class="card stat h-100"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e($value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>مشتری</th><th>موبایل</th><th>سطح</th><th>بخش</th><th>تعداد خرید</th><th>مبلغ خرید</th><th>کش‌بک</th><th>میانگین</th><th>آخرین خرید</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><a href="<?= e(url('/customers/show?id=' . (int) $row['id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a></td>
                <td class="ltr small"><?= e((string) $row['phone_number']) ?></td>
                <td><?= e((string) ($row['tier_name'] ?? '—')) ?></td>
                <td>
                    <?php if (!empty($row['segment_code'])): ?>
                        <span class="badge text-bg-<?= e(RfmCalculator::segmentColor((string) $row['segment_code'])) ?>"><?= e(RfmCalculator::segmentLabel((string) $row['segment_code'])) ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="ltr"><?= e(number_format((int) $row['purchases'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['total'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['cashback'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['average'])) ?></td>
                <td class="ltr small"><?= e(Jalali::formatDateTime($row['last_purchase'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9" class="text-muted">در این بازه خریدی ثبت نشده است.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
