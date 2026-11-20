<?php

use App\Core\Jalali;

$distribution = $distribution ?? [];
$movements = $movements ?? [];
$tierless = (int) ($tierless ?? 0);

$totalCustomers = $tierless;
$totalWallet = 0.0;
foreach ($distribution as $row) {
    $totalCustomers += (int) $row['customers'];
    $totalWallet += (float) $row['wallet'];
}
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">گزارش‌ها / باشگاه مشتریان</div>
        <h1 class="h3 mb-0">توزیع و تغییر سطح</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-success" href="<?= e(url('/reports/club/export?' . http_build_query(array_merge($_GET, ['type' => 'tiers'])))) ?>">خروجی CSV</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/loyalty')) ?>">مدیریت سطح‌ها</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'کل مشتریان' => number_format($totalCustomers),
        'دارای سطح' => number_format(max(0, $totalCustomers - $tierless)),
        'بدون سطح' => number_format($tierless),
        'اعتبار کیف پول‌ها' => money($totalWallet),
    ] as $label => $value): ?>
        <div class="col-md-3"><div class="card stat h-100"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e($value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">توزیع فعلی</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>سطح</th><th>درصد</th><th>مشتری</th><th>سهم</th><th>موجودی</th></tr></thead>
                    <tbody>
                    <?php foreach ($distribution as $row): ?>
                        <tr>
                            <td><?= e((string) $row['name']) ?></td>
                            <td class="ltr"><?= e(number_format((float) $row['cashback_percent'], 2)) ?>٪</td>
                            <td class="ltr"><?= e(number_format((int) $row['customers'])) ?></td>
                            <td class="ltr"><?= e($totalCustomers > 0 ? number_format((int) $row['customers'] / $totalCustomers * 100, 1) : '0') ?>٪</td>
                            <td class="ltr"><?= e(money((float) $row['wallet'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$distribution): ?><tr><td colspan="5" class="text-muted">سطحی تعریف نشده است.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small"><a href="<?= e(url('/customers?tier_id=none')) ?>">مشاهده مشتریان بدون سطح</a></div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">تغییرات سطح در بازه</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>مشتری</th><th>از</th><th>به</th><th>مبنا</th><th>مبلغ مبنا</th><th>زمان</th></tr></thead>
                    <tbody>
                    <?php foreach ($movements as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/customers/show?id=' . (int) $row['customer_id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a></td>
                            <td><?= e((string) ($row['old_tier_name'] ?? '—')) ?></td>
                            <td><?= e((string) ($row['new_tier_name'] ?? '—')) ?></td>
                            <td class="small"><?= e($row['basis'] === 'rolling' ? 'بازه غلتان' : 'از ابتدا') ?></td>
                            <td class="ltr"><?= e(money((float) $row['spend'])) ?></td>
                            <td class="small ltr"><?= e(Jalali::formatDateTime($row['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$movements): ?><tr><td colspan="6" class="text-muted">تغییر سطحی در این بازه ثبت نشده است.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
