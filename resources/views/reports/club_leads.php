<?php

use App\Core\Jalali;

$funnel = $funnel ?? [];
$sources = $sources ?? [];
$cycle = $cycle ?? ['won' => 0, 'average_days' => 0];

$totalLeads = 0;
$wonLeads = 0;
foreach ($funnel as $stage) {
    $totalLeads += (int) $stage['leads'];
}
$wonLeads = (int) ($cycle['won'] ?? 0);
$conversion = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0.0;
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">گزارش‌ها / باشگاه مشتریان</div>
        <h1 class="h3 mb-0">قیف سرنخ فروش</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-success" href="<?= e(url('/reports/club/export?' . http_build_query(array_merge($_GET, ['type' => 'leads'])))) ?>">خروجی CSV</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/followups')) ?>">پیگیری‌ها</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <form class="row g-2 align-items-end" method="get" action="<?= e(url('/reports/club/leads')) ?>">
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="از تاریخ" name="date_from" value="<?= e(Jalali::formatDate($from)) ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="تا تاریخ" name="date_to" value="<?= e(Jalali::formatDate($to)) ?>"></div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">اعمال</button></div>
    </form>
</div></div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'کل سرنخ‌ها' => number_format($totalLeads),
        'فروش نهایی' => number_format($wonLeads),
        'نرخ تبدیل' => number_format($conversion, 1) . '٪',
        'میانگین چرخه فروش' => number_format((float) ($cycle['average_days'] ?? 0), 1) . ' روز',
    ] as $label => $value): ?>
        <div class="col-md-3"><div class="card stat h-100"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e($value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">مراحل</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>مرحله</th><th>تعداد</th><th>مبلغ بالقوه</th><th>میانگین امتیاز</th></tr></thead>
                    <tbody>
                    <?php foreach ($funnel as $stage): ?>
                        <tr>
                            <td><?= e((string) $stage['label']) ?></td>
                            <td class="ltr"><?= e(number_format((int) $stage['leads'])) ?></td>
                            <td class="ltr"><?= e(money((float) $stage['amount'])) ?></td>
                            <td class="ltr"><?= e(number_format((float) $stage['average_score'], 1)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$funnel): ?><tr><td colspan="4" class="text-muted">پیگیری فروشی در این بازه نیست.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-bold">منبع سرنخ</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>منبع</th><th>سرنخ</th><th>موفق</th><th>نرخ تبدیل</th><th>مبلغ موفق</th></tr></thead>
                    <tbody>
                    <?php foreach ($sources as $source): ?>
                        <tr>
                            <td><?= e((string) $source['label']) ?></td>
                            <td class="ltr"><?= e(number_format((int) $source['leads'])) ?></td>
                            <td class="ltr"><?= e(number_format((int) $source['won'])) ?></td>
                            <td class="ltr"><?= e(number_format((float) $source['conversion'], 1)) ?>٪</td>
                            <td class="ltr"><?= e(money((float) $source['won_amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$sources): ?><tr><td colspan="5" class="text-muted">داده‌ای نیست.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small">برای ثبت منبع و امتیاز سرنخ، از <a href="<?= e(url('/followups/create')) ?>">فرم ثبت پیگیری</a> استفاده کنید.</div>
        </div>
    </div>
</div>
