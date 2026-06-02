<?php

declare(strict_types=1);

use App\Controllers\Admin\ActivityLogController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\UserController;
use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\PurchaseController;
use App\Controllers\ReportController;
use App\Controllers\SmsController;
use App\Controllers\WalletController;
use App\Core\Router;

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'login'], false);
$router->post('/login', [AuthController::class, 'authenticate'], false);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/customers', [CustomerController::class, 'index']);
$router->get('/customers/create', [CustomerController::class, 'create']);
$router->post('/customers/create', [CustomerController::class, 'store']);
$router->get('/customers/edit', [CustomerController::class, 'edit']);
$router->post('/customers/edit', [CustomerController::class, 'update']);
$router->get('/customers/show', [CustomerController::class, 'show']);
$router->get('/customers/export', [CustomerController::class, 'export']);

$router->get('/purchases/create', [PurchaseController::class, 'create']);
$router->post('/purchases/create', [PurchaseController::class, 'store']);
$router->get('/wallet/reduce', [WalletController::class, 'reduce']);
$router->post('/wallet/reduce', [WalletController::class, 'store']);

$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/export', [ReportController::class, 'export']);
$router->get('/sms/logs', [SmsController::class, 'logs']);

$router->get('/admin/users', [UserController::class, 'index'], true, 'admin');
$router->get('/admin/users/create', [UserController::class, 'create'], true, 'admin');
$router->post('/admin/users/create', [UserController::class, 'store'], true, 'admin');
$router->get('/admin/users/edit', [UserController::class, 'edit'], true, 'admin');
$router->post('/admin/users/edit', [UserController::class, 'update'], true, 'admin');
$router->get('/admin/activity-logs', [ActivityLogController::class, 'index'], true, 'admin');
$router->get('/admin/sms-settings', [SettingsController::class, 'edit'], true, 'admin');
$router->post('/admin/sms-settings', [SettingsController::class, 'update'], true, 'admin');

return $router;
