<?php use App\Core\Csrf; ?>
<section class="login-page">
    <div class="login-visual">
        <span class="badge text-bg-light align-self-start mb-3">Cashback Admin</span>
        <h2><?= e(config_value('app.company_name', '')) ?></h2>
        <p>مدیریت مشتریان، خریدها، کیف پول و پیامک‌ها در یک داشبورد سریع، امن و آماده برای هاست اشتراکی.</p>
    </div>
    <div class="login-panel">
        <div class="card login-card">
            <div class="card-body p-4 p-md-5">
                <span class="brand-icon"><i class="bi bi-wallet2"></i></span>
                <h1 class="h4 fw-bold mb-2"><?= e(config_value('app.name')) ?></h1>
                <p class="text-muted mb-4">برای ادامه وارد حساب کاربری خود شوید.</p>
                <form method="post" action="<?= e(url('/login')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                    <div class="mb-3">
                        <label class="form-label">نام کاربری</label>
                        <input class="form-control ltr" name="username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">رمز عبور</label>
                        <input class="form-control ltr" type="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right"></i> ورود به داشبورد</button>
                </form>
            </div>
        </div>
    </div>
</section>
