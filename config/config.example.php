<?php

return [
    'app' => [
        'name' => 'سیستم مدیریت کش بک نوآوران زیبایی',
        'base_url' => '',
        'timezone' => 'Asia/Tehran',
        'debug' => false,
        'birthday_required' => false,
        'company_name' => 'نوآوران زیبایی',
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
];
