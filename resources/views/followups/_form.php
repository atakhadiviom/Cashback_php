<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Jalali;

$selectedCustomerId = (string) ($followup['customer_id'] ?? $_GET['customer_id'] ?? '');
$selectedCustomerLabel = '';
$customerOptions = [];
foreach ($customers as $customer) {
    $contract = $customer['contract_number'] ?? '';
    $company = $customer['company'] ?? '';
    $label = $customer['first_name'] . ' ' . $customer['last_name']
        . ($company !== '' ? ' - ' . $company : '')
        . ($contract !== '' ? ' - قرارداد ' . $contract : '')
        . ' - ' . $customer['phone_number'];
    $customerOptions[] = ['id' => (string) $customer['id'], 'label' => $label];
    if ((string) $customer['id'] === $selectedCustomerId) {
        $selectedCustomerLabel = $label;
    }
}

$selectedOperatorId = (string) ($followup['operator_id'] ?? Auth::id() ?? '');
$followupDateValue = (string) ($followup['followup_date'] ?? '');
if ($followupDateValue !== '' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $followupDateValue)) {
    $followupDateValue = str_replace(' ', 'T', substr($followupDateValue, 0, 16));
}
$nextContactDisplay = '';
$nextContactRaw = (string) ($followup['next_contact_date'] ?? '');
if ($nextContactRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', \normalize_digits($nextContactRaw))) {
    $nextContactDisplay = Jalali::toInputValue($nextContactRaw);
} else {
    $nextContactDisplay = $nextContactRaw;
}
$reminderTimeValue = (string) ($followup['reminder_time'] ?? '');
if ($reminderTimeValue !== '') {
    $reminderTimeValue = substr($reminderTimeValue, 0, 5);
}
?>
<input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
<?php if (!empty($followup['id'])): ?><input type="hidden" name="id" value="<?= e($followup['id']) ?>"><?php endif; ?>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">مشتری</label>
        <input type="hidden" name="customer_id" id="followup-customer-id" value="<?= e($selectedCustomerId) ?>">
        <div class="customer-combobox">
            <input class="form-control" id="followup-customer-picker" value="<?= e($selectedCustomerLabel) ?>" placeholder="نام، شرکت، قرارداد یا موبایل" autocomplete="off" required>
            <div class="customer-results shadow-sm" id="followup-customer-results" hidden>
                <div class="customer-results-empty" data-empty hidden>مشتری پیدا نشد.</div>
                <div class="customer-results-list">
                    <?php foreach ($customerOptions as $option): ?>
                        <button type="button" class="customer-result-item" data-id="<?= e($option['id']) ?>" data-label="<?= e($option['label']) ?>" data-search="<?= e($option['label']) ?>" hidden><?= e($option['label']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($errors['customer_id'])): ?><div class="form-text-error"><?= e($errors['customer_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">اپراتور ثبت‌کننده</label>
        <select class="form-select" name="operator_id" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($operators as $operator): ?>
                <?php if (empty($operator['is_active'])) continue; ?>
                <option value="<?= e($operator['id']) ?>" <?= (string) $operator['id'] === $selectedOperatorId ? 'selected' : '' ?>><?= e($operator['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['operator_id'])): ?><div class="form-text-error"><?= e($errors['operator_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاریخ پیگیری</label>
        <input class="form-control ltr" type="datetime-local" name="followup_date" value="<?= e($followupDateValue) ?>">
        <div class="form-text">اگر خالی بماند، زمان فعلی ثبت می‌شود.</div>
        <?php if (!empty($errors['followup_date'])): ?><div class="form-text-error"><?= e($errors['followup_date']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">مبلغ پیش فاکتور (ریال)</label>
        <input class="form-control ltr" name="pre_invoice_amount" value="<?= e($followup['pre_invoice_amount'] ?? '') ?>" inputmode="numeric" data-money>
    </div>
    <div class="col-md-4">
        <label class="form-label">مبلغ فاکتور (ریال)</label>
        <input class="form-control ltr" name="invoice_amount" value="<?= e($followup['invoice_amount'] ?? '') ?>" inputmode="numeric" data-money>
    </div>
    <div class="col-md-4">
        <label class="form-label">وضعیت فروش</label>
        <select class="form-select" name="sales_status" required>
            <?php foreach ($statuses as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($followup['sales_status'] ?? 'negotiating') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['sales_status'])): ?><div class="form-text-error"><?= e($errors['sales_status']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاریخ تماس بعدی (شمسی)</label>
        <input class="form-control ltr" type="text" name="next_contact_date" data-jdp data-jdp-only-date placeholder="1403/06/15" value="<?= e($nextContactDisplay) ?>" autocomplete="off">
        <?php if (!empty($errors['next_contact_date'])): ?><div class="form-text-error"><?= e($errors['next_contact_date']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">ساعت یادآوری</label>
        <input class="form-control ltr" type="time" name="reminder_time" value="<?= e($reminderTimeValue) ?>">
        <?php if (!empty($errors['reminder_time'])): ?><div class="form-text-error"><?= e($errors['reminder_time']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">مسیر فایل ضمیمه (اختیاری)</label>
        <input class="form-control ltr" name="attachment_path" maxlength="255" value="<?= e($followup['attachment_path'] ?? '') ?>" placeholder="storage/followups/example.pdf">
    </div>
    <div class="col-md-6">
        <label class="form-label">دلیل لغو فروش (در صورت لغو)</label>
        <input class="form-control" name="lost_reason" value="<?= e($followup['lost_reason'] ?? '') ?>">
        <?php if (!empty($errors['lost_reason'])): ?><div class="form-text-error"><?= e($errors['lost_reason']) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
        <label class="form-label">توضیحات کامل مکالمه</label>
        <textarea class="form-control" name="conversation_notes" rows="5" required><?= e($followup['conversation_notes'] ?? '') ?></textarea>
        <?php if (!empty($errors['conversation_notes'])): ?><div class="form-text-error"><?= e($errors['conversation_notes']) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
        <button class="btn btn-primary">ذخیره</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/followups')) ?>">بازگشت</a>
    </div>
    <div class="col-12 mt-2">
        <div class="form-text">
            <strong>توضیح:</strong>
            اگر وضعیت را روی «فروش نهایی» بگذارید، سیستم یک خرید در جدول خریدها ثبت می‌کند و سطح مشتری به‌روزرسانی می‌شود.
            اگر وضعیت را روی «لغو فروش» بگذارید، دلیل لغو الزامی است و خریدی ثبت نمی‌شود.
        </div>
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
