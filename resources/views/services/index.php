<?php use App\Core\Jalali; use App\Services\ServiceRecordService; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">سرویس‌ها</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-success" href="<?= e(url('/reports/services-export?' . http_build_query($filters))) ?>">CSV</a>
        <a class="btn btn-primary" href="<?= e(url('/services/create')) ?>">ثبت سرویس</a>
    </div>
</div>
<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get">
        <div class="col-md-2"><input class="form-control ltr" type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control" name="q" placeholder="جستجو" value="<?= e($filters['q'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="contract_number" placeholder="شماره قرارداد" value="<?= e($filters['contract_number'] ?? '') ?>"></div>
        <div class="col-md-2">
            <select class="form-select" name="technician_id">
                <option value="">همه تکنسین‌ها</option>
                <?php foreach ($technicians as $tech): ?>
                    <option value="<?= e($tech['id']) ?>" <?= (string)($filters['technician_id'] ?? '') === (string)$tech['id'] ? 'selected' : '' ?>><?= e($tech['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="payment_status">
                <option value="">همه وضعیت‌ها</option>
                <option value="paid" <?= ($filters['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>پرداخت شده</option>
                <option value="unpaid" <?= ($filters['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>پرداخت نشده</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="service_type">
                <option value="">همه انواع</option>
                <?php foreach ($serviceTypes as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['service_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">جستجو</button></div>
    </form>
</div></div>
<div class="row g-3 mb-4">
    <?php foreach ([
        'تعداد سرویس' => (int) ($summary['service_count'] ?? 0),
        'جمع پرداختی' => money($summary['paid_total'] ?? 0) . ' ریال',
        'پرداخت شده' => (int) ($summary['paid_count'] ?? 0),
        'پرداخت نشده' => (int) ($summary['unpaid_count'] ?? 0),
    ] as $label => $value): ?>
        <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e((string) $value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>
<div class="card mb-4"><div class="card-header bg-white">عملکرد تکنسین‌ها</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>تکنسین</th><th>تعداد</th><th>جمع پرداختی</th><th>پرداخت شده</th><th>پرداخت نشده</th></tr></thead>
    <tbody>
    <?php foreach ($byTechnician as $row): ?>
        <tr>
            <td><?= e($row['technician_name']) ?></td>
            <td><?= e($row['service_count']) ?></td>
            <td><?= e(money($row['paid_total'])) ?> ریال</td>
            <td><?= e($row['paid_count']) ?></td>
            <td><?= e($row['unpaid_count']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$byTechnician): ?><tr><td colspan="5" class="text-muted">موردی نیست.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>مشتری</th><th>قرارداد</th><th>تکنسین</th><th>تاریخ</th><th>نوع</th><th>مبلغ</th><th>وضعیت</th><th>پیامک</th></tr></thead>
    <tbody>
    <?php foreach ($services as $service): ?>
        <tr>
            <td><?= e($service['first_name'] . ' ' . $service['last_name']) ?></td>
            <td class="ltr"><?= e($service['contract_number'] ?? '') ?></td>
            <td><?= e($service['technician_name']) ?></td>
            <td><?= e(Jalali::formatDate($service['service_date'])) ?></td>
            <td><?= e(ServiceRecordService::serviceTypeLabel($service['service_type'])) ?></td>
            <td><?= e(money($service['paid_amount'])) ?> ریال</td>
            <td><?= $service['payment_status'] === 'paid' ? 'پرداخت شده' : 'پرداخت نشده' ?></td>
            <td><?= e($service['sms_status'] ?? '-') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
