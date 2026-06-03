<?php use App\Core\Csrf; use App\Core\Flash; ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پرتال مشتری — <?= e(config_value('app.company_name', '')) ?></title>
    <link href="<?= e(asset_url('vendor/bootstrap/bootstrap.rtl.min.css')) ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
</head>
<body class="guest-main">
<main class="page-shell py-5">
    <div class="container" style="max-width: 480px;">
        <?php foreach (Flash::all() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
        <?= $content ?>
    </div>
</main>
</body>
</html>
