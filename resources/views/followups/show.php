<?php use App\Core\Jalali; use App\Services\FollowupService; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">جزئیات پیگیری فروش</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('/followups/edit?id=' . $followup['id'])) ?>">ویرایش</a>
        <a class="btn btn-outline-primary" href="<?= e(url('/customers/show?id=' . $followup['customer_id'])) ?>">پروفایل مشتری</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">مشتری</div><div class="fw-bold"><?= e($followup['first_name'] . ' ' . $followup['last_name']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">شرکت</div><div class="fw-bold"><?= e($followup['company'] ?? '-') ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">اپراتور</div><div class="fw-bold"><?= e($followup['operator_name']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">وضعیت</div><div class="fw-bold"><?= e(FollowupService::salesStatusLabel($followup['sales_status'])) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">تاریخ پیگیری</div><div class="ltr"><?= e($followup['followup_date']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">مبلغ پیش فاکتور</div><div><?= $followup['pre_invoice_amount'] !== null ? e(money($followup['pre_invoice_amount'])) . ' ریال' : '-' ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">مبلغ فاکتور</div><div><?= $followup['invoice_amount'] !== null ? e(money($followup['invoice_amount'])) . ' ریال' : '-' ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">تماس بعدی</div><div><?= e(Jalali::formatDate($followup['next_contact_date'] ?? null)) ?> <?= e($followup['reminder_time'] ? substr($followup['reminder_time'], 0, 5) : '') ?></div></div></div></div>
</div>
<div class="card mb-4"><div class="card-header bg-white">توضیحات کامل مکالمه</div><div class="card-body"><?= nl2br(e($followup['conversation_notes'])) ?></div></div>
<?php if (!empty($followup['lost_reason']) || !empty($followup['attachment_path']) || !empty($followup['purchase_id'])): ?>
<div class="card"><div class="card-body row g-3">
    <div class="col-md-4"><div class="text-muted mb-1">دلیل لغو</div><div><?= e($followup['lost_reason'] ?? '-') ?></div></div>
    <div class="col-md-4"><div class="text-muted mb-1">فایل ضمیمه</div><div class="ltr"><?= e($followup['attachment_path'] ?? '-') ?></div></div>
    <div class="col-md-4"><div class="text-muted mb-1">خرید ثبت شده</div><div><?= !empty($followup['purchase_id']) ? 'خرید #' . e($followup['purchase_id']) : '-' ?></div></div>
</div></div>
<?php endif; ?>
