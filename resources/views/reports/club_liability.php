<?php

use App\Core\Jalali;
use App\Services\RfmCalculator;

$rows = $rows ?? [];
$totals = $totals ?? [];
$filters = $filters ?? [];
$tiers = $tiers ?? [];
$segments = $segments ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">گزارش‌ها / باشگاه مشتریان</div>
        <h1 class="h3 mb-0">بدهی امتیاز و انقضا</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-success" href="<?= e(url('/reports/club/export?' . http_build_query(array_merge($_GET, ['type' => 'liability'])))) ?>">خروجی CSV</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/club')) ?>">داشبورد باشگاه</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'کل اعتبار نزد مشتریان' => money((float) ($totals['total'] ?? 0)),
        'مشتریان دارای اعتبار' => number_format((int) ($totals['customers'] ?? 0)),
        'انقضا تا ۳۰ روز' => money((float) ($totals['d30'] ?? 0)),
        '۳۱ تا ۹۰ روز' => money((float) ($totals['d90'] ?? 0)),
        'بیش از ۹۰ روز' => money((float) ($totals['later'] ?? 0)),
        'بدون انقضا' => money((float) ($totals['never'] ?? 0)),
    ] as $label => $value): ?>
        <div class="col-md-4 col-xl-2"><div class="card stat h-100"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e($value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get" action="<?= e(url('/reports/club/liability')) ?>">
        <div class="col-md-3">
            <select class="form-select" name="segment">
                <option value="">همه بخش‌ها</option>
                <?php foreach ($segments as $segment): ?>
                    <option value="<?= e((string) $segment['code']) ?>" <?= ($filters['segment'] ?? '') === $segment['code'] ? 'selected' : '' ?>><?= e((string) $segment['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="tier_id">
                <option value="">همه سطح‌ها</option>
                <?php foreach ($tiers as $tier): ?>
                    <option value="<?= e($tier['id']) ?>" <?= (string) ($filters['tier_id'] ?? '') === (string) $tier['id'] ? 'selected' : '' ?>><?= e((string) $tier['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">اعمال</button></div>
    </form>
</div></div>

<div class="card"><div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
        <thead><tr><th>مشتری</th><th>موبایل</th><th>سطح</th><th>بخش</th><th>موجودی</th><th>تا ۳۰ روز</th><th>۳۱ تا ۹۰</th><th>بیشتر</th><th>بدون انقضا</th><th>نزدیک‌ترین انقضا</th></tr></thead>
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
                <td class="ltr fw-bold"><?= e(money((float) $row['wallet_balance'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['bucket_30'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['bucket_90'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['bucket_later'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['bucket_never'])) ?></td>
                <td class="small ltr"><?= !empty($row['next_expiry']) ? e(Jalali::formatDate(substr((string) $row['next_expiry'], 0, 10))) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="10" class="text-muted">اعتباری نزد مشتریان نیست.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
