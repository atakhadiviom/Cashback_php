<?php use App\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">سلام <?= e($customer['first_name']) ?></h1>
    <form method="post" action="<?= e(url('/portal/logout')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
        <button class="btn btn-sm btn-outline-secondary">خروج</button>
    </form>
</div>
<div class="card mb-3"><div class="card-body text-center">
    <div class="text-muted small">موجودی کیف پول</div>
    <div class="fs-3 fw-bold"><?= e(money($customer['wallet_balance'])) ?> ریال</div>
    <div class="text-muted small mt-2">کل کش‌بک دریافتی: <?= e(money($lifetime_earned)) ?> ریال</div>
</div></div>
<div class="card"><div class="card-header">آخرین تراکنش‌ها</div><ul class="list-group list-group-flush">
<?php foreach ($transactions as $tx): ?>
    <li class="list-group-item d-flex justify-content-between">
        <span><?= e($tx['type']) ?></span>
        <span><?= e(money($tx['amount'])) ?></span>
    </li>
<?php endforeach; ?>
</ul></div>
