<?php use App\Core\Csrf; use App\Core\Jalali; ?>
<h1 class="h3 mb-4">ثبت سرویس</h1>
<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/services/create')) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <?php
    $selectedId = $selectedCustomer ? (string) $selectedCustomer['id'] : '';
    $selectedLabel = '';
    if ($selectedCustomer) {
        $contract = $selectedCustomer['contract_number'] ?? '';
        $selectedLabel = $selectedCustomer['first_name'] . ' ' . $selectedCustomer['last_name']
            . ($contract !== '' ? ' - قرارداد ' . $contract : '')
            . ' - ' . ($selectedCustomer['national_code'] ?? '')
            . ' - ' . $selectedCustomer['phone_number'];
    }
  $serviceDateDisplay = '';
  $serviceDateRaw = (string) ($_POST['service_date'] ?? date('Y-m-d'));
  if ($serviceDateRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', \normalize_digits($serviceDateRaw))) {
      $serviceDateDisplay = Jalali::toInputValue($serviceDateRaw);
  } else {
      $serviceDateDisplay = $serviceDateRaw;
  }
    ?>
    <div class="col-md-6">
        <label class="form-label">مشتری</label>
        <input type="hidden" name="customer_id" id="service-customer-id" value="<?= e($selectedId) ?>">
        <div class="customer-combobox">
            <input class="form-control" id="service-customer-picker" data-search-url="<?= e(url('/customers/search')) ?>" value="<?= e($selectedLabel) ?>" placeholder="نام، قرارداد، کد ملی یا موبایل" autocomplete="off" required>
            <div class="customer-results shadow-sm" id="service-customer-results" hidden>
                <div class="customer-results-empty" data-empty hidden>مشتری پیدا نشد.</div>
                <div class="customer-results-list"></div>
            </div>
        </div>
        <?php if (!empty($errors['customer_id'])): ?><div class="form-text-error"><?= e($errors['customer_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">تکنسین</label>
        <select class="form-select" name="technician_id" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($technicians as $tech): ?>
                <?php if (empty($tech['is_active'])) continue; ?>
                <option value="<?= e($tech['id']) ?>" <?= (string)($tech['id']) === (string)($_POST['technician_id'] ?? '') ? 'selected' : '' ?>><?= e($tech['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['technician_id'])): ?><div class="form-text-error"><?= e($errors['technician_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاریخ سرویس (شمسی)</label>
        <input class="form-control ltr" type="text" name="service_date" data-jdp data-jdp-only-date placeholder="1403/06/15" value="<?= e($serviceDateDisplay) ?>" required autocomplete="off">
        <?php if (!empty($errors['service_date'])): ?><div class="form-text-error"><?= e($errors['service_date']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">نوع سرویس</label>
        <select class="form-select" name="service_type" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($serviceTypes as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($value === ($_POST['service_type'] ?? '')) ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['service_type'])): ?><div class="form-text-error"><?= e($errors['service_type']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
        <label class="form-label">مبلغ پرداختی (ریال)</label>
        <input class="form-control ltr" name="paid_amount" value="<?= e($_POST['paid_amount'] ?? '0') ?>" inputmode="numeric" data-money>
        <?php if (!empty($errors['paid_amount'])): ?><div class="form-text-error"><?= e($errors['paid_amount']) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
        <label class="form-label">توضیحات</label>
        <textarea class="form-control" name="description" rows="3"><?= e($_POST['description'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary">ثبت سرویس</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/services')) ?>">بازگشت</a>
    </div>
</form>
</div></div>
<link href="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.css')) ?>" rel="stylesheet">
<script src="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jalaliDatepicker !== 'undefined') {
        jalaliDatepicker.startWatch({ separatorChars: { date: '/' }, persianDigits: true, autoShow: true, autoHide: true });
    }
});
</script>
