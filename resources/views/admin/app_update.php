<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">به‌روزرسانی برنامه</h1>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">مخزن GitHub</div>
                <div class="ltr"><?= e(($status['owner'] ?: '-') . '/' . ($status['repo'] ?: '-')) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">شاخه</div>
                <div class="ltr"><?= e($status['branch']) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">نسخه نصب‌شده</div>
                <div class="ltr">v<?= e($status['version']) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">آخرین نسخه GitHub</div>
                <div class="ltr">
                    <?php if (!empty($status['remote_version'])): ?>
                        v<?= e($status['remote_version']) ?>
                    <?php elseif (!empty($status['remote_error'])): ?>
                        <span class="text-danger">خطا در بررسی</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">وضعیت</div>
                <div>
                    <?php if (!empty($status['update_available'])): ?>
                        <span class="text-success">نسخه جدید آماده نصب است</span>
                    <?php elseif (!empty($status['remote_version'])): ?>
                        <span>برنامه به‌روز است</span>
                    <?php else: ?>
                        <span><?= $status['enabled'] ? 'فعال' : 'غیرفعال در تنظیمات' ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">ZipArchive</div>
                <div><?= $status['zip_available'] ? 'آماده' : 'غیرفعال روی هاست' ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">cURL</div>
                <div><?= $status['curl_available'] ? 'آماده' : 'غیرفعال روی هاست' ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($status['remote_error'])): ?>
    <div class="alert alert-danger">
        بررسی نسخه GitHub ناموفق بود: <span class="ltr"><?= e($status['remote_error']) ?></span>
    </div>
<?php elseif (!empty($status['update_available'])): ?>
    <div class="alert alert-success">
        نسخه جدید v<?= e($status['remote_version']) ?> روی شاخه <span class="ltr"><?= e($status['branch']) ?></span> آماده نصب است.
    </div>
<?php endif; ?>

<?php if (!$status['enabled']): ?>
    <div class="alert alert-danger">
        به‌روزرسانی در تنظیمات غیرفعال است. برای نصب نسخه جدید، مقدار <span class="ltr">updater.enabled</span> را در فایل تنظیمات فعال کنید.
    </div>
<?php endif; ?>

<div class="alert alert-warning">
    قبل از اجرا، برنامه یک فایل پشتیبان در <span class="ltr"><?= e($status['backup_dir']) ?></span> می‌سازد و فایل‌های محلی مثل تنظیمات و پوشه storage را دست نمی‌زند.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="post" action="<?= e(url('/admin/app-update')) ?>" onsubmit="return confirm('به‌روزرسانی از شاخه <?= e($status['branch']) ?> اجرا شود؟');">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="run_migrations" id="run_migrations" value="1" checked>
                <label class="form-check-label" for="run_migrations">بعد از کپی فایل‌ها، مایگریشن‌های دیتابیس اجرا شود</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="setup_cpanel_cron" id="setup_cpanel_cron" value="1" checked>
                <label class="form-check-label" for="setup_cpanel_cron">کرون‌جاب‌های cPanel (پیامک، یادآوری سررسید، تلاش مجدد) خودکار ثبت شوند</label>
                <div class="form-text">نیاز به فعال بودن <span class="ltr">cpanel.enabled</span> و توکن API در تنظیمات دارد.</div>
            </div>
            <button class="btn btn-primary" <?= (!$status['enabled'] || !$status['zip_available'] || !$status['curl_available']) ? 'disabled' : '' ?>>
                <i class="bi bi-cloud-download"></i> دریافت و نصب آخرین نسخه <?= e($status['branch']) ?>
            </button>
        </form>
    </div>
</div>

<?php if (is_array($result)): ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5 mb-3">نتیجه به‌روزرسانی</h2>
            <?php if (!empty($result['backup'])): ?>
                <div class="alert alert-info">پشتیبان: <span class="ltr"><?= e($result['backup']) ?></span></div>
            <?php endif; ?>
            <ol class="mb-0">
                <?php foreach ($result['messages'] ?? [] as $message): ?>
                    <li class="ltr text-start"><?= e($message) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
<?php endif; ?>
