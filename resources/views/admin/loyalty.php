<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">سطوح مشتری و پروموشن‌ها</h1>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header">سطوح (بر اساس مجموع خرید)</div>
        <table class="table mb-0"><thead><tr><th>نام</th><th>حداقل خرید</th><th>حداکثر خرید</th><th>درصد</th></tr></thead><tbody>
        <?php foreach ($tiers as $tier): ?><tr><td><?= e($tier['name']) ?></td><td><?= e(money($tier['min_lifetime_spend'])) ?></td><td><?= $tier['max_lifetime_spend'] ? e(money($tier['max_lifetime_spend'])) : '∞' ?></td><td><?= e($tier['cashback_percent']) ?>٪</td></tr><?php endforeach; ?>
        </tbody></table>
        <div class="card-body border-top">
        <form method="post" action="<?= e(url('/admin/loyalty/tiers')) ?>" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <div class="col-3"><input class="form-control" name="name" placeholder="نام سطح" required></div>
            <div class="col-3"><input class="form-control ltr" name="min_lifetime_spend" placeholder="حداقل خرید" required></div>
            <div class="col-3"><input class="form-control ltr" name="max_lifetime_spend" placeholder="حداکثر خرید (خالی = ∞)"></div>
            <div class="col-2"><input class="form-control ltr" name="cashback_percent" placeholder="درصد" required></div>
            <div class="col-1"><button class="btn btn-primary w-100">+</button></div>
        </form>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header">پروموشن‌ها</div>
        <table class="table mb-0"><thead><tr><th>نام</th><th>بونوس٪</th><th>فعال</th></tr></thead><tbody>
        <?php foreach ($promotions as $p): ?><tr><td><?= e($p['name']) ?></td><td><?= e($p['percent_bonus']) ?></td><td><?= (int)$p['is_active'] ? 'بله' : 'خیر' ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <div class="card-body border-top">
        <form method="post" action="<?= e(url('/admin/loyalty/promotions')) ?>" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
            <div class="col-12"><input class="form-control" name="name" placeholder="نام" required></div>
            <div class="col-4"><input class="form-control ltr" name="percent_bonus" placeholder="بونوس ٪"></div>
            <div class="col-4"><input class="form-control ltr" type="datetime-local" name="starts_at" required></div>
            <div class="col-4"><input class="form-control ltr" type="datetime-local" name="ends_at" required></div>
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="promo_active" checked><label for="promo_active">فعال</label></div></div>
            <div class="col-12"><button class="btn btn-primary">افزودن پروموشن</button></div>
        </form>
        </div></div>
    </div>
</div>
