<?php use App\Core\Csrf; ?>
<h1 class="h4 mb-3">ورود به پرتال کش‌بک</h1>
<p class="text-muted small">شماره موبایل ثبت‌شده در باشگاه مشتریان را وارد کنید.</p>
<form method="post" action="<?= e(url('/portal')) ?>">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label">موبایل</label>
        <input class="form-control ltr" name="phone" value="<?= e($phone ?? '') ?>" required maxlength="11">
        <?php if (!empty($errors['phone'])): ?><div class="text-danger small"><?= e($errors['phone']) ?></div><?php endif; ?>
        <?php if (!empty($errors['portal'])): ?><div class="text-danger small"><?= e($errors['portal']) ?></div><?php endif; ?>
    </div>
    <button class="btn btn-primary w-100">دریافت کد تأیید</button>
</form>
