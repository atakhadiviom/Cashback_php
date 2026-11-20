<?php

use App\Core\Csrf;
use App\Core\Jalali;
use App\Services\RfmCalculator;

$catalog = $catalog ?? [];
$distribution = $distribution ?? [];
$thresholds = $thresholds ?? ['r' => [], 'f' => [], 'm' => []];
$lastComputedAt = $lastComputedAt ?? null;

$byCode = [];
foreach ($distribution as $row) {
    $byCode[(string) $row['segment_code']] = $row;
}
$total = 0;
foreach ($distribution as $row) {
    $total += (int) $row['members'];
}
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="text-muted small">باشگاه مشتریان</div>
        <h1 class="h3 mb-0">بخش‌بندی و طبقه‌بندی مشتریان</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('/club')) ?>">داشبورد باشگاه</a>
        <form method="post" action="<?= e(url('/club/segments/refresh')) ?>" class="d-inline">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <button class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> بازمحاسبه بخش‌بندی</button>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-4">
        <div><span class="text-muted small d-block">آخرین محاسبه</span><span class="fw-bold"><?= $lastComputedAt ? e(Jalali::formatDateTime((string) $lastComputedAt)) : 'هنوز انجام نشده' ?></span></div>
        <div><span class="text-muted small d-block">مشتریان بخش‌بندی‌شده</span><span class="fw-bold ltr"><?= e(number_format($total)) ?></span></div>
        <div>
            <span class="text-muted small d-block">آستانه‌های فعلی</span>
            <span class="small ltr" dir="ltr">
                R: <?= e(implode(' / ', array_map(static fn ($v) => (string) round((float) $v), $thresholds['r']))) ?> روز —
                F: <?= e(implode(' / ', array_map(static fn ($v) => (string) round((float) $v), $thresholds['f']))) ?> خرید —
                M: <?= e(implode(' / ', array_map(static fn ($v) => money((float) $v), $thresholds['m']))) ?>
            </span>
            <a class="small ms-2" href="<?= e(url('/admin/club')) ?>">ویرایش</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-bold">بخش‌های استاندارد RFM</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>بخش</th>
                    <th>توضیح</th>
                    <th>تعداد</th>
                    <th>سهم</th>
                    <th>فروش تجمعی</th>
                    <th>میانگین سبد</th>
                    <th>میانگین روز از آخرین خرید</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($catalog as $segment): ?>
                <?php
                $code = (string) $segment['code'];
                $row = $byCode[$code] ?? null;
                $members = $row !== null ? (int) $row['members'] : 0;
                ?>
                <tr>
                    <td><span class="badge text-bg-<?= e((string) ($segment['color'] ?: RfmCalculator::segmentColor($code))) ?>"><?= e((string) $segment['name']) ?></span></td>
                    <td class="small text-muted"><?= e((string) ($segment['description'] ?? '')) ?></td>
                    <td class="ltr"><?= e(number_format($members)) ?></td>
                    <td class="ltr"><?= e($total > 0 ? number_format($members / $total * 100, 1) : '0') ?>٪</td>
                    <td class="ltr"><?= e(money($row !== null ? (float) $row['revenue'] : 0)) ?></td>
                    <td class="ltr"><?= e(money($row !== null ? (float) $row['average'] : 0)) ?></td>
                    <td class="ltr"><?= e($row !== null ? number_format((float) $row['average_recency']) : '—') ?></td>
                    <td class="text-end">
                        <?php if ($members > 0): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/customers?segment=' . urlencode($code))) ?>">مشاهده مشتریان</a>
                            <a class="btn btn-sm btn-outline-success" href="<?= e(url('/admin/club-reminders?segment=' . urlencode($code))) ?>">کمپین</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$catalog): ?><tr><td colspan="8" class="text-muted">جدول بخش‌ها ساخته نشده است؛ مهاجرت ۰۷۳ را اجرا کنید.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-3 small">
    بخش‌بندی بر اساس سه معیار محاسبه می‌شود: <strong>R</strong> فاصله آخرین خرید،
    <strong>F</strong> تعداد خرید و <strong>M</strong> مبلغ کل خرید. هر معیار امتیاز ۱ تا ۵ می‌گیرد و ترکیب آن‌ها یکی از بخش‌های بالا را می‌سازد.
</div>
