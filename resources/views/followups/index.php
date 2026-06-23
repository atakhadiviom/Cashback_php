<?php use App\Core\Jalali; use App\Services\FollowupService; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">پیگیری‌های فروش</h1>
    <a class="btn btn-primary" href="<?= e(url('/followups/create')) ?>">ثبت پیگیری</a>
</div>
<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get">
        <div class="col-md-2"><input class="form-control ltr" type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control ltr" type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control" name="q" placeholder="جستجو" value="<?= e($filters['q'] ?? '') ?>"></div>
        <div class="col-md-2">
            <select class="form-select" name="operator_id">
                <option value="">همه اپراتورها</option>
                <?php foreach ($operators as $operator): ?>
                    <option value="<?= e($operator['id']) ?>" <?= (string)($filters['operator_id'] ?? '') === (string)$operator['id'] ? 'selected' : '' ?>><?= e($operator['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="sales_status">
                <option value="">همه وضعیت‌ها</option>
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['sales_status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">جستجو</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>مشتری</th><th>شرکت</th><th>اپراتور</th><th>تاریخ</th><th>وضعیت</th><th>پیش‌فاکتور</th><th>فاکتور</th><th>تماس بعدی</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($followups as $followup): ?>
        <tr>
            <td><?= e($followup['first_name'] . ' ' . $followup['last_name']) ?></td>
            <td><?= e($followup['company'] ?? '') ?></td>
            <td><?= e($followup['operator_name']) ?></td>
            <td><?= e($followup['followup_date']) ?></td>
            <td><?= e(FollowupService::salesStatusLabel($followup['sales_status'])) ?></td>
            <td><?= $followup['pre_invoice_amount'] !== null ? e(money($followup['pre_invoice_amount'])) . ' ریال' : '-' ?></td>
            <td><?= $followup['invoice_amount'] !== null ? e(money($followup['invoice_amount'])) . ' ریال' : '-' ?></td>
            <td><?= e(Jalali::formatDate($followup['next_contact_date'] ?? null)) ?> <?= e($followup['reminder_time'] ? substr($followup['reminder_time'], 0, 5) : '') ?></td>
            <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/followups/show?id=' . $followup['id'])) ?>">مشاهده</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/followups/edit?id=' . $followup['id'])) ?>">ویرایش</a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$followups): ?><tr><td colspan="9" class="text-muted">موردی یافت نشد.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div>
