<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="text-muted small mb-1">نمای کلی سیستم</div>
        <h2 class="h3 fw-bold mb-0">داشبورد مدیریتی</h2>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-primary" href="<?= e(url('/customers/create')) ?>"><i class="bi bi-person-plus"></i> افزودن مشتری</a>
        <a class="btn btn-outline-primary" href="<?= e(url('/purchases/create')) ?>"><i class="bi bi-receipt"></i> ثبت خرید</a>
    </div>
</div>
<div class="row g-3">
    <?php
    $cards = [
        ['مشتریان', $stats['customers'], 'bi-people'],
        ['خریدهای فعال', $stats['purchases'], 'bi-bag-check'],
        ['جمع خرید', money($stats['purchase_amount']) . ' ریال', 'bi-cash-stack'],
        ['جمع کش‌بک', money($stats['cashback']) . ' ریال', 'bi-stars'],
        ['موجودی کیف پول‌ها', money($stats['wallets']) . ' ریال', 'bi-wallet2'],
        ['کش‌بک این ماه', money($stats['cashback_month'] ?? 0) . ' ریال', 'bi-calendar-month'],
        ['کسر این ماه', money($stats['reductions_month'] ?? 0) . ' ریال', 'bi-arrow-down-circle'],
        ['بدهی کیف پول (تعهد)', money($stats['outstanding_liability'] ?? 0) . ' ریال', 'bi-piggy-bank'],
        ['تولدهای امروز', $stats['birthdays_today'], 'bi-gift'],
    ];
    foreach ($cards as [$label, $value, $icon]): ?>
        <div class="col-md-4 col-xl-3">
            <div class="card stat h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small"><?= e($label) ?></span>
                        <i class="bi <?= e($icon) ?>"></i>
                    </div>
                    <div class="fs-5 fw-bold"><?= e($value) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
