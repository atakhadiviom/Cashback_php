<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">کلیدهای API</h1>
<?php if ($newKey): ?><div class="alert alert-warning">کلید جدید (فقط یک‌بار نمایش داده می‌شود): <code class="ltr user-select-all"><?= e($newKey) ?></code></div><?php endif; ?>
<div class="card mb-3"><div class="card-body">
<form method="post" action="<?= e(url('/admin/api-keys')) ?>" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <div class="col-md-8"><input class="form-control" name="name" placeholder="نام کلید (مثلاً POS)" required></div>
    <div class="col-md-4"><button class="btn btn-primary w-100">ایجاد کلید</button></div>
</form>
</div></div>
<table class="table table-bordered bg-white">
    <thead><tr><th>نام</th><th>پیشوند</th><th>آخرین استفاده</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($keys as $key): ?>
        <tr>
            <td><?= e($key['name']) ?></td>
            <td class="ltr"><?= e($key['key_prefix']) ?>…</td>
            <td><?= e($key['last_used_at'] ?? '-') ?></td>
            <td><?= (int)$key['is_active'] ? 'فعال' : 'غیرفعال' ?></td>
            <td>
                <?php if ((int)$key['is_active']): ?>
                <form method="post" action="<?= e(url('/admin/api-keys/deactivate')) ?>" class="d-inline">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                    <input type="hidden" name="id" value="<?= e($key['id']) ?>">
                    <button class="btn btn-sm btn-outline-danger">غیرفعال</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
