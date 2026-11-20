<?php

use App\Core\Jalali;

$rows = $rows ?? [];
$totals = $totals ?? [];
$groupBy = $groupBy ?? 'day';
$rate = (float) ($totals['earned'] ?? 0) > 0 ? round((float) $totals['redeemed'] / (float) $totals['earned'] * 100, 1) : 0.0;
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">گزارش‌ها / باشگاه مشتریان</div>
        <h1 class="h3 mb-0">کش‌بک صادر، مصرف و انقضا</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-success" href="<?= e(url('/reports/club/export?' . http_build_query(array_merge($_GET, ['type' => 'earn-redeem'])))) ?>">خروجی CSV</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/club')) ?>">داشبورد باشگاه</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <form class="row g-2 align-items-end" method="get" action="<?= e(url('/reports/club/earn-redeem')) ?>">
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="از تاریخ" name="date_from" value="<?= e(Jalali::formatDate($from)) ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="تا تاریخ" name="date_to" value="<?= e(Jalali::formatDate($to)) ?>"></div>
        <input type="hidden" name="group" value="<?= e($groupBy) ?>">
        <div class="col-md-2"><button class="btn btn-secondary w-100">اعمال</button></div>
        <div class="col-md-4 d-flex gap-2">
            <a class="btn btn-sm <?= $groupBy === 'day' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(url('/reports/club/earn-redeem?' . http_build_query(array_merge($_GET, ['group' => 'day'])))) ?>">روزانه</a>
            <a class="btn btn-sm <?= $groupBy === 'month' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(url('/reports/club/earn-redeem?' . http_build_query(array_merge($_GET, ['group' => 'month'])))) ?>">ماهانه</a>
        </div>
    </form>
</div></div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'صادرشده' => money((float) ($totals['earned'] ?? 0)),
        'مصرف‌شده' => money((float) ($totals['redeemed'] ?? 0)),
        'منقضی‌شده' => money((float) ($totals['expired'] ?? 0)),
        'برگشتی' => money((float) ($totals['reversed'] ?? 0)),
        'نرخ مصرف' => number_format($rate, 1) . '٪',
    ] as $label => $value): ?>
        <div class="col-md-4 col-xl-2"><div class="card stat h-100"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e($value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="card"><div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
        <thead><tr><th><?= $groupBy === 'month' ? 'ماه' : 'تاریخ' ?></th><th>صادرشده</th><th>مصرف‌شده</th><th>منقضی</th><th>برگشتی</th><th>مانده خالص</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
            $bucket = (string) $row['bucket'];
            $label = $groupBy === 'month' ? $bucket : Jalali::formatDate($bucket);
            $net = (float) $row['earned'] - (float) $row['redeemed'] - (float) $row['expired'] - (float) $row['reversed'];
            ?>
            <tr>
                <td class="ltr"><?= e($label) ?></td>
                <td class="ltr"><?= e(money((float) $row['earned'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['redeemed'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['expired'])) ?></td>
                <td class="ltr"><?= e(money((float) $row['reversed'])) ?></td>
                <td class="ltr fw-bold"><?= e(money($net)) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="text-muted">در این بازه تراکنش کیف پولی ثبت نشده است.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
