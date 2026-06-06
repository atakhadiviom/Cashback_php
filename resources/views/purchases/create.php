<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">ثبت خرید</h1>
<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/purchases/create')) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <?php
    $selectedId = (string) ($_POST['customer_id'] ?? $_GET['customer_id'] ?? '');
    $selectedLabel = '';
    $customerOptions = [];
    foreach ($customers as $customer) {
        $label = $customer['first_name'] . ' ' . $customer['last_name'] . ' - ' . $customer['national_code'] . ' - ' . $customer['phone_number'];
        $customerOptions[] = ['id' => (string) $customer['id'], 'label' => $label];
        if ((string) $customer['id'] === $selectedId) {
            $selectedLabel = $label;
        }
    }
    ?>
    <div class="col-md-6">
        <label class="form-label">مشتری</label>
        <input type="hidden" name="customer_id" id="purchase-customer-id" value="<?= e($selectedId) ?>">
        <input class="form-control" id="purchase-customer-picker" list="purchase-customer-options" value="<?= e($selectedLabel) ?>" placeholder="نام، کد ملی یا شماره موبایل را تایپ کنید" autocomplete="off" required>
        <datalist id="purchase-customer-options">
            <?php foreach ($customerOptions as $option): ?>
                <option value="<?= e($option['label']) ?>" data-id="<?= e($option['id']) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <div class="form-text">برای انتخاب مشتری، نام یا شماره موبایل را تایپ کنید و از لیست انتخاب کنید.</div>
        <?php if (!empty($errors['customer_id'])): ?><div class="form-text-error"><?= e($errors['customer_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">مبلغ خرید (ریال)</label>
        <input class="form-control ltr" name="amount" value="<?= e($_POST['amount'] ?? '') ?>" inputmode="numeric" data-money required>
        <?php if (!empty($errors['amount'])): ?><div class="form-text-error"><?= e($errors['amount']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">شماره فاکتور / مرجع (اختیاری)</label>
        <input class="form-control ltr" name="invoice_ref" value="<?= e($_POST['invoice_ref'] ?? '') ?>">
        <?php if (!empty($errors['invoice_ref'])): ?><div class="form-text-error"><?= e($errors['invoice_ref']) ?></div><?php endif; ?>
    </div>
    <?php if (!empty($needs_confirm) || !empty($errors['duplicate'])): ?>
    <div class="col-12">
        <div class="alert alert-warning">
            <?= e($errors['duplicate'] ?? 'خرید مشابه اخیر یافت شد.') ?>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="confirm_duplicate" id="confirm_duplicate" value="1">
                <label class="form-check-label" for="confirm_duplicate">تأیید ثبت مجدد</label>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-12">
        <button class="btn btn-primary">ثبت خرید (نرخ پایه <?= e($settings['cashback_percent'] ?? '5') ?>٪)</button>
    </div>
</form>
</div></div>
