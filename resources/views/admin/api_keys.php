<?php

use App\Core\Csrf;

$scopeLabels = static function (string $scopes): string {
    $parts = [];
    foreach (explode(',', $scopes) as $scope) {
        $scope = trim($scope);
        if ($scope === 'read') {
            $parts[] = 'خواندن';
        } elseif ($scope === 'write') {
            $parts[] = 'نوشتن';
        } elseif ($scope === '*') {
            $parts[] = 'همه';
        }
    }

    return $parts === [] ? 'خواندن' : implode(' + ', $parts);
};
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="small text-muted">مدیریت</div>
        <h1 class="h3 mb-0">کلیدهای API</h1>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/system-status')) ?>">وضعیت سیستم</a>
</div>

<?php if ($newKey): ?>
    <div class="alert alert-warning">کلید جدید (فقط یک‌بار نمایش داده می‌شود): <code class="ltr user-select-all"><?= e($newKey) ?></code></div>
<?php endif; ?>

<div class="card mb-3"><div class="card-body">
    <form method="post" action="<?= e(url('/admin/api-keys')) ?>" class="row g-2 align-items-end">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
        <div class="col-md-4">
            <label class="form-label small mb-0">نام کلید</label>
            <input class="form-control" name="name" placeholder="مثلاً POS" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-0 d-block">دسترسی</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="scopes[]" value="read" id="new-scope-read" checked>
                <label class="form-check-label" for="new-scope-read">خواندن</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="scopes[]" value="write" id="new-scope-write" checked>
                <label class="form-check-label" for="new-scope-write">نوشتن</label>
            </div>
        </div>
        <div class="col-md-4"><button class="btn btn-primary w-100">ایجاد کلید</button></div>
    </form>
    <div class="form-text">«نوشتن» شامل ثبت خرید، شارژ/کسر کیف پول، ساخت و ویرایش مشتری و تغییرات باشگاه است.</div>
</div></div>

<div class="card"><div class="table-responsive">
    <table class="table table-bordered mb-0 align-middle">
        <thead><tr><th>نام</th><th>پیشوند</th><th>دسترسی</th><th>آخرین استفاده</th><th>وضعیت</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($keys as $key): ?>
            <?php
            $scopes = (string) ($key['scopes'] ?? 'read,write');
            $hasRead = str_contains($scopes, 'read') || str_contains($scopes, '*');
            $hasWrite = str_contains($scopes, 'write') || str_contains($scopes, '*');
            ?>
            <tr>
                <td><?= e($key['name']) ?></td>
                <td class="ltr"><?= e($key['key_prefix']) ?>…</td>
                <td class="small">
                    <span class="badge text-bg-<?= $hasWrite ? 'success' : 'secondary' ?>"><?= e($scopeLabels($scopes)) ?></span>
                    <form method="post" action="<?= e(url('/admin/api-keys/scopes')) ?>" class="d-flex align-items-center gap-2 mt-1">
                        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                        <input type="hidden" name="id" value="<?= e($key['id']) ?>">
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" name="scopes[]" value="read" id="read-<?= e($key['id']) ?>" <?= $hasRead ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="read-<?= e($key['id']) ?>">خواندن</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" name="scopes[]" value="write" id="write-<?= e($key['id']) ?>" <?= $hasWrite ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="write-<?= e($key['id']) ?>">نوشتن</label>
                        </div>
                        <button class="btn btn-sm btn-outline-primary">ذخیره</button>
                    </form>
                </td>
                <td class="small"><?= e(\App\Core\Jalali::formatDateTime($key['last_used_at'] ?? null) ?: '-') ?></td>
                <td><?= (int) $key['is_active'] ? 'فعال' : 'غیرفعال' ?></td>
                <td class="text-nowrap">
                    <?php if ((int) $key['is_active']): ?>
                        <form method="post" action="<?= e(url('/admin/api-keys/deactivate')) ?>" class="d-inline" data-confirm="این کلید غیرفعال شود؟">
                            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="id" value="<?= e($key['id']) ?>">
                            <button class="btn btn-sm btn-outline-danger">غیرفعال</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$keys): ?><tr><td colspan="6" class="text-muted">کلیدی ساخته نشده است.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
