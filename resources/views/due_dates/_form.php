<?php use App\Core\Csrf; use App\Core\Auth; use App\Core\Jalali; ?>
<?php
$selectedCustomerId = (string) ($dueDate['customer_id'] ?? $_GET['customer_id'] ?? '');
$selectedCustomerLabel = '';
$customerOptions = [];
foreach ($customers as $customer) {
    $company = $customer['company'] ?? '';
    $label = $customer['first_name'] . ' ' . $customer['last_name']
        . ($company !== '' ? ' - ' . $company : '')
        . ' - ' . $customer['phone_number'];
    $customerOptions[] = ['id' => (string) $customer['id'], 'label' => $label];
    if ((string) $customer['id'] === $selectedCustomerId) {
        $selectedCustomerLabel = $label;
    }
}

$selectedPurchaseId = (string) ($dueDate['purchase_id'] ?? '');
$selectedInvoiceLabel = '';
$invoiceOptions = [];
foreach ($invoices as $invoice) {
    $label = ($invoice['invoice_ref'] ?? '') . ' - ' . trim(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? ''))
        . ' - ' . money($invoice['amount']) . ' ریال';
    $invoiceOptions[] = ['id' => (string) $invoice['id'], 'label' => $label];
    if ((string) $invoice['id'] === $selectedPurchaseId) {
        $selectedInvoiceLabel = $label;
    }
}

$dueDateDisplay = '';
$dueDateRaw = (string) ($dueDate['due_date'] ?? '');
if ($dueDateRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', \normalize_digits($dueDateRaw))) {
    $dueDateDisplay = Jalali::toInputValue($dueDateRaw);
} else {
    $dueDateDisplay = $dueDateRaw;
}

$selectedOperatorId = (string) ($dueDate['operator_id'] ?? Auth::id() ?? '');
$isEdit = !empty($dueDate['id']);
?>
<input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
<?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($dueDate['id']) ?>"><?php endif; ?>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">مشتری</label>
        <input type="hidden" name="customer_id" id="due-date-customer-id" value="<?= e($selectedCustomerId) ?>">
        <div class="customer-combobox">
            <input class="form-control" id="due-date-customer-picker" value="<?= e($selectedCustomerLabel) ?>" placeholder="نام یا موبایل مشتری" autocomplete="off" required>
            <div class="customer-results shadow-sm" id="due-date-customer-results" hidden>
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
        <label class="form-label">فاکتور (اختیاری)</label>
        <input type="hidden" name="purchase_id" id="due-date-purchase-id" value="<?= e($selectedPurchaseId) ?>">
        <div class="customer-combobox">
            <input class="form-control ltr" id="due-date-invoice-picker" value="<?= e($selectedInvoiceLabel) ?>" placeholder="شماره فاکتور یا نام مشتری" autocomplete="off">
            <div class="customer-results shadow-sm" id="due-date-invoice-results" hidden>
                <div class="customer-results-empty" data-empty hidden>فاکتوری پیدا نشد.</div>
                <div class="customer-results-list">
                    <?php foreach ($invoiceOptions as $option): ?>
                        <button type="button" class="customer-result-item" data-id="<?= e($option['id']) ?>" data-label="<?= e($option['label']) ?>" data-search="<?= e($option['label']) ?>" hidden><?= e($option['label']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="form-text">فاکتورهای ثبت‌شده با شماره مرجع نمایش داده می‌شوند.</div>
        <?php if (!empty($errors['purchase_id'])): ?><div class="form-text-error"><?= e($errors['purchase_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاریخ سررسید (شمسی)</label>
        <input class="form-control ltr" type="text" name="due_date" data-jdp data-jdp-only-date placeholder="1403/06/15" value="<?= e($dueDateDisplay) ?>" autocomplete="off" required>
        <?php if (!empty($errors['due_date'])): ?><div class="form-text-error"><?= e($errors['due_date']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">مبلغ (ریال)</label>
        <input class="form-control ltr" name="amount" value="<?= e($dueDate['amount'] ?? '') ?>" inputmode="numeric" data-money required>
        <?php if (!empty($errors['amount'])): ?><div class="form-text-error"><?= e($errors['amount']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">نوع سررسید</label>
        <select class="form-select" name="due_type" required>
            <?php foreach ($dueTypes as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($dueDate['due_type'] ?? 'other') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['due_type'])): ?><div class="form-text-error"><?= e($errors['due_type']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">شماره چک یا فاکتور</label>
        <input class="form-control ltr" name="reference_number" value="<?= e($dueDate['reference_number'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">وضعیت</label>
        <select class="form-select" name="status" required>
            <?php foreach ($statuses as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($dueDate['status'] ?? 'pending') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['status'])): ?><div class="form-text-error"><?= e($errors['status']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">اپراتور ثبت‌کننده</label>
        <select class="form-select" name="operator_id" required <?= $isEdit ? 'disabled' : '' ?>>
            <option value="">انتخاب کنید</option>
            <?php foreach ($operators as $operator): ?>
                <?php if (empty($operator['is_active'])) continue; ?>
                <option value="<?= e($operator['id']) ?>" <?= (string) $operator['id'] === $selectedOperatorId ? 'selected' : '' ?>><?= e($operator['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($isEdit): ?><input type="hidden" name="operator_id" value="<?= e($selectedOperatorId) ?>"><?php endif; ?>
        <?php if (!empty($errors['operator_id'])): ?><div class="form-text-error"><?= e($errors['operator_id']) ?></div><?php endif; ?>
    </div>
    <?php if ($isEdit): ?>
    <div class="col-md-6">
        <label class="form-label">تاریخ ثبت</label>
        <input class="form-control" value="<?= e(Jalali::formatDate(substr((string) ($dueDate['created_at'] ?? ''), 0, 10))) ?>" disabled>
    </div>
    <?php endif; ?>
    <div class="col-12">
        <label class="form-label">توضیحات</label>
        <textarea class="form-control" name="description" rows="3"><?= e($dueDate['description'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary">ذخیره</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/due-dates')) ?>">بازگشت</a>
    </div>
</div>
<link href="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.css')) ?>" rel="stylesheet">
<script src="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof jalaliDatepicker !== 'undefined') {
        jalaliDatepicker.startWatch({ separatorChars: { date: '/' }, persianDigits: true, autoShow: true, autoHide: true });
    }
});
</script>
