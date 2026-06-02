<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">ثبت خرید</h1>
<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/purchases/create')) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <div class="col-md-6">
        <label class="form-label">مشتری</label>
        <select class="form-select" name="customer_id" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($customers as $customer): ?>
                <option value="<?= e($customer['id']) ?>" <?= (string)($customer['id']) === (string)($_GET['customer_id'] ?? '') ? 'selected' : '' ?>><?= e($customer['first_name'] . ' ' . $customer['last_name'] . ' - ' . $customer['national_code']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['customer_id'])): ?><div class="form-text-error"><?= e($errors['customer_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">مبلغ خرید</label>
        <input class="form-control ltr" name="amount" required>
        <?php if (!empty($errors['amount'])): ?><div class="form-text-error"><?= e($errors['amount']) ?></div><?php endif; ?>
    </div>
    <div class="col-12"><button class="btn btn-primary">ثبت خرید و محاسبه ۵٪ کش‌بک</button></div>
</form>
</div></div>
