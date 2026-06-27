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
        ['پیگیری‌های امروز', $reminderStats['today'] ?? 0, 'bi-calendar-check'],
        ['پیگیری‌های معوق', $reminderStats['overdue'] ?? 0, 'bi-exclamation-triangle'],
        ['یادآوری‌های باز', $reminderStats['pending'] ?? 0, 'bi-bell', url('/reminders')],
        ['سررسیدهای امروز', $dueDateStats['today_count'] ?? 0, 'bi-calendar-day', url('/due-dates?scope=today')],
        ['سررسیدهای فردا', $dueDateStats['tomorrow_count'] ?? 0, 'bi-calendar2', url('/due-dates?scope=tomorrow')],
        ['سررسیدهای معوق', $dueDateStats['overdue_count'] ?? 0, 'bi-calendar-x', url('/due-dates?scope=overdue')],
        ['مجموع مبلغ امروز', money($dueDateStats['today_amount'] ?? 0) . ' ریال', 'bi-cash', url('/due-dates?scope=today')],
        ['چک‌های در انتظار', $dueDateStats['pending_checks'] ?? 0, 'bi-bank', url('/due-dates?due_type=check&status=pending')],
        ['اقساط معوق', $dueDateStats['overdue_installments'] ?? 0, 'bi-exclamation-circle', url('/due-dates?due_type=installment&status=overdue')],
    ];
    foreach ($cards as $card): ?>
        <?php [$label, $value, $icon, $link] = array_pad($card, 4, null); ?>
        <div class="col-md-4 col-xl-3">
            <div class="card stat h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small"><?= e($label) ?></span>
                        <i class="bi <?= e($icon) ?>"></i>
                    </div>
                    <?php if ($link): ?>
                        <a class="fs-5 fw-bold text-decoration-none" href="<?= e($link) ?>"><?= e($value) ?></a>
                    <?php else: ?>
                        <div class="fs-5 fw-bold"><?= e($value) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
