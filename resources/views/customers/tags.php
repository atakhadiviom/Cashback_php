<?php

use App\Core\Csrf;

$tags = $tags ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="text-muted small">مشتریان</div>
        <h1 class="h3 mb-0">برچسب‌های مشتری</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('/customers')) ?>">لیست مشتریان</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/club/segments')) ?>">بخش‌بندی RFM</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white fw-bold">برچسب جدید</div>
            <div class="card-body">
                <form method="post" action="<?= e(url('/customers/tags')) ?>" class="d-flex gap-2">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                    <input class="form-control" name="name" placeholder="مثلاً عمده‌فروش، VIP، معرف" required>
                    <button class="btn btn-primary">افزودن</button>
                </form>
                <div class="form-text">برچسب‌ها کنار بخش‌بندی RFM برای دسته‌بندی دستی مشتریان استفاده می‌شوند.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white fw-bold">برچسب‌های موجود</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead><tr><th>برچسب</th><th>تعداد مشتری</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td>
                                <form method="post" action="<?= e(url('/customers/tags/edit')) ?>" class="d-flex gap-1">
                                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($tag['id']) ?>">
                                    <input class="form-control form-control-sm" name="name" value="<?= e((string) $tag['name']) ?>">
                                    <button class="btn btn-sm btn-outline-primary">ذخیره</button>
                                </form>
                            </td>
                            <td class="ltr"><?= e(number_format((int) $tag['customers'])) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/customers?tag_id=' . (int) $tag['id'])) ?>">مشتریان</a>
                                <form method="post" action="<?= e(url('/customers/tags/delete')) ?>" class="d-inline" data-confirm="این برچسب حذف شود؟">
                                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                                    <input type="hidden" name="id" value="<?= e($tag['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tags): ?><tr><td colspan="3" class="text-muted">برچسبی ثبت نشده است.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
