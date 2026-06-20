<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$task = $argv[1] ?? 'all';
$result = (new App\Services\CronRunnerService())->runTask($task);
foreach ($result['messages'] as $line) {
    echo $line . PHP_EOL;
}
if (!$result['messages']) {
    echo "OK" . PHP_EOL;
}
exit($result['ok'] ? 0 : 1);
