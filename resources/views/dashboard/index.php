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
        ['مشتریان', $stats['customers'], 'bi-people', 'ثبت‌شده در سیستم'],
        ['خریدها', $stats['purchases'], 'bi-bag-check', 'تراکنش‌های ثبت‌شده'],
        ['جمع خرید', money($stats['purchase_amount']) . ' ریال', 'bi-cash-stack', 'مجموع فروش'],
        ['جمع کش‌بک', money($stats['cashback']) . ' ریال', 'bi-stars', 'اعتبار اعطا شده'],
        ['موجودی کیف پول‌ها', money($stats['wallets']) . ' ریال', 'bi-wallet2', 'مانده کل'],
        ['تولدهای امروز', $stats['birthdays_today'], 'bi-gift', 'مشتریان امروز'],
    ];
    foreach ($cards as [$label, $value, $icon, $hint]): ?>
        <div class="col-md-4 col-xl-2">
            <div class="card stat h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted small"><?= e($label) ?></div>
                        <span class="badge rounded-pill text-bg-light"><i class="bi <?= e($icon) ?>"></i></span>
                    </div>
                    <div class="fs-5 fw-bold"><?= e($value) ?></div>
                    <div class="text-muted small mt-2"><?= e($hint) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-3 mt-2">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 fw-bold mb-0">میانبرهای کاری</h3>
                    <span class="badge text-bg-primary">روزانه</span>
                </div>
                <div class="row g-2">
                    <div class="col-md-4"><a class="btn btn-outline-secondary w-100 py-3" href="<?= e(url('/customers')) ?>"><i class="bi bi-search"></i> جستجوی مشتری</a></div>
                    <div class="col-md-4"><a class="btn btn-outline-secondary w-100 py-3" href="<?= e(url('/reports')) ?>"><i class="bi bi-bar-chart-line"></i> گزارش‌ها</a></div>
                    <div class="col-md-4"><a class="btn btn-outline-secondary w-100 py-3" href="<?= e(url('/sms/logs')) ?>"><i class="bi bi-chat-dots"></i> پیامک‌ها</a></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h5 fw-bold mb-2">وضعیت امروز</h3>
                <p class="text-muted mb-0">از منوی سمت راست برای ثبت مشتری، خرید، کاهش کیف پول و بررسی گزارش‌ها استفاده کنید.</p>
            </div>
        </div>
    </div>
</div>
