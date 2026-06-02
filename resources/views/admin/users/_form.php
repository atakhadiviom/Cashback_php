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
<div class="mt-4"><button class="btn btn-primary">ذخیره</button><a class="btn btn-outline-secondary" href="<?= e(url('/admin/users')) ?>">بازگشت</a></div>
