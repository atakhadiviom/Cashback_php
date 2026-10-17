<?php use App\Core\Jalali; use App\Services\ServiceRecordService; ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">جزئیات سرویس</h1>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= e(url('/services/edit?id=' . $service['id'])) ?>">ویرایش</a>
        <a class="btn btn-outline-primary" href="<?= e(url('/customers/show?id=' . $service['customer_id'])) ?>">پروفایل مشتری</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/services')) ?>">بازگشت به لیست</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">مشتری</div><div class="fw-bold"><a href="<?= e(url('/customers/show?id=' . $service['customer_id'])) ?>"><?= e($service['first_name'] . ' ' . $service['last_name']) ?></a></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">شماره قرارداد</div><div class="fw-bold ltr"><?= e($service['contract_number'] ?? '-') ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">موبایل</div><div class="fw-bold ltr"><?= e($service['phone_number'] ?? '-') ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">تکنسین</div><div class="fw-bold"><?= e($service['technician_name']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">تاریخ سرویس</div><div class="fw-bold"><?= e(Jalali::formatDate($service['service_date'])) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">نوع سرویس</div><div class="fw-bold"><?= e(ServiceRecordService::serviceTypeLabel($service['service_type'])) ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">مبلغ پرداختی</div><div class="fw-bold"><?= e(money($service['paid_amount'])) ?> ریال</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">وضعیت پرداخت</div><div class="fw-bold"><?= $service['payment_status'] === 'paid' ? 'پرداخت شده' : 'پرداخت نشده' ?></div></div></div></div>
</div>
<div class="card mb-4"><div class="card-header bg-white">توضیحات سرویس</div><div class="card-body"><?= nl2br(e($service['description'] ?? '-')) ?></div></div>
<div class="card"><div class="card-body row g-3">
    <div class="col-md-4"><div class="text-muted mb-1">ثبت‌کننده</div><div><?= e($service['created_by_name'] ?? '-') ?></div></div>
    <div class="col-md-4"><div class="text-muted mb-1">تاریخ ثبت</div><div class="ltr"><?= e(Jalali::formatDateTime($service['created_at'])) ?></div></div>
    <div class="col-md-4"><div class="text-muted mb-1">وضعیت پیامک</div><div><?= e($service['sms_status'] ?? '-') ?></div></div>
</div></div>
