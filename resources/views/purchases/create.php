<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">ثبت خرید</h1>
<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/purchases/create')) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <?php
    $selectedId = $selectedCustomer ? (string) $selectedCustomer['id'] : '';
    $selectedLabel = $selectedCustomer
        ? $selectedCustomer['first_name'] . ' ' . $selectedCustomer['last_name'] . ' - ' . $selectedCustomer['national_code'] . ' - ' . $selectedCustomer['phone_number']
        : '';
    ?>
    <div class="col-md-6">
        <label class="form-label">مشتری</label>
        <input type="hidden" name="customer_id" id="purchase-customer-id" value="<?= e($selectedId) ?>">
        <div class="customer-combobox">
            <input class="form-control" id="purchase-customer-picker" data-search-url="<?= e(url('/customers/search')) ?>" value="<?= e($selectedLabel) ?>" placeholder="نام، کد ملی یا شماره موبایل را تایپ کنید" autocomplete="off" required>
            <div class="customer-results shadow-sm" id="purchase-customer-results" hidden>
                <div class="customer-results-empty" data-empty hidden>مشتری پیدا نشد.</div>
                <div class="customer-results-list"></div>
            </div>
        </div>
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
