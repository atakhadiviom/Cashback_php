<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

$navItems = [
    ['/dashboard', 'داشبورد', 'bi-speedometer2', null],
    ['/customers', 'مشتریان', 'bi-people', null],
    ['/customers/create', 'افزودن مشتری', 'bi-person-plus', null],
    ['/purchases/create', 'ثبت خرید', 'bi-receipt', 'purchase'],
    ['/reports', 'گزارش‌ها', 'bi-bar-chart-line', null],
    ['/sms/logs', 'لاگ پیامک', 'bi-chat-dots', null],
];
if (Auth::isAdmin()) {
    $navItems[] = ['/admin/users', 'اپراتورها', 'bi-person-gear', 'manage_users'];
    $navItems[] = ['/admin/activity-logs', 'فعالیت‌ها', 'bi-activity', null];
    $navItems[] = ['/admin/cashback-settings', 'تنظیمات کش‌بک', 'bi-percent', 'manage_settings'];
    $navItems[] = ['/admin/sms-settings', 'تنظیمات پیامک', 'bi-sliders', 'manage_settings'];
    $navItems[] = ['/admin/loyalty', 'سطوح و پروموشن', 'bi-trophy', 'manage_loyalty'];
    $navItems[] = ['/admin/api-keys', 'کلید API', 'bi-key', 'manage_api'];
    $navItems[] = ['/admin/customers/import', 'ورود CSV', 'bi-upload', 'import_customers'];
}
$navItems = array_values(array_filter($navItems, static fn (array $item): bool => $item[3] === null || Auth::can($item[3])));
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config_value('app.name')) ?></title>
    <link href="<?= e(asset_url('vendor/bootstrap/bootstrap.rtl.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
</head>
<body>
<?php if (Auth::check()): ?>
<button class="sidebar-toggle btn btn-light d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="باز کردن منو">
    <i class="bi bi-list"></i>
</button>
<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$renderSidebar = function (string $extraClass = '') use ($navItems, $currentPath): void {
?>
<aside class="app-sidebar <?= e($extraClass) ?>">
    <div class="brand-block">
        <a class="brand-mark" href="<?= e(url('/dashboard')) ?>">
            <span class="brand-icon"><i class="bi bi-wallet2"></i></span>
            <span>
                <strong>کش‌بک</strong>
                <small><?= e(config_value('app.company_name', '')) ?></small>
            </span>
        </a>
    </div>
    <div class="sidebar-section">منوی اصلی</div>
    <nav class="sidebar-nav">
        <?php foreach ($navItems as [$path, $label, $icon]): ?>
            <?php $active = $currentPath === $path || ($path !== '/dashboard' && str_starts_with($currentPath, $path)); ?>
            <a class="sidebar-link <?= $active ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
                <i class="bi <?= e($icon) ?>"></i>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-3"><a class="btn btn-outline-secondary btn-sm w-100" href="<?= e(url('/portal')) ?>" target="_blank">پرتال مشتری</a></div>
</aside>
<?php
};
$renderSidebar('d-none d-xl-flex');
?>
<div class="offcanvas offcanvas-start d-xl-none" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-body p-0"><?php $renderSidebar('mobile'); ?></div>
</div>
<?php endif; ?>
<div class="<?= Auth::check() ? 'app-main' : 'guest-main' ?>">
    <?php if (Auth::check()): ?>
        <header class="app-topbar">
            <div>
                <div class="topbar-kicker">پنل مدیریت</div>
                <h1><?= e(config_value('app.name')) ?></h1>
            </div>
            <div class="topbar-actions">
                <div class="user-chip">
                    <span class="user-avatar"><?= e(mb_substr(Auth::user()['name'] ?? 'ک', 0, 1)) ?></span>
                    <span>
                        <strong><?= e(Auth::user()['name'] ?? '') ?></strong>
                        <small><?= e(Auth::role() === 'admin' ? 'مدیر' : 'اپراتور') ?></small>
                    </span>
                </div>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-left"></i> خروج</button>
                </form>
            </div>
        </header>
    <?php endif; ?>
    <main class="page-shell">
        <?php foreach (Flash::all() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
</div>
<script src="<?= e(asset_url('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset_url('js/app.js')) ?>"></script>
</body>
</html>
