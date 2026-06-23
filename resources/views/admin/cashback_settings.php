<?php use App\Core\Csrf; ?>
<h1 class="h3 mb-4">تنظیمات کش‌بک و کیف پول</h1>
<div class="card"><div class="card-body">
<form method="post" action="<?= e(url('/admin/cashback-settings')) ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
    <div class="col-md-4"><label class="form-label">درصد کش‌بک پایه</label><input class="form-control ltr" name="cashback_percent" value="<?= e($settings['cashback_percent'] ?? '5') ?>" required></div>
    <div class="col-md-4"><label class="form-label">حداقل مبلغ خرید (ریال)</label><input class="form-control ltr" name="min_purchase_amount" value="<?= e(money_input_value($settings['min_purchase_amount'] ?? null)) ?>" inputmode="numeric" data-money></div>
    <div class="col-md-4"><label class="form-label">سقف کش‌بک هر خرید</label><input class="form-control ltr" name="max_cashback_per_purchase" value="<?= e(money_input_value($settings['max_cashback_per_purchase'] ?? null, '')) ?>" inputmode="numeric" data-money placeholder="اختیاری"></div>
    <div class="col-md-4"><label class="form-label">حداقل کسر از کیف پول</label><input class="form-control ltr" name="min_redemption_amount" value="<?= e(money_input_value($settings['min_redemption_amount'] ?? null, '')) ?>" inputmode="numeric" data-money placeholder="اختیاری"></div>
    <div class="col-md-4"><label class="form-label">حداکثر درصد استفاده از کیف پول</label><input class="form-control ltr" name="max_redemption_percent_of_purchase" value="<?= e($settings['max_redemption_percent_of_purchase'] ?? '') ?>" placeholder="مثلاً 50"></div>
    <div class="col-md-4"><label class="form-label">آستانه کسر بزرگ (فقط مدیر)</label><input class="form-control ltr" name="large_redemption_threshold" value="<?= e(money_input_value($settings['large_redemption_threshold'] ?? null, '')) ?>" inputmode="numeric" data-money placeholder="اختیاری"></div>
    <div class="col-md-4"><label class="form-label">پاداش تولد (ریال)</label><input class="form-control ltr" name="birthday_bonus_amount" value="<?= e(money_input_value($settings['birthday_bonus_amount'] ?? null)) ?>" inputmode="numeric" data-money></div>
    <div class="col-md-4"><label class="form-label">پاداش معرفی (ریال)</label><input class="form-control ltr" name="referral_bonus_amount" value="<?= e(money_input_value($settings['referral_bonus_amount'] ?? null)) ?>" inputmode="numeric" data-money></div>
    <div class="col-md-4"><label class="form-label">پنجره هشدار خرید تکراری (دقیقه)</label><input class="form-control ltr" name="duplicate_purchase_window_minutes" value="<?= e($settings['duplicate_purchase_window_minutes'] ?? '5') ?>"></div>
    <div class="col-12 mt-3">
        <h5 class="mb-2">منوهای فعال (قابلیت فعال/غیرفعال کردن منوها)</h5>
        <p class="text-muted small mb-2"><strong>قانون:</strong> فقط منوهایی که اینجا تیک دارند، در سایدبار نمایش داده می‌شوند.</p>
        <p class="text-muted small mb-2">برای پنهان کردن یک منو، تیک آن را بردارید و دکمه «ذخیره تنظیمات» را بزنید. بعد از ذخیره، حتماً صفحه را <strong>کامل رفرش</strong> کنید (Ctrl + Shift + R).</p>

        <div class="row g-2">
            <?php
            $menuOptions = [
                'dashboard' => 'داشبورد',
                'customers' => 'مشتریان',
                'add_customer' => 'افزودن مشتری',
                'purchases' => 'ثبت خرید',
                'services' => 'سرویس‌ها',
                'followups' => 'پیگیری فروش',
                'reminders' => 'یادآوری‌ها',
                'reports' => 'گزارش‌ها',
                'sms_logs' => 'لاگ پیامک',
            ];
            $enabledMenus = $settings['enabled_menus'] ?? null;
            $isEnabled = function($key) use ($enabledMenus) {
                if ($enabledMenus === null || $enabledMenus === []) return true;
                return in_array($key, $enabledMenus, true);
            };
            foreach ($menuOptions as $key => $label):
            ?>
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="menu_<?= e($key) ?>" id="menu_<?= e($key) ?>" value="1" <?= $isEnabled($key) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="menu_<?= e($key) ?>"><?= e($label) ?></label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php
        // Detect if the enabled_menus column exists (migration 017)
        $columnExists = false;
        try {
            $pdo = \App\Core\Database::pdo();
            $col = $pdo->query("SHOW COLUMNS FROM cashback_settings LIKE 'enabled_menus'")->fetch();
            $columnExists = (bool)$col;
        } catch (\Throwable $e) {
            $columnExists = false;
        }

        if (!$columnExists):
        ?>
            <div class="alert alert-warning mt-3 py-2 small mb-0">
                <strong>⚠️ قابلیت کنترل منوها هنوز فعال نشده است.</strong><br>
                این ویژگی نیاز به اجرای مایگریشن دارد.<br>
                روی سرور دستور زیر را اجرا کنید:
                <code class="d-block mt-1">php database/migrate.php</code>
                سپس صفحه را رفرش کنید و دوباره تنظیمات را ذخیره کنید.
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 mt-3"><button class="btn btn-primary">ذخیره تنظیمات</button></div>
</form>
</div></div>
