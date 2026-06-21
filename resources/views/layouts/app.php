<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

$mainNavItems = [
    ['/dashboard', 'داشبورد', 'bi-speedometer2', null],
    ['/customers', 'مشتریان', 'bi-people', null],
    ['/customers/create', 'افزودن مشتری', 'bi-person-plus', null],
    ['/purchases/create', 'ثبت خرید', 'bi-receipt', 'purchase'],
    ['/services', 'سرویس‌ها', 'bi-tools', null],
    ['/services/create', 'ثبت سرویس', 'bi-wrench', null],
    ['/reports', 'گزارش‌ها', 'bi-bar-chart-line', null],
    ['/sms/logs', 'لاگ پیامک', 'bi-chat-dots', null],
];
$adminNavItems = [];
if (Auth::isAdmin()) {
    $adminNavItems = [
        ['/admin/users', 'اپراتورها', 'bi-person-gear', 'manage_users'],
        ['/admin/activity-logs', 'فعالیت‌ها', 'bi-activity', null],
        ['/admin/system-status', 'وضعیت سیستم', 'bi-heart-pulse', null],
        ['/admin/cashback-settings', 'تنظیمات کش‌بک', 'bi-percent', 'manage_settings'],
        ['/admin/sms-settings', 'تنظیمات پیامک', 'bi-sliders', 'manage_settings'],
        ['/admin/loyalty', 'سطوح و پروموشن', 'bi-trophy', 'manage_loyalty'],
        ['/admin/api-keys', 'کلید API', 'bi-key', 'manage_api'],
        ['/admin/customers/import', 'ورود فایل', 'bi-upload', 'import_customers'],
        ['/admin/app-update', 'به‌روزرسانی', 'bi-cloud-download', 'manage_settings'],
    ];
}
$canSeeNavItem = static fn (array $item): bool => $item[3] === null || Auth::can($item[3]);
$mainNavItems = array_values(array_filter($mainNavItems, $canSeeNavItem));
$adminNavItems = array_values(array_filter($adminNavItems, $canSeeNavItem));
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config_value('app.name')) ?></title>
    <link href="<?= e(asset_url('vendor/bootstrap/bootstrap.rtl.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset_url('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>?v=<?= e(app_version()) ?>">
</head>
<body>
<?php if (Auth::check()): ?>
<button class="sidebar-toggle btn btn-light d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="باز کردن منو">
    <i class="bi bi-list"></i>
</button>
<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$renderSidebar = function (string $extraClass = '') use ($mainNavItems, $adminNavItems, $currentPath): void {
$isActive = static fn (string $path): bool => $currentPath === $path || ($path !== '/dashboard' && str_starts_with($currentPath, $path));
$adminActive = false;
foreach ($adminNavItems as [$path]) {
    if ($isActive($path)) {
        $adminActive = true;
        break;
    }
}
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
        <div class="mt-2 small text-muted ltr">v<?= e(app_version()) ?></div>
    </div>
    <div class="sidebar-section">منوی اصلی</div>
    <nav class="sidebar-nav">
        <?php foreach ($mainNavItems as [$path, $label, $icon]): ?>
            <?php $active = $isActive($path); ?>
            <a class="sidebar-link <?= $active ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
                <i class="bi <?= e($icon) ?>"></i>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php if ($adminNavItems): ?>
        <details class="sidebar-group" <?= $adminActive ? 'open' : '' ?>>
            <summary class="sidebar-group-toggle <?= $adminActive ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i>
                <span>مدیریت سیستم</span>
                <i class="bi bi-chevron-down group-chevron"></i>
            </summary>
            <nav class="sidebar-subnav">
                <?php foreach ($adminNavItems as [$path, $label, $icon]): ?>
                    <?php $active = $isActive($path); ?>
                    <a class="sidebar-link sidebar-link-sub <?= $active ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
                        <i class="bi <?= e($icon) ?>"></i>
                        <span><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </details>
    <?php endif; ?>
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
                <div class="text-muted small ltr">Version <?= e(app_version()) ?></div>
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
<script src="<?= e(asset_url('js/app.js')) ?>?v=<?= e(app_version()) ?>"></script>
</body>
</html>
