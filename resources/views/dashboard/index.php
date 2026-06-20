<?php use App\Core\Auth; use App\Core\Csrf; ?>
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
<?php if (Auth::isAdmin()): ?>
<div class="card mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>کارهای زمان‌بندی‌شده (بدون cron سرور)</span>
        <form method="post" action="<?= e(url('/admin/cron/run')) ?>" class="d-inline">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <input type="hidden" name="task" value="all">
            <button class="btn btn-sm btn-outline-primary">اجرای دستی همه</button>
        </form>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">با باز کردن داشبورد توسط مدیر، پیامک تولد، یادآوری تمدید قرارداد و تلاش مجدد پیامک (هر ۱۵ دقیقه) به‌صورت خودکار اجرا می‌شوند — نیازی به cron در cPanel نیست.</p>
        <?php if (!empty($cronMessages)): ?>
            <div class="alert alert-info small mb-3">
                <?php foreach ($cronMessages as $line): ?><div><?= e($line) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="row g-2 small">
            <?php
            $labels = ['birthday' => 'پیامک تولد', 'contract_renewal' => 'یادآوری قرارداد', 'sms_retry' => 'تلاش مجدد پیامک'];
            foreach ($labels as $key => $label):
            ?>
                <div class="col-md-4"><span class="text-muted"><?= e($label) ?>:</span> <?= e($cronState[$key] ?? 'هرگز') ?></div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($cronWebUrl)): ?>
            <div class="mt-3 small">
                <div class="text-muted mb-1">آدرس برای سرویس‌های خارجی (cron-job.org):</div>
                <code class="user-select-all d-block ltr"><?= e($cronWebUrl) ?></code>
            </div>
        <?php else: ?>
            <div class="mt-3 small text-muted">برای فراخوانی از اینترنت، در <code>cashback_config.php</code> مقدار <code>cron.web_token</code> را تنظیم کنید.</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
