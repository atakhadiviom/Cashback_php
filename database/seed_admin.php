<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name = $argv[3] ?? 'مدیر سیستم';

if (!$username || !$password) {
    echo "Usage: php database/seed_admin.php admin \"StrongPassword\" \"مدیر سیستم\"\n";
    exit(1);
}

$pdo = Database::pdo();
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
$stmt->execute(['username' => $username]);
if ($stmt->fetch()) {
    echo "User already exists: {$username}\n";
    exit(0);
}

$now = current_datetime();
$stmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role, is_active, created_at, updated_at) VALUES (:name, :username, :password_hash, "admin", 1, :created_at, :updated_at)');
$stmt->execute([
    'name' => $name,
    'username' => $username,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'created_at' => $now,
    'updated_at' => $now,
]);

echo "Admin user created: {$username}\n";
