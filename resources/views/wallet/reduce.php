<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">کسر از کیف پول: <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h1>
<div class="card"><div class="card-body">
<p>موجودی فعلی: <strong><?= e(money($customer['wallet_balance'])) ?> ریال</strong></p>
<form method="post" action="<?= e(url('/wallet/reduce')) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <input type="hidden" name="customer_id" value="<?= e($customer['id']) ?>">
    <div class="col-md-4"><label class="form-label">مبلغ</label><input class="form-control ltr" name="amount" required><?php if (!empty($errors['amount'])): ?><div class="form-text-error"><?= e($errors['amount']) ?></div><?php endif; ?></div>
    <div class="col-md-8"><label class="form-label">دلیل</label><input class="form-control" name="reason" required></div>
    <div class="col-12"><button class="btn btn-danger">ثبت کسر</button></div>
</form>
</div></div>
