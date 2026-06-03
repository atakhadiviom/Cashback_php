<?php use App\Core\Csrf; use App\Core\Jalali; ?>
<input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
<?php if (!empty($customer['id'])): ?><input type="hidden" name="id" value="<?= e($customer['id']) ?>"><?php endif; ?>
<?php
$birthdayRaw = (string) ($customer['birthday'] ?? '');
if ($birthdayRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', \normalize_digits($birthdayRaw))) {
    $birthdayDisplay = Jalali::toInputValue($birthdayRaw);
} else {
    $birthdayDisplay = $birthdayRaw;
}
?>
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
        <label class="form-label">شناسه معرف (اختیاری)</label>
        <input class="form-control ltr" name="referred_by_customer_id" value="<?= e($customer['referred_by_customer_id'] ?? '') ?>" placeholder="ID مشتری معرف">
    </div>
    <div class="col-md-4">
        <label class="form-label">تاریخ تولد شمسی</label>
        <input
            class="form-control ltr"
            type="text"
            name="birthday"
            id="customer-birthday"
            data-jdp
            data-jdp-only-date
            placeholder="1403/06/15"
            value="<?= e($birthdayDisplay) ?>"
            autocomplete="off"
        >
        <div class="form-text">از تقویم شمسی انتخاب کنید. در پایگاه داده به‌صورت میلادی ذخیره می‌شود.</div>
        <?php if (!empty($errors['birthday'])): ?><div class="form-text-error"><?= e($errors['birthday']) ?></div><?php endif; ?>
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary">ذخیره</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('/customers')) ?>">بازگشت</a>
</div>
<link href="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.css')) ?>" rel="stylesheet">
<script src="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jalaliDatepicker !== 'undefined') {
        jalaliDatepicker.startWatch({
            separatorChars: { date: '/' },
            persianDigits: true,
            autoShow: true,
            autoHide: true,
        });
    }
});
</script>
