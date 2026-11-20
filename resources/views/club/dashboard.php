<?php

use App\Core\Jalali;
use App\Services\RfmCalculator;

$kpi = $kpi ?? [];
$tierDistribution = $tierDistribution ?? [];
$segmentDistribution = $segmentDistribution ?? [];
$segmentCatalog = $segmentCatalog ?? [];
$topBuyers = $topBuyers ?? [];
$atRisk = $atRisk ?? [];
$expiring = $expiring ?? [];
$birthdays = $birthdays ?? [];
$recentReminders = $recentReminders ?? [];
$club = $club ?? [];
$tierless = (int) ($tierless ?? 0);

$segmentMeta = [];
foreach ($segmentCatalog as $segment) {
    $segmentMeta[(string) $segment['code']] = $segment;
}
$totalMembers = max(1, (int) ($kpi['members'] ?? 0));
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">باشگاه مشتریان</div>
        <h1 class="h3 mb-0">داشبورد باشگاه</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-primary" href="<?= e(url('/club/segments')) ?>">بخش‌بندی مشتریان</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/reports/club/purchases')) ?>">خرید مشتریان</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/reports/club/liability')) ?>">بدهی امتیاز</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/reports/club/earn-redeem')) ?>">صادر و مصرف</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/reports/club/leads')) ?>">قیف سرنخ</a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/reports/club/reminders')) ?>">یادآوری‌ها</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/club-reminders')) ?>">قواعد یادآوری</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['اعضای باشگاه', number_format((int) ($kpi['members'] ?? 0)), ''],
        ['فعال ۹۰ روز', number_format((int) ($kpi['active_90'] ?? 0)), ''],
        ['عضو جدید این ماه', number_format((int) ($kpi['new_this_month'] ?? 0)), ''],
        ['بدهی امتیاز (ریال)', money((float) ($kpi['wallet_liability'] ?? 0)), 'text-warning'],
        ['کش‌بک صادر ۳۰ روز', money((float) ($kpi['issued_30'] ?? 0)), 'text-success'],
        ['مصرف امتیاز ۳۰ روز', money((float) ($kpi['redeemed_30'] ?? 0)), ''],
        ['نرخ مصرف', number_format((float) ($kpi['redemption_rate'] ?? 0), 1) . '٪', ''],
        ['میانگین سبد', money((float) ($kpi['basket_30'] ?? 0)), ''],
    ] as [$label, $value, $class]): ?>
        <div class="col-md-4 col-xl-3">
            <div class="card stat h-100"><div class="card-body">
                <div class="small text-muted"><?= e($label) ?></div>
                <div class="fw-bold mt-2 <?= e($class) ?>"><?= e($value) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">توزیع سطح مشتریان</span>
                <a class="small" href="<?= e(url('/admin/loyalty')) ?>">مدیریت سطح‌ها</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>سطح</th><th>درصد</th><th>تعداد</th><th>موجودی</th></tr></thead>
                    <tbody>
                    <?php foreach ($tierDistribution as $row): ?>
                        <tr>
                            <td><?= e((string) $row['name']) ?></td>
                            <td class="ltr"><?= e(number_format((float) $row['cashback_percent'], 2)) ?>٪</td>
                            <td class="ltr"><?= e(number_format((int) $row['customers'])) ?></td>
                            <td class="ltr"><?= e(money((float) $row['wallet'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tierDistribution): ?><tr><td colspan="4" class="text-muted">سطحی تعریف نشده است.</td></tr><?php endif; ?>
                    <?php if ($tierless > 0): ?>
                        <tr><td class="text-muted">بدون سطح</td><td>—</td><td class="ltr"><?= e(number_format($tierless)) ?></td><td>—</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">بخش‌بندی RFM</span>
                <span class="small text-muted">
                    <?= $lastSegmentAt ? 'آخرین محاسبه: ' . e(Jalali::formatDateTime((string) $lastSegmentAt)) : 'هنوز محاسبه نشده' ?>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>بخش</th><th>تعداد</th><th>سهم</th><th>فروش تجمعی</th></tr></thead>
                    <tbody>
                    <?php foreach ($segmentDistribution as $row): ?>
                        <?php $code = (string) $row['segment_code']; ?>
                        <tr>
                            <td><span class="badge text-bg-<?= e(RfmCalculator::segmentColor($code)) ?>"><?= e((string) ($segmentMeta[$code]['name'] ?? RfmCalculator::segmentLabel($code))) ?></span></td>
                            <td class="ltr"><?= e(number_format((int) $row['members'])) ?></td>
                            <td class="ltr"><?= e(number_format((int) $row['members'] / $totalMembers * 100, 1)) ?>٪</td>
                            <td class="ltr"><?= e(money((float) $row['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$segmentDistribution): ?>
                        <tr><td colspan="4" class="text-muted">برای فعال‌سازی، در «بخش‌بندی مشتریان» دکمه بازمحاسبه را بزنید.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">۱۰ خریدار برتر</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>مشتری</th><th>خرید</th><th>مبلغ</th></tr></thead>
                    <tbody>
                    <?php foreach ($topBuyers as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/customers/show?id=' . (int) $row['id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a></td>
                            <td class="ltr"><?= e(number_format((int) $row['purchases'])) ?></td>
                            <td class="ltr"><?= e(money((float) $row['total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$topBuyers): ?><tr><td colspan="3" class="text-muted">داده‌ای نیست.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold text-danger">در خطر / نباید از دست برود</span>
                <a class="small" href="<?= e(url('/customers?segment=at_risk')) ?>">همه</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>مشتری</th><th>روز از آخرین خرید</th><th>فروش تجمعی</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($atRisk as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/customers/show?id=' . (int) $row['id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a></td>
                            <td class="ltr"><?= e(number_format((int) $row['recency_days'])) ?></td>
                            <td class="ltr"><?= e(money((float) $row['monetary'])) ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/followups/create?customer_id=' . (int) $row['id'])) ?>">پیگیری</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$atRisk): ?><tr><td colspan="4" class="text-muted">موردی پیدا نشد.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">امتیاز در حال انقضا (۳۰ روز)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>مشتری</th><th>مبلغ</th><th>انقضا</th></tr></thead>
                    <tbody>
                    <?php foreach ($expiring as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/customers/show?id=' . (int) $row['id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a></td>
                            <td class="ltr"><?= e(money((float) $row['expiring_amount'])) ?></td>
                            <td class="small ltr"><?= e(Jalali::formatDate(substr((string) $row['expires_at'], 0, 10))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$expiring): ?><tr><td colspan="3" class="text-muted">موردی نیست.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">تولدهای این ماه</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>مشتری</th><th>موبایل</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($birthdays, 0, 10) as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/customers/show?id=' . (int) $row['id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a></td>
                            <td class="ltr"><?= e((string) $row['phone_number']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$birthdays): ?><tr><td colspan="2" class="text-muted">موردی نیست.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">یادآوری‌های ارسال‌شده</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>مشتری</th><th>نوع</th><th>زمان</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentReminders as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/customers/show?id=' . (int) $row['customer_id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a></td>
                            <td class="small"><?= e(\App\Services\ReminderSchedule::kindLabel((string) $row['kind'])) ?></td>
                            <td class="small ltr"><?= e(Jalali::formatDateTime($row['sent_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentReminders): ?><tr><td colspan="3" class="text-muted">موردی نیست.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (empty($club['enabled'])): ?>
    <div class="alert alert-warning mt-3">باشگاه مشتریان غیرفعال است؛ ارسال یادآوری و کمپین انجام نمی‌شود.</div>
<?php endif; ?>
