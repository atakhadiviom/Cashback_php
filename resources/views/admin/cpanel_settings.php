<?php use App\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="text-muted small mb-1">اتصال API برای ثبت خودکار کرون‌جاب‌ها</div>
        <h1 class="h3 fw-bold mb-0">تنظیمات cPanel</h1>
    </div>
    <?php
    $configured = !empty($connection['configured']);
    $listOk = !empty($connection['list']['ok']);
    if ($configured && $listOk) {
        $badgeClass = 'success';
        $badgeLabel = 'متصل';
    } elseif ($configured) {
        $badgeClass = 'warning text-dark';
        $badgeLabel = 'پیکربندی شده — خطای اتصال';
    } else {
        $badgeClass = 'secondary';
        $badgeLabel = 'پیکربندی نشده';
    }
    ?>
    <span class="badge bg-<?= e($badgeClass) ?> align-self-center"><?= e($badgeLabel) ?></span>
</div>

<div class="alert alert-info mb-4">
    <div class="fw-bold mb-2">چطور توکن API بگیرم؟</div>
    <ol class="mb-2 pe-3">
        <li>وارد پنل <strong>cPanel</strong> هاست خود شوید.</li>
        <li>از بخش <strong>Security</strong> گزینه <strong>Manage API Tokens</strong> را باز کنید.</li>
        <li>روی <strong>Create</strong> بزنید، یک نام ساده بگذارید (مثلاً cashback) و توکن را بسازید.</li>
        <li>توکن نمایش‌داده‌شده را <strong>کپی</strong> کنید (فقط یک‌بار نشان داده می‌شود).</li>
        <li>همان را در فیلد «توکن API» همین صفحه بچسبانید و ذخیره کنید.</li>
    </ol>
    <div class="small mb-0">اگر Manage API Tokens را ندیدید، از پشتیبانی هاست بخواهید این قابلیت را فعال کند. فیلد رمز عبور فقط برای هاست‌های قدیمی‌تر لازم است و معمولاً خالی می‌ماند.</div>
</div>

<?php if ($configured && !$listOk): ?>
<div class="alert alert-warning mb-4">
    اتصال پیکربندی شده است، اما خواندن لیست کرون‌جاب‌ها ناموفق بود:
    <span class="ltr d-inline-block"><?= e((string) ($connection['list']['message'] ?? '')) ?></span>
</div>
<?php elseif ($configured && $listOk): ?>
<div class="alert alert-success mb-4">
    اتصال برقرار است.
    <?php
    $cronCount = is_array($connection['list']['crons'] ?? null) ? count($connection['list']['crons']) : 0;
    ?>
    تعداد کرون‌جاب‌های فعلی در cPanel: <strong class="ltr"><?= (int) $cronCount ?></strong>
</div>
<?php endif; ?>

<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/admin/cpanel-settings')) ?>" id="cpanel-settings-form">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <div class="row g-3">
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="enabled" name="enabled" <?= !empty($settings['enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="enabled">فعال‌سازی اتصال API سی‌پنل</label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="host">میزبان (host)</label>
            <input class="form-control ltr" id="host" name="host" value="<?= e($settings['host'] ?? '') ?>" placeholder="example.com:2083" autocomplete="off">
            <div class="form-text">معمولاً دامنه یا IP همراه با پورت ۲۰۸۳</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="username">نام کاربری cPanel</label>
            <input class="form-control ltr" id="username" name="username" value="<?= e($settings['username'] ?? '') ?>" autocomplete="off">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="api_token">توکن API</label>
            <input class="form-control ltr" id="api_token" name="api_token" value="" placeholder="<?= e($tokenHint) ?>" autocomplete="off">
            <div class="form-text"><?= !empty($hasApiToken) ? 'توکن قبلاً ذخیره شده است؛ خالی بگذارید تا همان بماند.' : 'هنوز توکنی ذخیره نشده است.' ?></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="password">رمز عبور (اختیاری)</label>
            <input class="form-control ltr" id="password" name="password" type="password" value="" placeholder="<?= e($passwordHint) ?>" autocomplete="new-password">
            <div class="form-text"><?= !empty($hasPassword) ? 'رمز قبلاً ذخیره شده است؛ خالی بگذارید تا همان بماند.' : 'فقط در صورت نیاز به API2 قدیمی.' ?></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="domain">دامنه / پوشه اپ</label>
            <input class="form-control ltr" id="domain" name="domain" value="<?= e($settings['domain'] ?? '') ?>" placeholder="cashback.example.com" autocomplete="off">
            <div class="form-text">نام پوشه زیر home، مثلاً دامنه سایت</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="php_path">مسیر PHP</label>
            <input class="form-control ltr" id="php_path" name="php_path" value="<?= e($settings['php_path'] ?? '/usr/local/bin/ea-php81') ?>" placeholder="/usr/local/bin/ea-php81" autocomplete="off">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="app_root">مسیر کامل اپ (app_root)</label>
            <input class="form-control ltr" id="app_root" name="app_root" value="<?= e($settings['app_root'] ?? '') ?>" placeholder="/home/USER/cashback.example.com" autocomplete="off">
            <div class="form-text">اگر پر باشد، اولویت با این مسیر است</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="home_dir">پوشه home (اختیاری)</label>
            <input class="form-control ltr" id="home_dir" name="home_dir" value="<?= e($settings['home_dir'] ?? '') ?>" placeholder="/home3/USER" autocomplete="off">
            <div class="form-text">برای هاست‌هایی مثل /home3/ به‌جای /home/</div>
        </div>
    </div>
    <div class="mt-4 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">ذخیره</button>
        <button type="submit" class="btn btn-outline-primary" formaction="<?= e(url('/admin/cpanel-settings/setup-cron')) ?>">ذخیره و ثبت کرون‌جاب‌ها</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/system-status')) ?>">وضعیت سیستم</a>
    </div>
</form>
</div></div>
