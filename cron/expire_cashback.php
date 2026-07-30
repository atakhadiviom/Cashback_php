<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

foreach ((new App\Services\CronRunnerService())->runCashbackExpiry()['messages'] as $line) {
    echo $line . PHP_EOL;
}
