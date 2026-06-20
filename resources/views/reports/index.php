<?php use App\Core\Jalali; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">گزارش‌ها</h1>
    <a class="btn btn-outline-success" href="<?= e(url('/reports/export?' . http_build_query($filters))) ?>">خروجی CSV</a>
</div>
<div class="card mb-3"><div class="card-body">
<form class="row g-2" method="get">
    <div class="col-md-2"><input class="form-control ltr" type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
    <div class="col-md-2"><input class="form-control ltr" type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
    <div class="col-md-2"><input class="form-control" name="last_name" placeholder="نام خانوادگی" value="<?= e($filters['last_name'] ?? '') ?>"></div>
    <div class="col-md-2"><input class="form-control ltr" name="national_code" placeholder="کد ملی" value="<?= e($filters['national_code'] ?? '') ?>"></div>
    <div class="col-md-2"><input class="form-control ltr" name="phone_number" placeholder="موبایل" value="<?= e($filters['phone_number'] ?? '') ?>"></div>
    <div class="col-md-2"><select class="form-select" name="created_by"><option value="">همه اپراتورها</option><?php foreach ($users as $user): ?><option value="<?= e($user['id']) ?>" <?= (string)($filters['created_by'] ?? '') === (string)$user['id'] ? 'selected' : '' ?>><?= e($user['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><input class="form-control ltr" name="purchase_min" placeholder="حداقل خرید" value="<?= e($filters['purchase_min'] ?? '') ?>"></div>
    <div class="col-md-2"><input class="form-control ltr" name="purchase_max" placeholder="حداکثر خرید" value="<?= e($filters['purchase_max'] ?? '') ?>"></div>
    <div class="col-md-2"><input class="form-control ltr" name="cashback_min" placeholder="حداقل کش‌بک" value="<?= e($filters['cashback_min'] ?? '') ?>"></div>
    <div class="col-md-2"><input class="form-control ltr" name="cashback_max" placeholder="حداکثر کش‌بک" value="<?= e($filters['cashback_max'] ?? '') ?>"></div>
    <div class="col-md-2"><input class="form-control ltr" name="birthday_month" placeholder="ماه تولد" value="<?= e($filters['birthday_month'] ?? '') ?>"></div>
    <div class="col-md-2 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="include_voided" value="1" id="include_voided" <?= !empty($filters['include_voided']) ? 'checked' : '' ?>><label class="form-check-label" for="include_voided">شامل ابطال</label></div></div>
    <div class="col-md-2"><button class="btn btn-secondary w-100">اعمال</button></div>
</form>
</div></div>
<div class="row g-3 mb-4">
    <?php foreach (['کل مشتریان' => $summary['total_customers'], 'کل خریدها' => $summary['total_purchases'], 'جمع مبلغ خرید' => money($summary['total_amount']), 'جمع کش‌بک' => money($summary['total_cashback']), 'میانگین کش‌بک' => money($summary['avg_cashback']), 'موجودی کیف پول‌ها' => money($summary['wallet_balances'])] as $label => $value): ?>
        <div class="col-md-4 col-xl-2"><div class="card stat"><div class="card-body"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold mt-2"><?= e($value) ?></div></div></div></div>
    <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat"><div class="card-body"><div class="small text-muted">کش‌بک صادر شده (بازه)</div><div class="fw-bold"><?= e(money($liability['issued'] ?? 0)) ?> ریال</div></div></div></div>
    <div class="col-md-4"><div class="card stat"><div class="card-body"><div class="small text-muted">استفاده از کیف پول (بازه)</div><div class="fw-bold"><?= e(money($liability['redeemed'] ?? 0)) ?> ریال</div></div></div></div>
    <div class="col-md-4"><div class="card stat"><div class="card-body"><div class="small text-muted">تعهد کل کیف پول</div><div class="fw-bold"><?= e(money($outstandingLiability)) ?> ریال</div></div></div></div>
</div>
<div class="card mb-4"><div class="card-header bg-white">مشتریان غیرفعال (بدون خرید اخیر)</div><div class="card-body">
<form class="row g-2 mb-3" method="get">
    <?php foreach ($filters as $k => $v): if ($k === 'inactive_days') continue; ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
    <div class="col-md-3"><input class="form-control" name="inactive_days" value="<?= e($filters['inactive_days'] ?? '90') ?>" placeholder="روز"></div>
    <div class="col-md-2"><button class="btn btn-secondary">به‌روزرسانی</button></div>
</form>
<?php foreach ($inactiveCustomers as $c): ?><div><?= e($c['first_name'] . ' ' . $c['last_name']) ?> — <?= e($c['phone_number']) ?></div><?php endforeach; if (!$inactiveCustomers): ?><span class="text-muted">موردی نیست.</span><?php endif; ?>
</div></div>
<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="card"><div class="card-header bg-white">۱۰ مشتری برتر بر اساس خرید</div><div class="table-responsive"><table class="table mb-0"><tbody><?php foreach ($topAmount as $row): ?><tr><td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td><td><?= e(money($row['total'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header bg-white">۱۰ مشتری برتر بر اساس کش‌بک</div><div class="table-responsive"><table class="table mb-0"><tbody><?php foreach ($topCashback as $row): ?><tr><td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td><td><?= e(money($row['total'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
<div class="card mb-4"><div class="card-header bg-white">خریدهای فیلتر شده</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>مشتری</th><th>کد ملی</th><th>مبلغ</th><th>کش‌بک</th><th>وضعیت</th><th>اپراتور</th><th>تاریخ</th></tr></thead><tbody>
<?php foreach ($purchases as $row): ?><tr><td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td><td><?= e($row['national_code']) ?></td><td><?= e(money($row['amount'])) ?></td><td><?= e(money($row['cashback_amount'])) ?></td><td><?= ($row['status'] ?? 'active') === 'voided' ? 'ابطال' : 'فعال' ?></td><td><?= e($row['created_by_name']) ?></td><td><?= e($row['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="card mb-4 mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>گزارش سرویس‌ها</span>
        <a class="btn btn-sm btn-outline-success" href="<?= e(url('/reports/services-export?' . http_build_query(array_merge($filters, $serviceFilters)))) ?>">CSV سرویس‌ها</a>
    </div>
    <div class="card-body">
        <form class="row g-2 mb-3" method="get">
            <?php foreach ($filters as $k => $v): if (is_array($v)) continue; ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
            <div class="col-md-2"><input class="form-control ltr" type="date" name="service_date_from" value="<?= e($serviceFilters['date_from'] ?? '') ?>"></div>
            <div class="col-md-2"><input class="form-control ltr" type="date" name="service_date_to" value="<?= e($serviceFilters['date_to'] ?? '') ?>"></div>
            <div class="col-md-2"><input class="form-control" name="service_q" placeholder="جستجوی مشتری" value="<?= e($serviceFilters['q'] ?? '') ?>"></div>
            <div class="col-md-2"><input class="form-control ltr" name="service_contract_number" placeholder="شماره قرارداد" value="<?= e($serviceFilters['contract_number'] ?? '') ?>"></div>
            <div class="col-md-2">
                <select class="form-select" name="service_technician_id">
                    <option value="">همه تکنسین‌ها</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= e($user['id']) ?>" <?= (string)($serviceFilters['technician_id'] ?? '') === (string)$user['id'] ? 'selected' : '' ?>><?= e($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="service_payment_status">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="paid" <?= ($serviceFilters['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>پرداخت شده</option>
                    <option value="unpaid" <?= ($serviceFilters['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>پرداخت نشده</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="service_type">
                    <option value="">همه انواع</option>
                    <?php foreach ($serviceTypes as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($serviceFilters['service_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-secondary w-100">اعمال</button></div>
        </form>
        <div class="row g-3 mb-3">
            <?php foreach ([
                'تعداد سرویس' => (int) ($serviceSummary['service_count'] ?? 0),
                'جمع پرداختی' => money($serviceSummary['paid_total'] ?? 0),
                'پرداخت شده' => (int) ($serviceSummary['paid_count'] ?? 0),
                'پرداخت نشده' => (int) ($serviceSummary['unpaid_count'] ?? 0),
            ] as $label => $value): ?>
                <div class="col-md-3"><div class="small text-muted"><?= e($label) ?></div><div class="fw-bold"><?= e((string) $value) ?></div></div>
            <?php endforeach; ?>
        </div>
        <div class="table-responsive mb-3">
            <table class="table table-sm mb-0">
                <thead><tr><th>تکنسین</th><th>تعداد</th><th>جمع پرداختی</th><th>پرداخت شده</th><th>پرداخت نشده</th></tr></thead>
                <tbody>
                <?php foreach ($servicesByTechnician as $row): ?>
                    <tr>
                        <td><?= e($row['technician_name']) ?></td>
                        <td><?= e($row['service_count']) ?></td>
                        <td><?= e(money($row['paid_total'])) ?></td>
                        <td><?= e($row['paid_count']) ?></td>
                        <td><?= e($row['unpaid_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$servicesByTechnician): ?><tr><td colspan="5" class="text-muted">موردی نیست.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>مشتری</th><th>قرارداد</th><th>تکنسین</th><th>تاریخ</th><th>نوع</th><th>مبلغ</th><th>پرداخت</th><th>پیامک</th></tr></thead>
                <tbody>
                <?php foreach ($services as $row): ?>
                    <tr>
                        <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td class="ltr"><?= e($row['contract_number'] ?? '') ?></td>
                        <td><?= e($row['technician_name']) ?></td>
                        <td><?= e(Jalali::formatDate($row['service_date'])) ?></td>
                        <td><?= e(\App\Services\ServiceRecordService::serviceTypeLabel($row['service_type'])) ?></td>
                        <td><?= e(money($row['paid_amount'])) ?></td>
                        <td><?= $row['payment_status'] === 'paid' ? 'پرداخت شده' : 'پرداخت نشده' ?></td>
                        <td><?= e($row['sms_status'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="row g-3">
    <?php foreach (['تولدهای امروز' => $birthdaysToday, 'تولدهای هفته' => $birthdaysWeek, 'تولدهای ماه' => $birthdaysMonth] as $title => $rows): ?>
    <div class="col-lg-4"><div class="card"><div class="card-header bg-white"><?= e($title) ?></div><div class="card-body"><?php foreach ($rows as $row): ?><div class="d-flex justify-content-between border-bottom py-1"><span><?= e($row['first_name'] . ' ' . $row['last_name']) ?></span><span><?= e(Jalali::formatDate($row['birthday'])) ?></span></div><?php endforeach; if (!$rows): ?><span class="text-muted">موردی نیست.</span><?php endif; ?></div></div></div>
    <?php endforeach; ?>
</div>
