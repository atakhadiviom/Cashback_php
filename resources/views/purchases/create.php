<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">ثبت خرید</h1>
<div class="card"><div class="card-body">
<form method="get" action="<?= e(url('/purchases/create')) ?>" class="row g-2 mb-3">
    <div class="col-md-9">
        <label class="form-label">جستجوی مشتری</label>
        <input
            type="search"
            class="form-control"
            name="q"
            id="purchase-customer-search"
            placeholder="نام، نام خانوادگی، کد ملی یا شماره موبایل"
            value="<?= e($filters['q'] ?? '') ?>"
            autocomplete="off"
        >
    </div>
    <div class="col-md-3 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-secondary w-100">جستجو</button>
        <?php if (($filters['q'] ?? '') !== ''): ?>
            <a class="btn btn-outline-secondary" href="<?= e(url('/purchases/create')) ?>">پاک کردن</a>
        <?php endif; ?>
    </div>
</form>

<form method="post" action="<?= e(url('/purchases/create')) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <?php if (($filters['q'] ?? '') !== ''): ?>
        <input type="hidden" name="search_q" value="<?= e($filters['q']) ?>">
    <?php endif; ?>
    <div class="col-md-6">
        <label class="form-label">مشتری</label>
        <select class="form-select" name="customer_id" id="purchase-customer-select" required>
            <option value="">انتخاب کنید</option>
            <?php
            $selectedId = (string) ($_POST['customer_id'] ?? $_GET['customer_id'] ?? '');
            foreach ($customers as $customer):
                $label = $customer['first_name'] . ' ' . $customer['last_name'] . ' - ' . $customer['national_code'] . ' - ' . $customer['phone_number'];
                $searchText = $customer['first_name'] . ' ' . $customer['last_name'] . ' ' . $customer['national_code'] . ' ' . $customer['phone_number'];
            ?>
                <option
                    value="<?= e($customer['id']) ?>"
                    data-search="<?= e($searchText) ?>"
                    <?= (string) $customer['id'] === $selectedId ? 'selected' : '' ?>
                ><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (empty($customers) && ($filters['q'] ?? '') !== ''): ?>
            <div class="form-text">مشتری‌ای با این جستجو پیدا نشد.</div>
        <?php endif; ?>
        <?php if (!empty($errors['customer_id'])): ?><div class="form-text-error"><?= e($errors['customer_id']) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label">مبلغ خرید</label>
        <input class="form-control ltr" name="amount" value="<?= e($_POST['amount'] ?? '') ?>" required>
        <?php if (!empty($errors['amount'])): ?><div class="form-text-error"><?= e($errors['amount']) ?></div><?php endif; ?>
    </div>
    <div class="col-12"><button class="btn btn-primary">ثبت خرید و محاسبه ۵٪ کش‌بک</button></div>
</form>
</div></div>
