<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Jalali;

$txLabels = ['cashback' => 'کش‌بک', 'reduction' => 'کسر', 'reversal' => 'برگشت'];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h1>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= e(url('/customers/edit?id=' . $customer['id'])) ?>">ویرایش</a>
        <?php if (Auth::can('purchase')): ?><a class="btn btn-primary" href="<?= e(url('/purchases/create?customer_id=' . $customer['id'])) ?>">ثبت خرید</a><?php endif; ?>
        <a class="btn btn-outline-primary" href="<?= e(url('/services/create?customer_id=' . $customer['id'])) ?>">ثبت سرویس</a>
        <?php if (Auth::can('reduce_wallet')): ?><a class="btn btn-outline-danger" href="<?= e(url('/wallet/reduce?customer_id=' . $customer['id'])) ?>">کسر کیف پول</a><?php endif; ?>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">کد ملی</div><div class="fw-bold ltr"><?= e($customer['national_code']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">موبایل</div><div class="fw-bold ltr"><?= e($customer['phone_number']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">موجودی کیف پول</div><div class="fw-bold"><?= e(money($customer['wallet_balance'])) ?> ریال</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">کل کش‌بک دریافتی</div><div class="fw-bold"><?= e(money($lifetimeEarned)) ?> ریال</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">تولد</div><div><?= e(Jalali::formatDate($customer['birthday'])) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">شماره قرارداد</div><div class="fw-bold ltr"><?= e($customer['contract_number'] ?? '-') ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">شروع قرارداد</div><div><?= e(Jalali::formatDate($customer['contract_starts_at'] ?? null)) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">پایان قرارداد</div><div><?= e(Jalali::formatDate($customer['contract_ends_at'] ?? null)) ?></div></div></div></div>
</div>
<?php if (!empty($services)): ?>
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>سرویس‌های اخیر</span>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/services/create?customer_id=' . $customer['id'])) ?>">ثبت سرویس</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>تاریخ</th><th>نوع</th><th>تکنسین</th><th>مبلغ</th><th>وضعیت</th><th>پیامک</th></tr></thead>
            <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><?= e(Jalali::formatDate($service['service_date'])) ?></td>
                    <td><?= e(\App\Services\ServiceRecordService::serviceTypeLabel($service['service_type'])) ?></td>
                    <td><?= e($service['technician_name']) ?></td>
                    <td><?= e(money($service['paid_amount'])) ?> ریال</td>
                    <td><?= $service['payment_status'] === 'paid' ? 'پرداخت شده' : 'پرداخت نشده' ?></td>
                    <td><?= e($service['sms_status'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white">تاریخچه خرید</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>مبلغ</th><th>کش‌بک</th><th>نرخ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?= e(money($purchase['amount'])) ?></td>
                            <td><?= e(money($purchase['cashback_amount'])) ?></td>
                            <td><?= e($purchase['cashback_percent_applied'] ?? '-') ?>٪</td>
                            <td><?= ($purchase['status'] ?? 'active') === 'voided' ? 'ابطال' : 'فعال' ?></td>
                            <td><?= e($purchase['created_at']) ?></td>
                            <td>
                                <?php if ($canVoid && ($purchase['status'] ?? 'active') === 'active'): ?>
                                <form method="post" action="<?= e(url('/purchases/void')) ?>" class="d-inline" onsubmit="return confirm('ابطال این خرید؟');">
                                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                                    <input type="hidden" name="purchase_id" value="<?= e($purchase['id']) ?>">
                                    <input type="hidden" name="customer_id" value="<?= e($customer['id']) ?>">
                                    <input type="hidden" name="reason" value="ابطال توسط مدیر">
                                    <button class="btn btn-sm btn-outline-danger">ابطال</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white">تراکنش‌های کیف پول</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>نوع</th><th>مبلغ</th><th>موجودی بعد</th><th>توضیح</th></tr></thead>
                    <tbody>
                    <?php foreach ($walletTransactions as $tx): ?>
                        <tr>
                            <td><?= e($txLabels[$tx['type']] ?? $tx['type']) ?></td>
                            <td><?= e(money($tx['amount'])) ?></td>
                            <td><?= e(money($tx['balance_after'])) ?></td>
                            <td><?= e($tx['reason']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php if (Auth::isAdmin()): ?>
<div class="mt-3 d-flex gap-2">
    <form method="post" action="<?= e(url('/customers/delete')) ?>" onsubmit="return confirm('حذف نرم مشتری؟');">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="id" value="<?= e($customer['id']) ?>">
        <button class="btn btn-outline-secondary btn-sm">حذف نرم</button>
    </form>
    <form method="post" action="<?= e(url('/customers/anonymize')) ?>" onsubmit="return confirm('ناشناس‌سازی غیرقابل بازگشت؟');">
        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="id" value="<?= e($customer['id']) ?>">
        <button class="btn btn-outline-danger btn-sm">ناشناس‌سازی</button>
    </form>
</div>
<?php endif; ?>
