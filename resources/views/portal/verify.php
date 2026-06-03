<?php use App\Core\Csrf; ?>
<h1 class="h4 mb-3">تأیید کد</h1>
<form method="post" action="<?= e(url('/portal/verify')) ?>">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="phone" value="<?= e($phone) ?>">
    <div class="mb-3"><label class="form-label">کد ۶ رقمی</label><input class="form-control ltr" name="code" required maxlength="6"></div>
    <?php if (!empty($errors['code'])): ?><div class="text-danger small mb-2"><?= e($errors['code']) ?></div><?php endif; ?>
    <button class="btn btn-primary w-100">ورود</button>
</form>
