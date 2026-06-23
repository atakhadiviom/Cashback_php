<?php use App\Core\Jalali; use App\Services\FollowupService; ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>گزارش CRM پیگیری‌ها</title>
    <style>
        body { font-family: Tahoma, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: right; }
        th { background: #f0f0f0; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="header">
    <h2>گزارش CRM پیگیری‌های فروش</h2>
    <p>تاریخ چاپ: <?= e(Jalali::formatDate(date('Y-m-d'))) ?></p>
</div>
<table>
    <thead><tr><th>مشتری</th><th>اپراتور</th><th>تاریخ</th><th>وضعیت</th><th>فاکتور</th><th>خرید</th></tr></thead>
    <tbody>
    <?php foreach ($followups as $f): ?>
        <tr>
            <td><?= e($f['first_name'] . ' ' . $f['last_name']) ?></td>
            <td><?= e($f['operator_name']) ?></td>
            <td><?= e($f['followup_date']) ?></td>
            <td><?= e(FollowupService::salesStatusLabel($f['sales_status'])) ?></td>
            <td><?= $f['invoice_amount'] ? e(money($f['invoice_amount'])) : '-' ?></td>
            <td><?= $f['purchase_amount'] ? 'بله' : 'خیر' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
