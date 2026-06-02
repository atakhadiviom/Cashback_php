<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
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
        $user = (new UserRepository())->findByUsername($username);
        if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
            Flash::set('danger', 'نام کاربری یا رمز عبور نادرست است.');
            \redirect('/login');
        }
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
