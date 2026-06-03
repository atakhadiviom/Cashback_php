<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;

final class AuthController
{
    public function login(): void
    {
        View::render('auth/login');
    }

    public function authenticate(): void
    {
        Csrf::requireValid();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $attempts = new LoginAttemptRepository();
        $max = (int) \config_value('security.login_max_attempts', 5);
        $lockout = (int) \config_value('security.login_lockout_minutes', 15);

        if ($attempts->failedCountSince($username, $lockout) >= $max) {
            Flash::set('danger', 'تعداد تلاش‌های ناموفق زیاد است. لطفاً ' . $lockout . ' دقیقه بعد تلاش کنید.');
            \redirect('/login');
        }

        $user = (new UserRepository())->findByUsername($username);
        if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
            $attempts->record($username, $ip, false);
            Flash::set('danger', 'نام کاربری یا رمز عبور نادرست است.');
            \redirect('/login');
        }

        $attempts->record($username, $ip, true);
        Auth::login($user);
        (new ActivityLogger())->log('login', 'ورود به سیستم', null, (int) $user['id']);
        \redirect('/dashboard');
    }

    public function logout(): void
    {
        Csrf::requireValid();
        (new ActivityLogger())->log('logout', 'خروج از سیستم');
        Auth::logout();
        \redirect('/login');
    }
}
