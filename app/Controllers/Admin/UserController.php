<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;

final class UserController
{
    public function index(): void
    {
        View::render('admin/users/index', ['users' => (new UserRepository())->activeOperatorsAndAdmins()]);
    }

    public function create(): void
    {
        View::render('admin/users/create', ['user' => [], 'errors' => [], 'users' => (new UserRepository())->activeOperatorsAndAdmins()]);
    }

    public function store(): void
    {
        Csrf::requireValid();
        $errors = $this->validate($_POST, true);
        if ($errors) {
            View::render('admin/users/create', ['user' => $_POST, 'errors' => $errors, 'users' => (new UserRepository())->activeOperatorsAndAdmins()]);
            return;
        }
        $role = $_POST['role'] === 'admin' ? 'admin' : 'operator';
        $now = \current_datetime();
        (new UserRepository())->create([
            'name' => trim((string) $_POST['name']),
            'username' => trim((string) $_POST['username']),
            'password_hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
            'role' => $role,
            'permissions' => $this->permissionsJson($role, $_POST),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        (new ActivityLogger())->log('operator_create', 'کاربر جدید ایجاد شد: ' . trim((string) $_POST['username']));
        Flash::set('success', 'کاربر ایجاد شد.');
        \redirect('/admin/users');
    }

    public function edit(): void
    {
        $user = (new UserRepository())->find((int) ($_GET['id'] ?? 0));
        if (!$user) {
            Flash::set('danger', 'کاربر یافت نشد.');
            \redirect('/admin/users');
        }
        if (!empty($user['permissions']) && is_string($user['permissions'])) {
            $user['permissions'] = json_decode($user['permissions'], true);
        }
        View::render('admin/users/edit', ['user' => $user, 'errors' => [], 'users' => (new UserRepository())->activeOperatorsAndAdmins()]);
    }

    public function update(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $errors = $this->validate($_POST, false, $id);
        if ($errors) {
            View::render('admin/users/edit', ['user' => $_POST, 'errors' => $errors, 'users' => (new UserRepository())->activeOperatorsAndAdmins()]);
            return;
        }
        $role = $_POST['role'] === 'admin' ? 'admin' : 'operator';
        $data = [
            'name' => trim((string) $_POST['name']),
            'username' => trim((string) $_POST['username']),
            'password_hash' => trim((string) ($_POST['password'] ?? '')) !== '' ? password_hash((string) $_POST['password'], PASSWORD_DEFAULT) : '',
            'role' => $role,
            'permissions' => $this->permissionsJson($role, $_POST),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => \current_datetime(),
        ];
        (new UserRepository())->update($id, $data);
        (new ActivityLogger())->log('operator_edit', 'کاربر ویرایش شد: ' . $data['username']);
        Flash::set('success', 'کاربر ویرایش شد.');
        \redirect('/admin/users');
    }

    private function permissionsJson(string $role, array $post): string
    {
        if ($role === 'admin') {
            return UserRepository::defaultPermissionsJson('admin');
        }
        $keys = ['purchase', 'reduce_wallet', 'export', 'void_purchase', 'manage_settings', 'import_customers', 'manage_api', 'manage_loyalty'];
        $perms = [];
        foreach ($keys as $key) {
            $perms[$key] = isset($post['perm_' . $key]);
        }
        $scope = in_array((string) ($post['data_access_scope'] ?? 'self'), ['self', 'selected', 'all'], true)
            ? (string) $post['data_access_scope']
            : 'self';
        $perms['data_access_scope'] = $scope;
        $perms['data_access_user_ids'] = [];
        if ($scope === 'selected') {
            foreach ((array) ($post['data_access_user_ids'] ?? []) as $userId) {
                $userId = (int) $userId;
                if ($userId > 0) {
                    $perms['data_access_user_ids'][] = $userId;
                }
            }
            $perms['data_access_user_ids'] = array_values(array_unique($perms['data_access_user_ids']));
        }
        $perms['data_access_can_modify_others'] = isset($post['data_access_can_modify_others']);
        return json_encode($perms, JSON_UNESCAPED_UNICODE);
    }

    private function validate(array $data, bool $requirePassword, ?int $exceptId = null): array
    {
        $errors = [];
        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = 'نام الزامی است.';
        }
        if (trim((string) ($data['username'] ?? '')) === '') {
            $errors['username'] = 'نام کاربری الزامی است.';
        } elseif ((new UserRepository())->usernameExists(trim((string) $data['username']), $exceptId)) {
            $errors['username'] = 'این نام کاربری قبلاً ثبت شده است.';
        }
        if ($requirePassword && strlen((string) ($data['password'] ?? '')) < 8) {
            $errors['password'] = 'رمز عبور حداقل باید ۸ کاراکتر باشد.';
        }
        return $errors;
    }
}
