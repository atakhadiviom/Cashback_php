<?php use App\Core\Jalali; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">مشتریان</h1>
    <div>
        <a class="btn btn-outline-success" href="<?= e(url('/customers/export?' . http_build_query($filters))) ?>">CSV</a>
        <a class="btn btn-primary" href="<?= e(url('/customers/create')) ?>">افزودن</a>
    </div>
</div>
<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get">
        <div class="col-md-3"><input class="form-control" name="q" placeholder="جستجو" value="<?= e($filters['q'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control" name="company" placeholder="شرکت" value="<?= e($filters['company'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="national_code" placeholder="کد ملی" value="<?= e($filters['national_code'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="contract_number" placeholder="شماره قرارداد" value="<?= e($filters['contract_number'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="phone_number" placeholder="موبایل" value="<?= e($filters['phone_number'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="birthday_month" placeholder="ماه تولد" value="<?= e($filters['birthday_month'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="birthday_day" placeholder="روز تولد" value="<?= e($filters['birthday_day'] ?? '') ?>"></div>
        <div class="col-md-2 col-xl-1"><button class="btn btn-secondary w-100 text-nowrap">جستجو</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>نام</th><th>شرکت</th><th>کد ملی</th><th>قرارداد</th><th>موبایل</th><th>تولد</th><th>کیف پول</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></td>
            <td><?= e($customer['company'] ?? '') ?></td>
            <td class="ltr"><?= e($customer['national_code']) ?></td>
            <td class="ltr"><?= e($customer['contract_number'] ?? '') ?></td>
            <td class="ltr"><?= e($customer['phone_number']) ?></td>
            <td><?= e(Jalali::formatDate($customer['birthday'])) ?></td>
            <td><?= e(money($customer['wallet_balance'])) ?> ریال</td>
            <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/customers/show?id=' . $customer['id'])) ?>">جزئیات</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
