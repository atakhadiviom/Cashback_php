<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

foreach ((new App\Services\CronRunnerService())->runSmsRetry()['messages'] as $line) {
    echo $line . PHP_EOL;
}
