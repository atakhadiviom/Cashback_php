<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\ApiKeyRepository;
use App\Services\ApiAuthService;

final class ApiKeyController
{
    public function index(): void
    {
        View::render('admin/api_keys', [
            'keys' => (new ApiKeyRepository())->all(),
            'newKey' => $_SESSION['flash_new_api_key'] ?? null,
        ]);
        unset($_SESSION['flash_new_api_key']);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $generated = ApiAuthService::generateKey();
        (new ApiKeyRepository())->create(
            trim((string) ($_POST['name'] ?? 'API Key')),
            $generated['hash'],
            $generated['prefix'],
            (int) Auth::id()
        );
        $_SESSION['flash_new_api_key'] = $generated['plain'];
        Flash::set('success', 'کلید API ایجاد شد. آن را فقط یک‌بار کپی کنید.');
        \redirect('/admin/api-keys');
    }

    public function deactivate(): void
    {
        Csrf::requireValid();
        (new ApiKeyRepository())->deactivate((int) ($_POST['id'] ?? 0));
        Flash::set('success', 'کلید API غیرفعال شد.');
        \redirect('/admin/api-keys');
    }
}
