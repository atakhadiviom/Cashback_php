<?php

use App\Core\Jalali;
use App\Services\ReminderSchedule;

$rows = $rows ?? [];
$stats = $stats ?? [];
$recent = $recent ?? [];

$totalSent = 0;
$totalReturned = 0;
foreach ($rows as $row) {
    $totalSent += (int) $row['sent'];
    $totalReturned += (int) $row['returned'];
}
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">گزارش‌ها / باشگاه مشتریان</div>
        <h1 class="h3 mb-0">اثربخشی یادآوری‌ها</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-success" href="<?= e(url('/reports/club/export?' . http_build_query(array_merge($_GET, ['type' => 'reminders'])))) ?>">خروجی CSV</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/club-reminders')) ?>">مدیریت قواعد</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <form class="row g-2 align-items-end" method="get" action="<?= e(url('/reports/club/reminders')) ?>">
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="از تاریخ" name="date_from" value="<?= e(Jalali::formatDate($from)) ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="تا تاریخ" name="date_to" value="<?= e(Jalali::formatDate($to)) ?>"></div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">اعمال</button></div>
    </form>
</div></div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'یادآوری ارسال‌شده' => number_format($totalSent),
        'بازگشت مشتری' => number_format($totalReturned),
        'نرخ بازگشت' => number_format($totalSent > 0 ? $totalReturned / $totalSent * 100 : 0, 1) . '٪',
        'در صف' => number_format((int) ($stats['pending'] ?? 0)),
        'رد‌شده' => number_format((int) ($stats['skipped'] ?? 0)),
        'ناموفق' => number_format((int) ($stats['failed'] ?? 0)),
    ] as $label => $value): ?>
        <div class="col-md-4 col-xl-2"><div class="card stat h-100"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e($value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">به تفکیک نوع</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>نوع</th><th>ارسال</th><th>بازگشت</th><th>نرخ</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e((string) $row['label']) ?></td>
                            <td class="ltr"><?= e(number_format((int) $row['sent'])) ?></td>
                            <td class="ltr"><?= e(number_format((int) $row['returned'])) ?></td>
                            <td class="ltr"><?= e(number_format((float) $row['rate'], 1)) ?>٪</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="4" class="text-muted">در این بازه یادآوری موفقی ارسال نشده است.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">آخرین یادآوری‌های ارسال‌شده</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>مشتری</th><th>نوع</th><th>زمان ارسال</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><a href="<?= e(url('/customers/show?id=' . (int) $row['customer_id'])) ?>"><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></a></td>
                            <td class="small"><?= e(ReminderSchedule::kindLabel((string) $row['kind'])) ?></td>
                            <td class="small ltr"><?= e(Jalali::formatDateTime($row['sent_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?><tr><td colspan="3" class="text-muted">موردی نیست.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
