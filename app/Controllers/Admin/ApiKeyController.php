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
            (int) Auth::id(),
            $this->scopesFromPost()
        );
        $_SESSION['flash_new_api_key'] = $generated['plain'];
        Flash::set('success', 'کلید API ایجاد شد. آن را فقط یک‌بار کپی کنید.');
        \redirect('/admin/api-keys');
    }

    public function updateScopes(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !(new ApiKeyRepository())->updateScopes($id, $this->scopesFromPost())) {
            Flash::set('danger', 'ذخیره دسترسی کلید ممکن نشد.');
        } else {
            Flash::set('success', 'دسترسی کلید API ذخیره شد.');
        }
        \redirect('/admin/api-keys');
    }

    /**
     * Checkbox list → "read", "write" or "read,write". Empty means read-only.
     */
    private function scopesFromPost(): string
    {
        $allowed = ['read', 'write'];
        $list = [];
        foreach ((array) ($_POST['scopes'] ?? []) as $scope) {
            $scope = strtolower(trim((string) $scope));
            if (in_array($scope, $allowed, true)) {
                $list[] = $scope;
            }
        }
        $list = array_values(array_unique($list));

        return $list === [] ? 'read' : implode(',', $list);
    }

    public function deactivate(): void
    {
        Csrf::requireValid();
        (new ApiKeyRepository())->deactivate((int) ($_POST['id'] ?? 0));
        Flash::set('success', 'کلید API غیرفعال شد.');
        \redirect('/admin/api-keys');
    }
}
