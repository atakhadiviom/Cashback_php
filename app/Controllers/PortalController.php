<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Services\PortalService;

final class PortalController
{
    public function index(): void
    {
        if (Auth::portalCustomerId()) {
            \redirect('/portal/dashboard');
        }
        View::render('portal/index', ['errors' => []], 'portal');
    }

    public function requestOtp(): void
    {
        Csrf::requireValid();
        $result = (new PortalService())->requestOtp((string) ($_POST['phone'] ?? ''));
        if (!$result['ok']) {
            View::render('portal/index', ['errors' => $result['errors'], 'phone' => $_POST['phone'] ?? ''], 'portal');
            return;
        }
        Flash::set('success', 'کد تأیید ارسال شد.');
        \redirect('/portal/verify?phone=' . urlencode(\normalize_digits((string) ($_POST['phone'] ?? ''))));
    }

    public function verifyForm(): void
    {
        View::render('portal/verify', [
            'phone' => \normalize_digits((string) ($_GET['phone'] ?? '')),
            'errors' => [],
        ], 'portal');
    }

    public function verify(): void
    {
        Csrf::requireValid();
        $phone = (string) ($_POST['phone'] ?? '');
        $result = (new PortalService())->verifyOtp($phone, (string) ($_POST['code'] ?? ''));
        if (!$result['ok']) {
            View::render('portal/verify', ['phone' => $phone, 'errors' => $result['errors']], 'portal');
            return;
        }
        \redirect('/portal/dashboard');
    }

    public function dashboard(): void
    {
        $data = (new PortalService())->dashboard();
        if (!$data) {
            \redirect('/portal');
        }
        View::render('portal/dashboard', $data, 'portal');
    }

    public function logout(): void
    {
        Csrf::requireValid();
        Auth::logoutPortal();
        \redirect('/portal');
    }
}
