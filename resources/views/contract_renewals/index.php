<?php use App\Core\Jalali; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">تمدید قراردادها</h1>
    <a class="btn btn-primary" href="<?= e(url('/contract-renewals/create')) ?>">ثبت تمدید قرارداد</a>
</div>
<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get">
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="از تاریخ" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" type="text" data-jdp data-jdp-only-date autocomplete="off" placeholder="تا تاریخ" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
        <div class="col-md-4"><input class="form-control" name="q" placeholder="نام مشتری / موبایل / شماره قرارداد" value="<?= e($filters['q'] ?? '') ?>"></div>
        <div class="col-md-2">
            <select class="form-select" name="operator_id">
                <option value="">همه اپراتورها</option>
                <?php foreach ($operators as $op): ?>
                    <option value="<?= e($op['id']) ?>" <?= (string) ($filters['operator_id'] ?? '') === (string) $op['id'] ? 'selected' : '' ?>><?= e($op['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">جستجو</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>مشتری</th><th>قرارداد</th><th>پایان قبلی</th><th>شروع جدید</th><th>پایان جدید</th><th>اپراتور</th><th>تاریخ ثبت</th><th>یادداشت</th></tr></thead>
    <tbody>
    <?php foreach ($renewals as $row): ?>
        <tr>
            <td><a href="<?= e(url('/customers/show?id=' . $row['customer_id'])) ?>"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></a></td>
            <td class="ltr"><?= e($row['contract_number'] ?? '') ?></td>
            <td><?= e(Jalali::formatDate($row['previous_ends_at'] ?? null)) ?></td>
            <td><?= e(Jalali::formatDate($row['new_starts_at'] ?? null)) ?></td>
            <td><?= e(Jalali::formatDate($row['new_ends_at'])) ?></td>
            <td><?= e($row['operator_name']) ?></td>
            <td><?= e(Jalali::formatDateTime($row['created_at'])) ?></td>
            <td><?= e($row['note'] ?? '-') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$renewals): ?><tr><td colspan="8" class="text-muted">تمدیدی ثبت نشده است.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div>
