<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Services\SmsService;

$count = (new SmsService())->retryPending();
echo "Retried {$count} SMS messages.\n";
