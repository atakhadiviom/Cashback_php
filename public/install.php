<?php

declare(strict_types=1);

session_name('cashback_installer');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$root = dirname(__DIR__);
$rootConfigFile = $root . '/cashback_config.php';
$externalConfigFile = dirname($root) . '/cashback_config.php';
$internalConfigFile = $root . '/config/config.php';
$externalLockFile = dirname($root) . '/cashback_installed.lock';
$rootLockFile = $root . '/cashback_installed.lock';
$internalLockFile = $root . '/storage/installed.lock';
$schemaFile = $root . '/database/schema.sql';
$errors = [];
$success = false;

$canWriteRootConfig = is_writable(dirname($rootConfigFile)) && (!file_exists($rootConfigFile) || is_writable($rootConfigFile));
$canWriteExternalConfig = is_writable(dirname($externalConfigFile)) && (!file_exists($externalConfigFile) || is_writable($externalConfigFile));
$canWriteRootLock = is_writable(dirname($rootLockFile)) && (!file_exists($rootLockFile) || is_writable($rootLockFile));
$canWriteExternalLock = is_writable(dirname($externalLockFile)) && (!file_exists($externalLockFile) || is_writable($externalLockFile));

// Prefer WordPress-style config/lock in project root (public_html),
// then the legacy parent-folder paths, then fall back to in-project paths.
$configFile = $canWriteRootConfig ? $rootConfigFile : ($canWriteExternalConfig ? $externalConfigFile : $internalConfigFile);
$lockFile = $canWriteRootLock ? $rootLockFile : ($canWriteExternalLock ? $externalLockFile : $internalLockFile);

function installer_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function installer_token(): string
{
    if (empty($_SESSION['_installer_csrf'])) {
        $_SESSION['_installer_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_installer_csrf'];
}

function installer_dsn(string $host, ?string $database = null): string
{
    $host = trim($host);
    $port = null;

    if (str_contains($host, ';')) {
        $dsn = 'mysql:' . $host;
    } elseif (preg_match('/^(.+):(\d+)$/', $host, $matches)) {
        $dsn = 'mysql:host=' . $matches[1] . ';port=' . $matches[2];
    } else {
        $dsn = 'mysql:host=' . $host;
    }

    if ($database !== null && $database !== '') {
        $dsn .= ';dbname=' . $database;
    }

    return $dsn . ';charset=utf8mb4';
}

function installer_config(array $data): string
{
    $appName = 'سیستم مدیریت کش بک ' . $data['company_name'];

    return "<?php\n\nreturn [\n" .
        "    'app' => [\n" .
        "        'name' => " . var_export($appName, true) . ",\n" .
        "        'base_url' => " . var_export($data['base_url'], true) . ",\n" .
        "        'timezone' => " . var_export($data['timezone'], true) . ",\n" .
        "        'debug' => false,\n" .
        "        'birthday_required' => " . ($data['birthday_required'] ? 'true' : 'false') . ",\n" .
        "        'company_name' => " . var_export($data['company_name'], true) . ",\n" .
        "    ],\n" .
        "    'database' => [\n" .
        "        'host' => " . var_export($data['db_host'], true) . ",\n" .
        "        'name' => " . var_export($data['db_name'], true) . ",\n" .
        "        'user' => " . var_export($data['db_user'], true) . ",\n" .
        "        'password' => " . var_export($data['db_password'], true) . ",\n" .
        "        'charset' => 'utf8mb4',\n" .
        "    ],\n" .
        "    'security' => [\n" .
        "        'session_name' => 'cashback_session',\n" .
        "    ],\n" .
        "    'updater' => [\n" .
        "        'enabled' => false,\n" .
        "        'github_owner' => 'atakhadiviom',\n" .
        "        'github_repo' => 'Cashback_php',\n" .
        "        'branch' => 'main',\n" .
        "        'github_token' => '',\n" .
        "    ],\n" .
        "];\n";
}

$requirements = [
    [
        'label' => 'نسخه PHP',
        'ok' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'detail' => 'نسخه فعلی: ' . PHP_VERSION . '، حداقل مورد نیاز: 8.1',
    ],
    [
        'label' => 'افزونه PDO',
        'ok' => extension_loaded('pdo'),
        'detail' => extension_loaded('pdo') ? 'فعال است.' : 'افزونه pdo در PHP فعال نیست.',
    ],
    [
        'label' => 'افزونه PDO MySQL',
        'ok' => extension_loaded('pdo_mysql'),
        'detail' => extension_loaded('pdo_mysql') ? 'فعال است.' : 'افزونه pdo_mysql برای اتصال به MySQL فعال نیست.',
    ],
    [
        'label' => 'افزونه cURL',
        'ok' => extension_loaded('curl'),
        'detail' => extension_loaded('curl') ? 'فعال است.' : 'افزونه curl برای ارسال پیامک ippanel فعال نیست.',
    ],
    [
        'label' => 'افزونه mbstring',
        'ok' => extension_loaded('mbstring'),
        'detail' => extension_loaded('mbstring') ? 'فعال است.' : 'افزونه mbstring برای پردازش متن فارسی فعال نیست.',
    ],
    [
        'label' => 'Session',
        'ok' => function_exists('session_start'),
        'detail' => function_exists('session_start') ? 'قابل استفاده است.' : 'session_start در PHP در دسترس نیست.',
    ],
    [
        'label' => 'نوشتن تنظیمات پایدار بیرون از پوشه برنامه',
        'ok' => is_writable(dirname($configFile)) && (!file_exists($configFile) || is_writable($configFile)),
        'detail' => 'مسیر هدف: ' . $configFile . ' | این فایل عمداً داخل پوشه برنامه ساخته نمی‌شود تا با آپدیت ZIP حذف نشود.',
    ],
    [
        'label' => 'نوشتن قفل نصب پایدار بیرون از پوشه برنامه',
        'ok' => is_writable(dirname($lockFile)) && (!file_exists($lockFile) || is_writable($lockFile)),
        'detail' => 'مسیر هدف: ' . $lockFile . ' | این فایل عمداً داخل پوشه برنامه ساخته نمی‌شود تا با آپدیت ZIP حذف نشود.',
    ],
    [
        'label' => 'خواندن schema.sql',
        'ok' => is_readable($schemaFile),
        'detail' => 'مسیر: ' . $schemaFile,
    ],
];
$requirementsPassed = count(array_filter($requirements, fn (array $item): bool => $item['ok'])) === count($requirements);
$failedRequirements = array_values(array_filter($requirements, fn (array $item): bool => !$item['ok']));
$phpDiagnostics = [
    'PHP_VERSION' => PHP_VERSION,
    'PHP_SAPI' => PHP_SAPI,
    'PHP_BINARY' => PHP_BINARY,
    'php.ini' => php_ini_loaded_file() ?: 'php.ini پیدا نشد یا بارگذاری نشده است',
    'Server software' => $_SERVER['SERVER_SOFTWARE'] ?? 'نامشخص',
    'Document root' => $_SERVER['DOCUMENT_ROOT'] ?? 'نامشخص',
    'Project root' => $root,
    'Config file target' => $configFile,
    'External config target' => $externalConfigFile,
    'Old internal config path, read-only migration fallback' => $internalConfigFile,
    'Install lock target' => $lockFile,
    'External lock target' => $externalLockFile,
    'Old internal lock path, read-only migration fallback' => $internalLockFile,
    'Storage path' => $root . '/storage',
    'Schema path' => $schemaFile,
    'PDO loaded' => extension_loaded('pdo') ? 'YES' : 'NO',
    'pdo_mysql loaded' => extension_loaded('pdo_mysql') ? 'YES' : 'NO',
    'curl loaded' => extension_loaded('curl') ? 'YES' : 'NO',
    'mbstring loaded' => extension_loaded('mbstring') ? 'YES' : 'NO',
    'parent directory writable for persistent config' => is_writable(dirname($configFile)) ? 'YES' : 'NO',
    'config file writable/existing' => (!file_exists($configFile) || is_writable($configFile)) ? 'YES' : 'NO',
    'parent directory writable for install lock' => is_writable(dirname($lockFile)) ? 'YES' : 'NO',
    'schema readable' => is_readable($schemaFile) ? 'YES' : 'NO',
];

$values = [
    'db_host' => $_POST['db_host'] ?? 'localhost',
    'db_name' => $_POST['db_name'] ?? 'cashback_php',
    'db_user' => $_POST['db_user'] ?? '',
    'db_password' => $_POST['db_password'] ?? '',
    'base_url' => $_POST['base_url'] ?? '',
    'timezone' => $_POST['timezone'] ?? 'Asia/Tehran',
    'company_name' => $_POST['company_name'] ?? '',
    'admin_name' => $_POST['admin_name'] ?? 'مدیر سیستم',
    'admin_username' => $_POST['admin_username'] ?? 'admin',
    'birthday_required' => isset($_POST['birthday_required']),
    'create_database' => !isset($_POST['submitted']) || isset($_POST['create_database']),
];

$recommendedBaseUrl = '';
try {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $recommendedBaseUrl = ($requestPath === '/public/install.php' || str_starts_with($requestPath, '/public/')) ? '/public' : '';
} catch (Throwable) {
    $recommendedBaseUrl = '';
}

if (file_exists($externalLockFile) || file_exists($rootLockFile) || file_exists($internalLockFile)) {
    http_response_code(404);
    $locked = true;
} else {
    $locked = false;
}

if (!$locked && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $values['db_host'] = trim((string) $values['db_host']);
    $values['db_name'] = trim((string) $values['db_name']);
    $values['db_user'] = trim((string) $values['db_user']);
    $values['base_url'] = rtrim(trim((string) $values['base_url']), '/');
    $values['timezone'] = trim((string) $values['timezone']);
    $values['company_name'] = trim((string) $values['company_name']);
    $values['admin_name'] = trim((string) $values['admin_name']);
    $values['admin_username'] = trim((string) $values['admin_username']);
    $adminPassword = (string) ($_POST['admin_password'] ?? '');
    $adminPasswordConfirm = (string) ($_POST['admin_password_confirm'] ?? '');

    if (!hash_equals($_SESSION['_installer_csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        $errors[] = 'درخواست نامعتبر است. صفحه را تازه‌سازی کنید و دوباره تلاش کنید.';
    }
    foreach (['db_host' => 'هاست دیتابیس', 'db_name' => 'نام دیتابیس', 'db_user' => 'نام کاربری دیتابیس', 'company_name' => 'نام شرکت', 'admin_name' => 'نام مدیر', 'admin_username' => 'نام کاربری مدیر'] as $field => $label) {
        if ($values[$field] === '') {
            $errors[] = $label . ' الزامی است.';
        }
    }
    if (strlen($adminPassword) < 8) {
        $errors[] = 'رمز عبور مدیر باید حداقل ۸ کاراکتر باشد.';
    }
    if ($adminPassword !== $adminPasswordConfirm) {
        $errors[] = 'تکرار رمز عبور مدیر با رمز عبور یکسان نیست.';
    }
    foreach ($requirements as $requirement) {
        if (!$requirement['ok']) {
            $errors[] = 'نیازمندی برقرار نیست: ' . $requirement['label'] . ' - ' . $requirement['detail'];
        }
    }

    if (!$errors) {
        try {
            if ($values['create_database']) {
                $serverPdo = new PDO(installer_dsn($values['db_host']), (string) $values['db_user'], (string) $values['db_password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $serverPdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $values['db_name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }

            $pdo = new PDO(installer_dsn($values['db_host'], $values['db_name']), (string) $values['db_user'], (string) $values['db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec((string) file_get_contents($schemaFile));

            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role, is_active, created_at, updated_at) VALUES (:name, :username, :password_hash, "admin", 1, :created_at, :updated_at)');
            $stmt->execute([
                'name' => $values['admin_name'],
                'username' => $values['admin_username'],
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (file_put_contents($configFile, installer_config($values)) === false) {
                throw new RuntimeException('نوشتن فایل تنظیمات انجام نشد: ' . $configFile);
            }

            if (file_put_contents($lockFile, 'Installed at ' . $now . PHP_EOL) === false) {
                throw new RuntimeException('ایجاد فایل قفل نصب انجام نشد: ' . $lockFile);
            }

            $success = true;
        } catch (Throwable $exception) {
            $errors[] = 'خطای نصب: ' . $exception->getMessage();

            $message = $exception->getMessage();
            if (str_contains($message, 'SQLSTATE[HY000] [2002]')) {
                $errors[] = 'راهنما: خطای 2002 یعنی PHP نمی‌تواند به MySQL وصل شود. در هاست‌های اشتراکی معمولاً با یکی از موارد زیر حل می‌شود:';
                $errors[] = '۱) به جای localhost، مقدار هاست دیتابیس را روی 127.0.0.1 بگذارید (در بسیاری از سرورها localhost به سوکت اشاره می‌کند).';
                $errors[] = '۲) اگر پورت خاص دارید، هاست را به صورت host:port وارد کنید (مثلاً 127.0.0.1:3306).';
                $errors[] = '۳) اگر هاست شما سوکت مشخص می‌دهد، می‌توانید مقدار هاست را به شکل DSN کامل وارد کنید، مثل: host=localhost;unix_socket=/path/to/mysql.sock';
                $errors[] = '۴) مطمئن شوید مشخصات دیتابیس/یوزر و سطح دسترسی درست است و دیتابیس روی همین سرور فعال است.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نصب سیستم مدیریت کش بک</title>
    <link href="bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f5f7fb; color: #1f2937; }
        .shell { max-width: 980px; margin: 0 auto; padding: 32px 16px; }
        .card { border-radius: 8px; border-color: #e5e7eb; }
        .ltr { direction: ltr; unicode-bidi: embed; }
        .requirements-list { display: block; }
        .requirement-row { display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #eef0f4; }
        .requirement-row:last-child { border-bottom: 0; }
        .requirement-main { flex: 1 1 auto; min-width: 0; }
        .requirement-title { font-weight: 700; color: #111827; }
        .requirement-detail { margin-top: 6px; color: #4b5563; overflow-wrap: anywhere; font-size: .9rem; }
        .status-badge { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; min-width: 92px; padding: 7px 10px; border-radius: 999px; color: #fff; font-weight: 800; font-size: .82rem; }
        .status-ok { background: #198754; }
        .status-fail { background: #dc3545; }
        .plain-diagnostic { background: #111827; color: #f9fafb; border-radius: 8px; padding: 14px; font-family: Menlo, Consolas, monospace; direction: ltr; text-align: left; overflow: auto; }
        .plain-diagnostic-row { display: grid; grid-template-columns: 230px minmax(0, 1fr); gap: 12px; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,.12); }
        .plain-diagnostic-row:last-child { border-bottom: 0; }
        .plain-diagnostic-key { color: #93c5fd; font-weight: 700; }
        .plain-diagnostic-value { color: #f9fafb; overflow-wrap: anywhere; }
        @media (max-width: 720px) {
            .requirement-row { display: block; }
            .status-badge { margin-top: 10px; }
            .plain-diagnostic-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main class="shell">
    <div class="mb-4">
        <h1 class="h3">نصب سیستم مدیریت کش بک</h1>
        <p class="text-muted mb-0">اطلاعات دیتابیس و مدیر اولیه را وارد کنید. پس از نصب، این صفحه قفل می‌شود.</p>
    </div>

    <?php if ($locked): ?>
        <div class="alert alert-warning">نصب‌کننده غیرفعال است.</div>
        <a class="btn btn-primary" href="login">ورود به برنامه</a>
    <?php elseif ($success): ?>
        <div class="alert alert-success">نصب با موفقیت انجام شد. فایل تنظیمات نوشته شد و نصب‌کننده قفل شد.</div>
        <a class="btn btn-primary" href="login">ورود به برنامه</a>
    <?php else: ?>
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= installer_e($error) ?></div><?php endforeach; ?>

        <div style="background:#fee2e2;border:1px solid #fca5a5;color:#7f1d1d;border-radius:8px;padding:16px;margin-bottom:16px;">
            <div style="font-weight:800;font-size:18px;margin-bottom:10px;">موارد ناموفق که باید اصلاح شوند:</div>
            <?php if ($failedRequirements): ?>
                <?php foreach ($failedRequirements as $requirement): ?>
                    <div style="background:#fff;border:1px solid #fecaca;border-radius:6px;padding:10px 12px;margin-top:8px;">
                        <div style="font-weight:800;"><?= installer_e($requirement['label'] ?? 'نامشخص') ?></div>
                        <div style="direction:ltr;text-align:left;overflow-wrap:anywhere;margin-top:4px;color:#991b1b;"><?= installer_e($requirement['detail'] ?? 'جزئیات موجود نیست') ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="background:#fff;border:1px solid #bbf7d0;color:#14532d;border-radius:6px;padding:10px 12px;">هیچ مورد ناموفقی وجود ندارد. همه نیازمندی‌ها آماده هستند.</div>
            <?php endif; ?>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">جزئیات کامل PHP و مسیرها</div>
            <div class="card-body">
                <div class="plain-diagnostic">
                    <?php foreach ($phpDiagnostics as $key => $value): ?>
                        <div class="plain-diagnostic-row">
                            <div class="plain-diagnostic-key"><?= installer_e($key) ?></div>
                            <div class="plain-diagnostic-value"><?= installer_e($value) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>بررسی نیازمندی‌ها</span>
                <span class="badge <?= $requirementsPassed ? 'bg-success' : 'bg-danger' ?>">
                    <?= $requirementsPassed ? 'همه موارد موفق است' : 'برخی موارد ناموفق است' ?>
                </span>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($requirements as $requirement): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="ms-auto">
                            <div class="fw-bold"><?= installer_e($requirement['label']) ?></div>
                            <div class="small text-muted ltr"><?= installer_e($requirement['detail']) ?></div>
                        </div>
                        <span class="badge <?= $requirement['ok'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $requirement['ok'] ? 'موفق' : 'ناموفق' ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <a class="btn btn-outline-primary" href="#install-form">ادامه به فرم نصب</a>
            <?php if (!$requirementsPassed): ?>
                <span class="text-danger small">اگر موردی ناموفق است، ابتدا تنظیمات هاست یا دسترسی فایل‌ها را اصلاح کنید.</span>
            <?php endif; ?>
        </div>

        <div class="alert alert-info">
            <div style="font-weight:800;margin-bottom:6px;">راهنمای تنظیم Base URL</div>
            <?php if ($recommendedBaseUrl !== ''): ?>
                <div>با توجه به آدرس فعلی نصب، پیشنهاد می‌شود Base URL را روی مقدار زیر قرار دهید:</div>
                <div class="ltr" style="font-weight:800; margin-top:6px;"><?= installer_e($recommendedBaseUrl) ?></div>
            <?php else: ?>
                <div>اگر برنامه مستقیماً از ریشه دامنه اجرا می‌شود، Base URL را خالی بگذارید.</div>
            <?php endif; ?>
            <div class="small text-muted ltr" style="margin-top:8px;">Current request path: <?= installer_e(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/') ?></div>
        </div>

        <form method="post" class="card" id="install-form">
            <div class="card-body">
                <input type="hidden" name="submitted" value="1">
                <input type="hidden" name="_csrf" value="<?= installer_e(installer_token()) ?>">

                <h2 class="h5 mb-3">دیتابیس</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label">هاست دیتابیس</label><input class="form-control ltr" name="db_host" value="<?= installer_e($values['db_host']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">نام دیتابیس</label><input class="form-control ltr" name="db_name" value="<?= installer_e($values['db_name']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">نام کاربری دیتابیس</label><input class="form-control ltr" name="db_user" value="<?= installer_e($values['db_user']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">رمز دیتابیس</label><input class="form-control ltr" type="password" name="db_password" value="<?= installer_e($values['db_password']) ?>"></div>
                    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="create_database" id="create_database" <?= $values['create_database'] ? 'checked' : '' ?>><label class="form-check-label" for="create_database">اگر دیتابیس وجود ندارد، ایجاد شود</label></div></div>
                </div>

                <h2 class="h5 mb-3">تنظیمات برنامه</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Base URL</label>
                        <input class="form-control ltr" name="base_url" placeholder="مثلاً /cashback یا خالی" value="<?= installer_e($values['base_url'] !== '' ? $values['base_url'] : $recommendedBaseUrl) ?>">
                        <div class="form-text">اگر URLهای شما شامل <span class="ltr">/public</span> است، Base URL معمولاً باید <span class="ltr">/public</span> باشد.</div>
                    </div>
                    <div class="col-md-6"><label class="form-label">منطقه زمانی</label><input class="form-control ltr" name="timezone" value="<?= installer_e($values['timezone']) ?>"></div>
                    <div class="col-md-6"><label class="form-label">نام شرکت</label><input class="form-control" name="company_name" placeholder="مثلاً نوآوران زیبایی" value="<?= installer_e($values['company_name']) ?>" required></div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="birthday_required" id="birthday_required" <?= $values['birthday_required'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="birthday_required">اجباری کردن تاریخ تولد مشتریان</label>
                            <div class="form-text">پیش‌فرض: اختیاری است. فقط اگر می‌خواهید ثبت مشتری بدون تاریخ تولد ممکن نباشد، این گزینه را فعال کنید.</div>
                        </div>
                    </div>
                </div>

                <h2 class="h5 mb-3">مدیر اولیه</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">نام مدیر</label><input class="form-control" name="admin_name" value="<?= installer_e($values['admin_name']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">نام کاربری مدیر</label><input class="form-control ltr" name="admin_username" value="<?= installer_e($values['admin_username']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">رمز عبور مدیر</label><input class="form-control ltr" type="password" name="admin_password" required></div>
                    <div class="col-md-6"><label class="form-label">تکرار رمز عبور</label><input class="form-control ltr" type="password" name="admin_password_confirm" required></div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary" <?= $requirementsPassed ? '' : 'disabled' ?>>نصب و راه‌اندازی</button>
                    <?php if (!$requirementsPassed): ?>
                        <div class="text-danger small mt-2">تا زمانی که همه نیازمندی‌ها موفق نباشند، نصب فعال نمی‌شود.</div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
