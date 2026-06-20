<?php

declare(strict_types=1);

namespace App\Controllers;

final class CronController
{
    public function run(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $expected = trim((string) \config_value('cron.web_token', ''));
        if ($expected === '' || !hash_equals($expected, $token)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        $task = trim((string) ($_GET['task'] ?? 'all'));
        $result = (new \App\Services\CronRunnerService())->runTask($task);

        $format = strtolower(trim((string) ($_GET['format'] ?? 'text')));
        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');
        foreach ($result['messages'] as $line) {
            echo $line . "\n";
        }
        if (!$result['messages']) {
            echo "OK\n";
        }
        exit;
    }
}
