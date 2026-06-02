<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\ActivityLogRepository;

final class ActivityLogger
{
    public function log(string $type, string $description, ?int $customerId = null, ?int $userId = null): void
    {
        (new ActivityLogRepository())->create($userId ?? Auth::id(), $type, $description, $customerId);
    }
}
