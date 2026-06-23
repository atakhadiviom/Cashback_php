<?php

return [
    'app' => [
        'name' => 'سیستم مدیریت کش‌بک و مشتریان',
        'base_url' => '',
        'timezone' => 'Asia/Tehran',
        'debug' => false,
        'birthday_required' => false,
        'company_name' => 'پرشین نت',
    ],
    'database' => [
        'host' => 'localhost',
        'name' => 'cpanel_database_name',
        'user' => 'cpanel_database_user',
        'password' => 'cpanel_database_password',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'session_name' => 'cashback_session',
        'session_lifetime_minutes' => 120,
        'login_max_attempts' => 5,
        'login_lockout_minutes' => 15,
    ],
    'portal' => [
        'enabled' => true,
        'otp_ttl_seconds' => 300,
        'otp_max_attempts' => 5,
        'otp_rate_limit_per_hour' => 5,
    ],
    'updater' => [
        'enabled' => true,
        'github_owner' => 'atakhadiviom',
        'github_repo' => 'Cashback_php',
        'branch' => 'main',
        'github_token' => '',
    ],
    'cron' => [
        // Set a long random string to enable https://your-site/internal/cron?task=all&token=...
        'web_token' => '',
        'dashboard_auto_run' => true,
        'sms_retry_interval_minutes' => 15,
    ],
    'cpanel' => [
        'enabled' => false,
        'host' => 'yourdomain.com:2083',
        'username' => 'your_cpanel_username',
        'api_token' => 'your_cpanel_api_token_here',
        // Optional fallback for old cPanel API2 hosts. Prefer api_token when possible.
        'password' => '',
        'domain' => 'yourdomain.com',
        'php_path' => '/usr/local/bin/ea-php81',
    ],
];
