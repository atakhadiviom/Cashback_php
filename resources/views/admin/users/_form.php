<?php use App\Core\Csrf; ?>
<input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
<?php if (!empty($user['id'])): ?><input type="hidden" name="id" value="<?= e($user['id']) ?>"><?php endif; ?>
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">نام</label><input class="form-control" name="name" value="<?= e($user['name'] ?? '') ?>" required><?php if (!empty($errors['name'])): ?><div class="form-text-error"><?= e($errors['name']) ?></div><?php endif; ?></div>
    <div class="col-md-6"><label class="form-label">نام کاربری</label><input class="form-control ltr" name="username" value="<?= e($user['username'] ?? '') ?>" required><?php if (!empty($errors['username'])): ?><div class="form-text-error"><?= e($errors['username']) ?></div><?php endif; ?></div>
    <div class="col-md-4"><label class="form-label">رمز عبور</label><input class="form-control ltr" type="password" name="password" <?= empty($user['id']) ? 'required' : '' ?>><?php if (!empty($errors['password'])): ?><div class="form-text-error"><?= e($errors['password']) ?></div><?php endif; ?></div>
    <div class="col-md-4"><label class="form-label">نقش</label><select class="form-select" name="role"><option value="operator" <?= ($user['role'] ?? '') === 'operator' ? 'selected' : '' ?>>اپراتور</option><option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>ادمین</option></select></div>
    <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="active" <?= !isset($user['is_active']) || (int)$user['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="active">فعال</label></div></div>
</div>
<?php if (($user['role'] ?? 'operator') !== 'admin'): ?>
<?php
$perms = is_array($user['permissions'] ?? null) ? $user['permissions'] : [];
$permLabels = [
    'purchase' => 'ثبت خرید',
    'reduce_wallet' => 'کسر کیف پول',
    'export' => 'خروجی CSV',
    'void_purchase' => 'ابطال خرید',
    'import_customers' => 'ورود فایل مشتریان',
    'manage_settings' => 'تنظیمات',
    'manage_api' => 'کلید API',
    'manage_loyalty' => 'سطوح و پروموشن',
];
?>
<div class="mt-3"><label class="form-label">دسترسی‌های اپراتور</label>
<div class="row g-2">
<?php foreach ($permLabels as $key => $label): ?>
    <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="perm_<?= e($key) ?>" id="perm_<?= e($key) ?>" <?= !empty($perms[$key]) || empty($user['id']) && in_array($key, ['purchase','reduce_wallet','export'], true) ? 'checked' : '' ?>><label class="form-check-label" for="perm_<?= e($key) ?>"><?= e($label) ?></label></div></div>
<?php endforeach; ?>
</div></div>
<div class="mt-4 border rounded p-3 bg-light">
    <label class="form-label fw-bold">مدیریت سطح دسترسی کاربران</label>
    <?php
    $dataScope = (string) ($perms['data_access_scope'] ?? ($user['data_access_scope'] ?? 'self'));
    $allowedIds = $perms['data_access_user_ids'] ?? ($user['data_access_user_ids'] ?? []);
    if (!is_array($allowedIds)) { $allowedIds = []; }
    ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">محدوده مشاهده اطلاعات</label>
            <select class="form-select" name="data_access_scope">
                <option value="self" <?= $dataScope === 'self' ? 'selected' : '' ?>>فقط اطلاعات خودش (پیش‌فرض)</option>
                <option value="selected" <?= $dataScope === 'selected' ? 'selected' : '' ?>>اطلاعات کاربران مشخص</option>
                <option value="all" <?= $dataScope === 'all' ? 'selected' : '' ?>>تمام کاربران</option>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">کاربران قابل مشاهده در حالت مشخص</label>
            <select class="form-select" name="data_access_user_ids[]" multiple size="4">
                <?php foreach (($users ?? []) as $candidate): ?>
                    <?php if (!empty($user['id']) && (int)$candidate['id'] === (int)$user['id']) { continue; } ?>
                    <option value="<?= e($candidate['id']) ?>" <?= in_array((int)$candidate['id'], array_map('intval', $allowedIds), true) ? 'selected' : '' ?>><?= e($candidate['name'] . ' (' . $candidate['username'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">خود کاربر همیشه به اطلاعات خودش دسترسی دارد.</div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="data_access_can_modify_others" id="modify_others" <?= !empty($perms['data_access_can_modify_others']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="modify_others">اجازه ویرایش/حذف اطلاعات دیگران</label>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="mt-4"><button class="btn btn-primary">ذخیره</button><a class="btn btn-outline-secondary" href="<?= e(url('/admin/users')) ?>">بازگشت</a></div>
