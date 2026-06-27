<?php use App\Core\Jalali; use App\Services\DueDateService; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">مدیریت سررسیدها</h1>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= e(url('/due-dates/export?' . http_build_query($filters))) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/due-dates/print?' . http_build_query($filters))) ?>" target="_blank"><i class="bi bi-printer"></i> چاپ</a>
        <a class="btn btn-primary" href="<?= e(url('/due-dates/create')) ?>">ثبت سررسید</a>
    </div>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php
    $scopes = [
        '' => 'همه',
        'today' => 'امروز',
        'next_7_days' => '۷ روز آینده',
        'overdue' => 'معوق',
        'daily' => 'گزارش روزانه',
        'weekly' => 'گزارش هفتگی',
        'monthly' => 'گزارش ماهانه',
    ];
    $currentScope = (string) ($filters['scope'] ?? '');
    foreach ($scopes as $value => $label):
        $query = $filters;
        if ($value === '') {
            unset($query['scope']);
        } else {
            $query['scope'] = $value;
        }
    ?>
        <a class="btn btn-sm <?= $currentScope === $value ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(url('/due-dates?' . http_build_query($query))) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>
<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get">
        <?php if ($currentScope !== ''): ?><input type="hidden" name="scope" value="<?= e($currentScope) ?>"><?php endif; ?>
        <div class="col-md-2"><input class="form-control" name="q" placeholder="نام مشتری" value="<?= e($filters['q'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="reference_number" placeholder="شماره چک/فاکتور" value="<?= e($filters['reference_number'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" name="amount" placeholder="مبلغ" value="<?= e($filters['amount'] ?? '') ?>" inputmode="numeric" data-money></div>
        <div class="col-md-2"><input class="form-control ltr" type="text" name="due_date_from" data-jdp data-jdp-only-date placeholder="از تاریخ" value="<?= e($filters['due_date_from'] ?? '') ?>" autocomplete="off"></div>
        <div class="col-md-2"><input class="form-control ltr" type="text" name="due_date_to" data-jdp data-jdp-only-date placeholder="تا تاریخ" value="<?= e($filters['due_date_to'] ?? '') ?>" autocomplete="off"></div>
        <div class="col-md-2">
            <select class="form-select" name="due_type">
                <option value="">همه انواع</option>
                <?php foreach ($dueTypes as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['due_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">همه وضعیت‌ها</option>
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="operator_id">
                <option value="">همه اپراتورها</option>
                <?php foreach ($operators as $operator): ?>
                    <option value="<?= e($operator['id']) ?>" <?= (string)($filters['operator_id'] ?? '') === (string)$operator['id'] ? 'selected' : '' ?>><?= e($operator['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">جستجو</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>مشتری</th><th>نوع</th><th>مبلغ</th><th>تاریخ سررسید</th><th>شماره مرجع</th><th>وضعیت</th><th>اپراتور</th><th>تاریخ ثبت</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($dueDates as $row): ?>
        <tr>
            <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= e(DueDateService::dueTypeLabel($row['due_type'])) ?></td>
            <td><?= e(money($row['amount'])) ?> ریال</td>
            <td><?= e(Jalali::formatDate($row['due_date'])) ?></td>
            <td class="ltr"><?= e($row['reference_number'] ?? '-') ?></td>
            <td><?= e(DueDateService::statusLabel($row['status'])) ?></td>
            <td><?= e($row['operator_name']) ?></td>
            <td><?= e(Jalali::formatDate(substr($row['created_at'], 0, 10))) ?></td>
            <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/due-dates/edit?id=' . $row['id'])) ?>">ویرایش</a>
                <form method="post" action="<?= e(url('/due-dates/delete')) ?>" class="d-inline" onsubmit="return confirm('سررسید حذف شود؟');">
                    <input type="hidden" name="_csrf" value="<?= e(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$dueDates): ?><tr><td colspan="9" class="text-muted">موردی یافت نشد.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div>
<link href="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.css')) ?>" rel="stylesheet">
<script src="<?= e(asset_url('vendor/jalalidatepicker/jalalidatepicker.min.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof jalaliDatepicker !== 'undefined') {
        jalaliDatepicker.startWatch({ separatorChars: { date: '/' }, persianDigits: true, autoShow: true, autoHide: true });
    }
});
</script>
