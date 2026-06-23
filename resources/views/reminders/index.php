<?php
use App\Core\Csrf;
use App\Services\FollowupService;
use App\Services\ReminderService;
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">یادآوری‌های پیگیری</h1>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary" href="<?= e(url('/reminders?scope=today')) ?>">امروز</a>
        <a class="btn btn-outline-danger" href="<?= e(url('/reminders?scope=overdue')) ?>">معوق</a>
        <a class="btn btn-primary" href="<?= e(url('/followups/create')) ?>">ثبت پیگیری</a>
    </div>
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
            <select class="form-select" name="status">
                <option value="">همه وضعیت‌ها</option>
                <?php foreach (['pending', 'seen', 'done', 'missed'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e(ReminderService::statusLabel($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-secondary w-100">جستجو</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>زمان یادآوری</th><th>مشتری</th><th>شرکت</th><th>اپراتور</th><th>وضعیت یادآوری</th><th>وضعیت فروش</th><th>خلاصه مکالمه</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reminders as $reminder): ?>
        <tr>
            <td class="ltr"><?= e($reminder['remind_at']) ?></td>
            <td><?= e($reminder['first_name'] . ' ' . $reminder['last_name']) ?></td>
            <td><?= e($reminder['company'] ?? '') ?></td>
            <td><?= e($reminder['operator_name']) ?></td>
            <td><?= e(ReminderService::statusLabel($reminder['status'])) ?></td>
            <td><?= e(FollowupService::salesStatusLabel($reminder['sales_status'])) ?></td>
            <td><?= e(mb_substr($reminder['conversation_notes'], 0, 80)) ?><?= mb_strlen($reminder['conversation_notes']) > 80 ? '...' : '' ?></td>
            <td class="text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/followups/show?id=' . $reminder['followup_id'])) ?>">پیگیری</a>
                <?php if ($reminder['status'] !== 'done'): ?>
                    <form class="d-inline" method="post" action="<?= e(url('/reminders/mark-seen')) ?>">
                        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                        <input type="hidden" name="id" value="<?= e($reminder['id']) ?>">
                        <button class="btn btn-sm btn-outline-secondary">دیده شد</button>
                    </form>
                    <form class="d-inline" method="post" action="<?= e(url('/reminders/mark-done')) ?>">
                        <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                        <input type="hidden" name="id" value="<?= e($reminder['id']) ?>">
                        <button class="btn btn-sm btn-success">انجام شد</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$reminders): ?><tr><td colspan="8" class="text-muted">یادآوری‌ای یافت نشد.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div>
