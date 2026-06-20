<?php

declare(strict_types=1);

use App\Controllers\Admin\ActivityLogController;
use App\Controllers\Admin\AppUpdateController;
use App\Controllers\Admin\ApiKeyController;
use App\Controllers\Admin\CashbackSettingsController;
use App\Controllers\Admin\CronController as AdminCronController;
use App\Controllers\Admin\CustomerImportController;
use App\Controllers\Admin\LoyaltyController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\UserController;
use App\Controllers\AuthController;
use App\Controllers\CronController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\PortalController;
use App\Controllers\PurchaseController;
use App\Controllers\PurchaseVoidController;
use App\Controllers\ReportController;
use App\Controllers\ServiceRecordController;
use App\Controllers\SmsController;
use App\Controllers\WalletController;
use App\Core\Router;

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'login'], false);
$router->post('/login', [AuthController::class, 'authenticate'], false);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/internal/cron', [CronController::class, 'run'], false);

$router->get('/portal', [PortalController::class, 'index'], false);
$router->post('/portal', [PortalController::class, 'requestOtp'], false);
$router->get('/portal/verify', [PortalController::class, 'verifyForm'], false);
$router->post('/portal/verify', [PortalController::class, 'verify'], false);
$router->get('/portal/dashboard', [PortalController::class, 'dashboard'], false);
$router->post('/portal/logout', [PortalController::class, 'logout'], false);

$router->get('/customers', [CustomerController::class, 'index']);
$router->get('/customers/create', [CustomerController::class, 'create']);
$router->post('/customers/create', [CustomerController::class, 'store']);
$router->get('/customers/edit', [CustomerController::class, 'edit']);
$router->post('/customers/edit', [CustomerController::class, 'update']);
$router->get('/customers/show', [CustomerController::class, 'show']);
$router->get('/customers/export', [CustomerController::class, 'export'], true, null, 'export');
$router->post('/customers/delete', [CustomerController::class, 'delete'], true, 'admin');
$router->post('/customers/anonymize', [CustomerController::class, 'anonymize'], true, 'admin');

$router->get('/purchases/create', [PurchaseController::class, 'create'], true, null, 'purchase');
$router->post('/purchases/create', [PurchaseController::class, 'store'], true, null, 'purchase');
$router->post('/purchases/void', [PurchaseVoidController::class, 'store'], true, null, 'void_purchase');

$router->get('/wallet/reduce', [WalletController::class, 'reduce'], true, null, 'reduce_wallet');
$router->post('/wallet/reduce', [WalletController::class, 'store'], true, null, 'reduce_wallet');

$router->get('/services', [ServiceRecordController::class, 'index']);
$router->get('/services/create', [ServiceRecordController::class, 'create']);
$router->post('/services/create', [ServiceRecordController::class, 'store']);

$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/export', [ReportController::class, 'export'], true, null, 'export');
$router->get('/reports/services-export', [ReportController::class, 'exportServices'], true, null, 'export');
$router->get('/sms/logs', [SmsController::class, 'logs']);

$router->get('/admin/users', [UserController::class, 'index'], true, 'admin');
$router->get('/admin/users/create', [UserController::class, 'create'], true, 'admin');
$router->post('/admin/users/create', [UserController::class, 'store'], true, 'admin');
$router->get('/admin/users/edit', [UserController::class, 'edit'], true, 'admin');
$router->post('/admin/users/edit', [UserController::class, 'update'], true, 'admin');
$router->get('/admin/activity-logs', [ActivityLogController::class, 'index'], true, 'admin');
$router->get('/admin/sms-settings', [SettingsController::class, 'edit'], true, 'admin');
$router->post('/admin/sms-settings', [SettingsController::class, 'update'], true, 'admin');
$router->post('/admin/cron/run', [AdminCronController::class, 'run'], true, 'admin');
$router->get('/admin/cashback-settings', [CashbackSettingsController::class, 'edit'], true, 'admin');
$router->post('/admin/cashback-settings', [CashbackSettingsController::class, 'update'], true, 'admin');
$router->get('/admin/customers/import', [CustomerImportController::class, 'form'], true, 'admin');
$router->post('/admin/customers/import', [CustomerImportController::class, 'import'], true, 'admin');
$router->get('/admin/loyalty', [LoyaltyController::class, 'index'], true, 'admin');
$router->post('/admin/loyalty/tiers', [LoyaltyController::class, 'storeTier'], true, 'admin');
$router->post('/admin/loyalty/promotions', [LoyaltyController::class, 'storePromotion'], true, 'admin');
$router->get('/admin/api-keys', [ApiKeyController::class, 'index'], true, 'admin');
$router->post('/admin/api-keys', [ApiKeyController::class, 'store'], true, 'admin');
$router->post('/admin/api-keys/deactivate', [ApiKeyController::class, 'deactivate'], true, 'admin');
$router->get('/admin/app-update', [AppUpdateController::class, 'index'], true, 'admin');
$router->post('/admin/app-update', [AppUpdateController::class, 'update'], true, 'admin');

return $router;
