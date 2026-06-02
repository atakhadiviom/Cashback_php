<h1 class="h3 mb-4">گزارش فعالیت‌ها</h1>
<div class="card mb-3"><div class="card-body"><form class="row g-2" method="get">
<div class="col-md-2"><input class="form-control ltr" type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
<div class="col-md-2"><input class="form-control ltr" type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
<div class="col-md-2"><select class="form-select" name="user_id"><option value="">همه کاربران</option><?php foreach ($users as $user): ?><option value="<?= e($user['id']) ?>" <?= (string)($filters['user_id'] ?? '') === (string)$user['id'] ? 'selected' : '' ?>><?= e($user['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><input class="form-control" name="activity_type" placeholder="نوع فعالیت" value="<?= e($filters['activity_type'] ?? '') ?>"></div>
<div class="col-md-2"><input class="form-control" name="customer" placeholder="مشتری/کد ملی" value="<?= e($filters['customer'] ?? '') ?>"></div>
<div class="col-md-2"><button class="btn btn-secondary w-100">فیلتر</button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>کاربر</th><th>نوع</th><th>توضیح</th><th>مشتری</th><th>IP</th><th>تاریخ</th></tr></thead><tbody><?php foreach ($logs as $log): ?><tr><td><?= e($log['user_name']) ?></td><td><?= e($log['activity_type']) ?></td><td><?= e($log['description']) ?></td><td><?= e(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '') . ' ' . ($log['national_code'] ?? ''))) ?></td><td><?= e($log['ip_address']) ?></td><td><?= e($log['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
