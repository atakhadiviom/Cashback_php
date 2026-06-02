<h1 class="h3 mb-4">لاگ پیامک</h1>
<div class="card mb-3"><div class="card-body">
<form class="row g-2" method="get">
    <div class="col-md-3"><select class="form-select" name="event_type"><option value="">همه رویدادها</option><?php foreach (['purchase'=>'خرید','birthday'=>'تولد','wallet_reduction'=>'کسر کیف پول','welcome'=>'خوشامد'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= ($filters['event_type'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><select class="form-select" name="status"><option value="">همه وضعیت‌ها</option><?php foreach (['pending'=>'در انتظار','sent'=>'ارسال شده','failed'=>'ناموفق'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><button class="btn btn-secondary w-100">فیلتر</button></div>
</form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>مشتری</th><th>موبایل</th><th>رویداد</th><th>وضعیت</th><th>پیام</th><th>پاسخ</th><th>تاریخ</th></tr></thead><tbody>
<?php foreach ($logs as $log): ?><tr><td><?= e(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''))) ?></td><td class="ltr"><?= e($log['phone_number']) ?></td><td><?= e($log['event_type']) ?></td><td><?= e($log['status']) ?></td><td><?= e($log['message']) ?></td><td><?= e($log['provider_response']) ?></td><td><?= e($log['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
