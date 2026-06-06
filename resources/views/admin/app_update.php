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
                <div class="text-muted small">وضعیت</div>
                <div><?= $status['enabled'] ? 'فعال' : 'غیرفعال در تنظیمات' ?></div>
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

<div class="alert alert-warning">
    قبل از اجرا، برنامه یک فایل پشتیبان در <span class="ltr"><?= e($status['backup_dir']) ?></span> می‌سازد و فایل‌های محلی مثل تنظیمات و پوشه storage را دست نمی‌زند.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="post" action="<?= e(url('/admin/app-update')) ?>" onsubmit="return confirm('به‌روزرسانی از شاخه main اجرا شود؟');">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="run_migrations" id="run_migrations" checked>
                <label class="form-check-label" for="run_migrations">بعد از کپی فایل‌ها، مایگریشن‌های دیتابیس اجرا شود</label>
            </div>
            <button class="btn btn-primary" <?= (!$status['enabled'] || !$status['zip_available'] || !$status['curl_available']) ? 'disabled' : '' ?>>
                <i class="bi bi-cloud-download"></i> دریافت و نصب آخرین نسخه main
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
