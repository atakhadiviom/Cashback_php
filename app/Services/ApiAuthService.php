<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ApiKeyRepository;

final class ApiAuthService
{
    public function authenticate(string $apiKey): ?array
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '' || !str_contains($apiKey, '.')) {
            return null;
        }
        [$prefix, $secret] = explode('.', $apiKey, 2);
        $row = (new ApiKeyRepository())->findByPrefix($prefix);
        if (!$row || !password_verify($secret, $row['key_hash'])) {
            return null;
        }
        (new ApiKeyRepository())->touchUsed((int) $row['id']);
        return $row;
    }

    public static function generateKey(): array
    {
        $prefix = bin2hex(random_bytes(4));
        $secret = bin2hex(random_bytes(16));
        $plain = $prefix . '.' . $secret;
        return [
            'plain' => $plain,
            'prefix' => $prefix,
            'hash' => password_hash($secret, PASSWORD_DEFAULT),
        ];
    }
}
