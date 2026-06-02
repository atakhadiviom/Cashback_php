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
$lockFile = $root . '/storage/installed.lock';
$configFile = $root . '/config/config.php';
$schemaFile = $root . '/database/schema.sql';
$errors = [];
$success = false;

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
        "];\n";
}

$requirements = [
    'PHP 8.1+' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO' => extension_loaded('pdo'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'cURL' => extension_loaded('curl'),
    'mbstring' => extension_loaded('mbstring'),
    'Session' => function_exists('session_start'),
    'config writable' => is_writable(dirname($configFile)) && (!file_exists($configFile) || is_writable($configFile)),
    'storage writable' => is_writable($root . '/storage'),
    'schema readable' => is_readable($schemaFile),
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

if (file_exists($lockFile)) {
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
    foreach ($requirements as $label => $ok) {
        if (!$ok) {
            $errors[] = 'نیازمندی برقرار نیست: ' . $label;
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
                throw new RuntimeException('نوشتن فایل config/config.php انجام نشد.');
            }

            if (file_put_contents($lockFile, 'Installed at ' . $now . PHP_EOL) === false) {
                throw new RuntimeException('ایجاد فایل قفل نصب انجام نشد.');
            }

            $success = true;
        } catch (Throwable $exception) {
            $errors[] = 'خطای نصب: ' . $exception->getMessage();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f5f7fb; color: #1f2937; }
        .shell { max-width: 980px; margin: 0 auto; padding: 32px 16px; }
        .card { border-radius: 8px; border-color: #e5e7eb; }
        .ltr { direction: ltr; unicode-bidi: embed; }
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

        <div class="card mb-4">
            <div class="card-header bg-white">بررسی نیازمندی‌ها</div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($requirements as $label => $ok): ?>
                        <div class="col-md-4">
                            <span class="badge <?= $ok ? 'bg-success' : 'bg-danger' ?>"><?= $ok ? 'OK' : 'FAIL' ?></span>
                            <?= installer_e($label) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <form method="post" class="card">
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
                    <div class="col-md-6"><label class="form-label">Base URL</label><input class="form-control ltr" name="base_url" placeholder="مثلاً /cashback یا خالی" value="<?= installer_e($values['base_url']) ?>"></div>
                    <div class="col-md-6"><label class="form-label">منطقه زمانی</label><input class="form-control ltr" name="timezone" value="<?= installer_e($values['timezone']) ?>"></div>
                    <div class="col-md-6"><label class="form-label">نام شرکت</label><input class="form-control" name="company_name" placeholder="مثلاً نوآوران زیبایی" value="<?= installer_e($values['company_name']) ?>" required></div>
                    <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="birthday_required" id="birthday_required" <?= $values['birthday_required'] ? 'checked' : '' ?>><label class="form-check-label" for="birthday_required">تاریخ تولد الزامی باشد</label></div></div>
                </div>

                <h2 class="h5 mb-3">مدیر اولیه</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">نام مدیر</label><input class="form-control" name="admin_name" value="<?= installer_e($values['admin_name']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">نام کاربری مدیر</label><input class="form-control ltr" name="admin_username" value="<?= installer_e($values['admin_username']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">رمز عبور مدیر</label><input class="form-control ltr" type="password" name="admin_password" required></div>
                    <div class="col-md-6"><label class="form-label">تکرار رمز عبور</label><input class="form-control ltr" type="password" name="admin_password_confirm" required></div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary">نصب و راه‌اندازی</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
