<?php use App\Core\Csrf; use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="text-muted small mb-1">Health check and cron checks</div>
        <h1 class="h3 fw-bold mb-0">وضعیت سیستم</h1>
    </div>
    <form method="post" action="<?= e(url('/admin/cron/run')) ?>" class="d-inline">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
        <input type="hidden" name="task" value="all">
        <button class="btn btn-primary"><i class="bi bi-play-circle"></i> اجرای دستی همه</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">PHP</div>
                <div class="fs-5 fw-bold ltr text-end"><?= e($phpVersion) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Database</div>
                <div class="fs-6 fw-bold ltr text-end"><?= e($databaseName) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Cron state</div>
                <div class="small ltr text-end text-break"><?= e($cronStatePath) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($schemaMissing)): ?>
<div class="alert alert-danger d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <strong>اسکیمای دیتابیس ناقص است.</strong>
        برخی جداول مورد نیاز نسخه فعلی برنامه وجود ندارند. تا زمانی که مایگریشن اجرا نشود، داشبورد و بخش سررسیدها ممکن است خطا بدهند.
    </div>
    <form method="post" action="<?= e(url('/admin/migrations/run')) ?>" class="m-0">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
        <button class="btn btn-danger"><i class="bi bi-database-check"></i> اجرای مایگریشن‌های دیتابیس</button>
    </form>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-white fw-bold">بررسی جداول دیتابیس</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>بخش</th><th>جدول</th><th>فایل مایگریشن</th><th>وضعیت</th></tr></thead>
            <tbody>
            <?php foreach ($schemaChecks as $check): ?>
                <tr>
                    <td><?= e($check['label']) ?></td>
                    <td class="ltr text-end"><?= e($check['table']) ?></td>
                    <td class="ltr text-end"><?= e($check['migration']) ?></td>
                    <td><span class="badge bg-<?= $check['ok'] ? 'success' : 'danger' ?>"><?= $check['ok'] ? 'موجود' : 'ناموجود' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body border-top">
        <form method="post" action="<?= e(url('/admin/migrations/run')) ?>" class="d-inline">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <button class="btn btn-outline-primary btn-sm"><i class="bi bi-database-check"></i> اجرای مایگریشن‌های pending</button>
        </form>
        <span class="text-muted small ms-2">معادل دستور <code class="ltr">php database/migrate.php</code></span>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white fw-bold">بررسی سلامت</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>مورد</th><th>وضعیت</th><th>جزئیات</th></tr></thead>
            <tbody>
            <?php foreach ($healthChecks as $check): ?>
                <tr>
                    <td><?= e($check['label']) ?></td>
                    <td><span class="badge bg-<?= $check['ok'] ? 'success' : 'danger' ?>"><?= e($check['status']) ?></span></td>
                    <td class="small text-muted ltr text-break"><?= e($check['detail']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Cron checks</span>
        <span class="text-muted small">این وضعیت بر اساس آخرین اجرای ثبت‌شده در برنامه است.</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>کار</th><th>زمان‌بندی</th><th>آخرین اجرا</th><th>وضعیت</th><th>جزئیات</th></tr></thead>
            <tbody>
            <?php foreach ($cronChecks as $check): ?>
                <tr>
                    <td><?= e($check['label']) ?></td>
                    <td class="ltr text-end"><?= e($check['schedule']) ?></td>
                    <td class="ltr text-end"><?= e($check['last_run']) ?></td>
                    <td>
                        <?php if (!$check['enabled']): ?>
                            <span class="badge bg-secondary">غیرفعال</span>
                        <?php elseif ($check['due']): ?>
                            <span class="badge bg-warning text-dark">در انتظار اجرا</span>
                        <?php else: ?>
                            <span class="badge bg-success">به‌روز</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= e($check['detail']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body border-top small text-muted">
        <?php if (!empty($cronWebUrl)): ?>
            <div class="mb-2">آدرس اجرای خارجی:</div>
            <code class="user-select-all d-block ltr text-break"><?= e($cronWebUrl) ?></code>
        <?php else: ?>
            <div>برای فراخوانی از اینترنت، در <code>cashback_config.php</code> مقدار <code>cron.web_token</code> را تنظیم کنید.</div>
        <?php endif; ?>
    </div>

    <?php if (!$cpanelEnabled || empty($cpanelStatus['ok'])): ?>
    <div class="card-body border-top bg-light">
        <div class="alert alert-warning mb-0">
            <strong>هشدار:</strong> کرون‌جاب‌های خودکار در cPanel تنظیم نشده‌اند.
            <form method="post" action="<?= e(url('/admin/cron/setup-cpanel')) ?>" class="d-inline ms-2">
                <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                <button class="btn btn-sm btn-warning">تنظیم خودکار در cPanel</button>
            </form>
            <details class="mt-2">
                <summary class="text-decoration-underline">راهنمای دستی تنظیم کرون‌جاب</summary>
                <pre class="small mt-2 bg-white p-2 border"><code># Birthday SMS (Daily 08:00)
<?php
$cpanelAppRoot = trim((string) \config_value('cpanel.app_root', ''));
if ($cpanelAppRoot === '') {
    $cpanelHome = trim((string) \config_value('cpanel.home_dir', '/home/' . \config_value('cpanel.username', 'USER')));
    $cpanelAppRoot = rtrim($cpanelHome, '/') . '/' . \config_value('cpanel.domain', 'DOMAIN');
}
$cpanelPhp = \config_value('cpanel.php_path', '/usr/local/bin/ea-php81');
?>
<?= e($cpanelPhp) ?> <?= e($cpanelAppRoot) ?>/cron/run.php birthday

# Contract Renewal (Daily 09:00)
<?= e($cpanelPhp) ?> <?= e($cpanelAppRoot) ?>/cron/run.php contract_renewal

# Due Date Reminders (Daily 10:00)
<?= e($cpanelPhp) ?> <?= e($cpanelAppRoot) ?>/cron/run.php due_date_reminders

# Retry Failed SMS (Every 15 min)
<?= e($cpanelPhp) ?> <?= e($cpanelAppRoot) ?>/cron/run.php sms_retry</code></pre>
            <p class="small mb-0">در cPanel → Cron Jobs این دستورات را اضافه کنید.</p>
            </details>
        </div>
    </div>
    <?php endif; ?>
</div>
