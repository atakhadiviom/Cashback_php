<?php use App\Core\Csrf; use App\Core\Jalali; ?>
<input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
<?php
$selectedCustomerId = $selectedCustomer ? (string) $selectedCustomer['id'] : '';
$selectedCustomerLabel = '';
if ($selectedCustomer) {
    $company = $selectedCustomer['company'] ?? '';
    $contract = $selectedCustomer['contract_number'] ?? '';
    $selectedCustomerLabel = $selectedCustomer['first_name'] . ' ' . $selectedCustomer['last_name']
        . ($company !== '' ? ' - ' . $company : '')
        . ($contract !== '' ? ' - قرارداد ' . $contract : '')
        . ' - ' . $selectedCustomer['phone_number'];
}

$currentEnd = (string) ($selectedCustomer['contract_ends_at'] ?? '');
$currentStartLabel = !empty($selectedCustomer['contract_starts_at']) ? Jalali::formatDate($selectedCustomer['contract_starts_at']) : '-';
$currentEndLabel = $currentEnd !== '' ? Jalali::formatDate($currentEnd) : '-';

$startDisplay = (string) ($renewal['new_starts_at'] ?? '');
$endDisplay = (string) ($renewal['new_ends_at'] ?? '');
if ($endDisplay === '' && $currentEnd !== '') {
    $nextYear = strtotime($currentEnd . ' +1 year');
    if ($nextYear !== false) {
        $endDisplay = Jalali::toInputValue(date('Y-m-d', $nextYear));
    }
}
$noteValue = (string) ($renewal['note'] ?? '');
?>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">مشتری</label>
        <input type="hidden" name="customer_id" id="renewal-customer-id" value="<?= e($selectedCustomerId) ?>">
        <div class="customer-combobox">
            <input class="form-control" id="renewal-customer-picker" data-search-url="<?= e(url('/customers/search')) ?>" value="<?= e($selectedCustomerLabel) ?>" placeholder="نام، شرکت، قرارداد یا موبایل" autocomplete="off" required>
            <div class="customer-results shadow-sm" id="renewal-customer-results" hidden>
                <div class="customer-results-empty" data-empty hidden>مشتری پیدا نشد.</div>
                <div class="customer-results-list"></div>
            </div>
        </div>
        <?php if (!empty($errors['customer_id'])): ?><div class="form-text-error"><?= e($errors['customer_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">قرارداد فعلی</label>
        <div class="form-control-plaintext">شروع: <?= e($currentStartLabel) ?> — پایان: <?= e($currentEndLabel) ?></div>
        <div class="form-text">پس از ثبت، یادآوری پیامکی ۵ روز قبل از تاریخ پایان جدید ارسال می‌شود (در صورت فعال بودن پیامک تمدید).</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاریخ شروع جدید (شمسی)</label>
        <input class="form-control ltr" type="text" name="new_starts_at" data-jdp data-jdp-only-date placeholder="1404/01/01" value="<?= e($startDisplay) ?>" autocomplete="off">
        <div class="form-text">اختیاری؛ اگر خالی بماند تاریخ شروع قبلی حفظ می‌شود.</div>
        <?php if (!empty($errors['new_starts_at'])): ?><div class="form-text-error"><?= e($errors['new_starts_at']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاریخ پایان جدید (شمسی)</label>
        <input class="form-control ltr" type="text" name="new_ends_at" data-jdp data-jdp-only-date placeholder="1405/01/01" value="<?= e($endDisplay) ?>" required autocomplete="off">
        <?php if (!empty($errors['new_ends_at'])): ?><div class="form-text-error"><?= e($errors['new_ends_at']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">یادداشت (اختیاری)</label>
        <input class="form-control" name="note" maxlength="500" value="<?= e($noteValue) ?>">
    </div>
    <div class="col-12">
        <button class="btn btn-primary">ثبت تمدید</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/contract-renewals')) ?>">بازگشت</a>
    </div>
</div>
<link href="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.css')) ?>" rel="stylesheet">
<script src="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jalaliDatepicker !== 'undefined') {
        jalaliDatepicker.startWatch({ separatorChars: { date: '/' }, persianDigits: true, autoShow: true, autoHide: true });
    }
});
</script>
