<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">ورود مشتریان از CSV / Excel</h1>
<p class="text-muted">ستون‌ها: نام، نام خانوادگی (اختیاری)، کد ملی یا شناسه ملی شرکت (اختیاری)، موبایل، تاریخ تولد (شمسی اختیاری)</p>
<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/admin/customers/import')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <div class="mb-3"><input type="file" name="import_file" accept=".csv,.xlsx" class="form-control" required></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="preview_only" value="1" id="preview"><label for="preview">فقط پیش‌نمایش</label></div>
    <?php if (!empty($errors['file'])): ?><div class="text-danger"><?= e($errors['file']) ?></div><?php endif; ?>
    <button class="btn btn-primary">ارسال</button>
</form>
<?php if ($preview): ?>
<h3 class="h5 mt-4">پیش‌نمایش</h3>
<pre class="small"><?php foreach ($preview as $row): ?>ردیف <?= e($row['row']) ?>: <?= e(json_encode($row['data'], JSON_UNESCAPED_UNICODE)) ?>

<?php endforeach; ?></pre>
<?php endif; ?>
</div></div>
