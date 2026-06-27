<?php use App\Core\Jalali; use App\Services\DueDateService; ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>گزارش سررسیدها</title>
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
    <h2>گزارش سررسیدهای پرداخت</h2>
    <p>تاریخ چاپ: <?= e(Jalali::formatDate(date('Y-m-d'))) ?></p>
</div>
<table>
    <thead><tr><th>مشتری</th><th>نوع</th><th>مبلغ</th><th>تاریخ سررسید</th><th>شماره مرجع</th><th>وضعیت</th><th>اپراتور</th></tr></thead>
    <tbody>
    <?php foreach ($dueDates as $row): ?>
        <tr>
            <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= e(DueDateService::dueTypeLabel($row['due_type'])) ?></td>
            <td><?= e(money($row['amount'])) ?></td>
            <td><?= e(Jalali::formatDate($row['due_date'])) ?></td>
            <td><?= e($row['reference_number'] ?? '-') ?></td>
            <td><?= e(DueDateService::statusLabel($row['status'])) ?></td>
            <td><?= e($row['operator_name']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
