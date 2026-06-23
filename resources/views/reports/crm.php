<?php use App\Core\Jalali; use App\Services\FollowupService; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">گزارش CRM پیگیری‌های فروش</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-success" href="<?= e(url('/reports/crm/export?' . http_build_query($filters))) ?>">خروجی Excel</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/reports/crm/print?' . http_build_query($filters))) ?>" target="_blank">چاپ</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'کل پیگیری‌ها' => $summary['total_followups'] ?? 0,
        'فروش موفق' => $summary['won_count'] ?? 0,
        'فروش لغو شده' => $summary['lost_count'] ?? 0,
        'در جریان' => $summary['open_count'] ?? 0,
        'مجموع فاکتورها' => money($summary['total_invoice_amount'] ?? 0) . ' ریال',
        'مجموع فروش موفق' => money($summary['won_amount'] ?? 0) . ' ریال',
    ] as $label => $value): ?>
        <div class="col-md-2"><div class="card stat"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e((string) $value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="card mb-4"><div class="card-header">عملکرد اپراتورها</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>اپراتور</th><th>تعداد</th><th>موفق</th><th>لغو</th><th>مجموع مبلغ</th></tr></thead>
    <tbody>
    <?php foreach ($byOperator as $op): ?>
        <tr>
            <td><?= e($op['name']) ?></td>
            <td><?= e($op['total']) ?></td>
            <td><?= e($op['won']) ?></td>
            <td><?= e($op['lost']) ?></td>
            <td><?= e(money($op['total_amount'])) ?> ریال</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>

<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get" action="<?= e(url('/reports/crm')) ?>">
        <div class="col-md-2"><input class="form-control ltr" type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
        <div class="col-md-2">
            <select class="form-select" name="operator_id">
                <option value="">همه اپراتورها</option>
                <?php foreach ($operators as $op): ?>
                    <option value="<?= e($op['id']) ?>" <?= (string)($filters['operator_id'] ?? '') === (string)$op['id'] ? 'selected' : '' ?>><?= e($op['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="sales_status">
                <option value="">همه وضعیت‌ها</option>
                <?php foreach (FollowupService::salesStatusOptions() as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= ($filters['sales_status'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="tier_id">
                <option value="">همه سطوح</option>
                <?php foreach ($tiers as $tier): ?>
                    <option value="<?= e($tier['id']) ?>" <?= (string)($filters['tier_id'] ?? '') === (string)$tier['id'] ? 'selected' : '' ?>><?= e($tier['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">جستجو</button></div>
    </form>
</div></div>

<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>مشتری</th><th>اپراتور</th><th>تاریخ</th><th>وضعیت</th><th>فاکتور</th><th>تماس بعدی</th><th>خرید</th></tr></thead>
    <tbody>
    <?php foreach ($followups as $f): ?>
        <tr>
            <td><?= e($f['first_name'] . ' ' . $f['last_name']) ?> <?= $f['company'] ? '(' . e($f['company']) . ')' : '' ?></td>
            <td><?= e($f['operator_name']) ?></td>
            <td><?= e($f['followup_date']) ?></td>
            <td><?= e(FollowupService::salesStatusLabel($f['sales_status'])) ?></td>
            <td><?= $f['invoice_amount'] ? e(money($f['invoice_amount'])) . ' ریال' : '-' ?></td>
            <td><?= e(Jalali::formatDate($f['next_contact_date'] ?? null)) ?></td>
            <td><?= $f['purchase_amount'] ? 'خرید #' . e($f['purchase_id'] ?? '') : '-' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
