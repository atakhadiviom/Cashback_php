<?php use App\Core\Csrf; ?>
<input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
<?php if (!empty($customer['id'])): ?><input type="hidden" name="id" value="<?= e($customer['id']) ?>"><?php endif; ?>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">نام</label>
        <input class="form-control" name="first_name" value="<?= e($customer['first_name'] ?? '') ?>" required>
        <?php if (!empty($errors['first_name'])): ?><div class="form-text-error"><?= e($errors['first_name']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">نام خانوادگی</label>
        <input class="form-control" name="last_name" value="<?= e($customer['last_name'] ?? '') ?>" required>
        <?php if (!empty($errors['last_name'])): ?><div class="form-text-error"><?= e($errors['last_name']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">کد ملی</label>
        <input class="form-control ltr" name="national_code" maxlength="10" value="<?= e($customer['national_code'] ?? '') ?>" required>
        <?php if (!empty($errors['national_code'])): ?><div class="form-text-error"><?= e($errors['national_code']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">موبایل</label>
        <input class="form-control ltr" name="phone_number" maxlength="11" value="<?= e($customer['phone_number'] ?? '') ?>" required>
        <?php if (!empty($errors['phone_number'])): ?><div class="form-text-error"><?= e($errors['phone_number']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاریخ تولد میلادی</label>
        <input class="form-control ltr" type="date" name="birthday" value="<?= e($customer['birthday'] ?? '') ?>">
        <?php if (!empty($errors['birthday'])): ?><div class="form-text-error"><?= e($errors['birthday']) ?></div><?php endif; ?>
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary">ذخیره</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('/customers')) ?>">بازگشت</a>
</div>
