<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0">مدیریت کاربران</h1><a class="btn btn-primary" href="<?= e(url('/admin/users/create')) ?>">کاربر جدید</a></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>نام</th><th>نام کاربری</th><th>نقش</th><th>وضعیت</th><th></th></tr></thead><tbody>
<?php foreach ($users as $user): ?><tr><td><?= e($user['name']) ?></td><td><?= e($user['username']) ?></td><td><?= e($user['role']) ?></td><td><?= (int)$user['is_active'] ? 'فعال' : 'غیرفعال' ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/users/edit?id=' . $user['id'])) ?>">ویرایش</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
