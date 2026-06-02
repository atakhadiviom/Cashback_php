<?php use App\Core\Jalali; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h1>
    <div>
        <a class="btn btn-outline-secondary" href="<?= e(url('/customers/edit?id=' . $customer['id'])) ?>">ویرایش</a>
        <a class="btn btn-primary" href="<?= e(url('/purchases/create?customer_id=' . $customer['id'])) ?>">ثبت خرید</a>
        <a class="btn btn-outline-danger" href="<?= e(url('/wallet/reduce?customer_id=' . $customer['id'])) ?>">کسر کیف پول</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">کد ملی</div><div class="fw-bold ltr"><?= e($customer['national_code']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">موبایل</div><div class="fw-bold ltr"><?= e($customer['phone_number']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">کیف پول</div><div class="fw-bold"><?= e(money($customer['wallet_balance'])) ?> ریال</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">تولد</div><div><?= e(Jalali::formatDate($customer['birthday'])) ?></div></div></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-header bg-white">تاریخچه خرید</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>مبلغ</th><th>کش‌بک</th><th>اپراتور</th><th>تاریخ</th></tr></thead><tbody>
        <?php foreach ($purchases as $purchase): ?><tr><td><?= e(money($purchase['amount'])) ?></td><td><?= e(money($purchase['cashback_amount'])) ?></td><td><?= e($purchase['created_by_name']) ?></td><td><?= e($purchase['created_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header bg-white">تراکنش‌های کیف پول</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>نوع</th><th>مبلغ</th><th>موجودی بعد</th><th>توضیح</th></tr></thead><tbody>
        <?php foreach ($walletTransactions as $tx): ?><tr><td><?= e($tx['type'] === 'cashback' ? 'کش‌بک' : 'کسر') ?></td><td><?= e(money($tx['amount'])) ?></td><td><?= e(money($tx['balance_after'])) ?></td><td><?= e($tx['reason']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
</div>
