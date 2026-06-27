<?php

declare(strict_types=1);

namespace App\Services;

final class CpanelCronService
{
    private string $host;
    private string $username;
    private string $apiToken;
    private string $domain;
    private string $phpPath;
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) \config_value('cpanel.enabled', false);
        $this->host = (string) \config_value('cpanel.host', '');
        $this->username = (string) \config_value('cpanel.username', '');
        $this->apiToken = (string) \config_value('cpanel.api_token', '');
        $this->domain = (string) \config_value('cpanel.domain', '');
        $this->phpPath = (string) \config_value('cpanel.php_path', '/usr/local/bin/ea-php81');
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->host !== '' && $this->username !== '' && $this->apiToken !== '';
    }

    /**
     * @return array{ok: bool, message: string, crons: array}
     */
    public function listCrons(): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'message' => 'cPanel API not configured', 'crons' => []];
        }

        $response = $this->callUapi('Cron', 'list_cron', []);
        if (!$response['ok']) {
            $response = $this->callApi2('Cron', 'listcron', []);
        }

        if (!$response['ok']) {
            return $response;
        }

        $data = $response['data']['data'] ?? $response['data']['cpanelresult']['data'] ?? [];

        return [
            'ok' => true,
            'message' => 'OK',
            'crons' => is_array($data) ? $data : [],
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function ensureCronJobs(): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'message' => 'cPanel API not configured in config.php'];
        }

        $root = '/home/' . $this->username . '/' . $this->domain;
        $php = $this->phpPath;

        $jobs = [
            [
                'minute' => '0',
                'hour' => '8',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
                'command' => "{$php} {$root}/cron/run.php birthday",
            ],
            [
                'minute' => '0',
                'hour' => '9',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
                'command' => "{$php} {$root}/cron/run.php contract_renewal",
            ],
            [
                'minute' => '0',
                'hour' => '10',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
                'command' => "{$php} {$root}/cron/run.php due_date_reminders",
            ],
            [
                'minute' => '*/15',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
                'command' => "{$php} {$root}/cron/run.php sms_retry",
            ],
        ];

        $existing = $this->listCrons();
        if (!$existing['ok']) {
            return $existing;
        }

        $existingCommands = array_column($existing['crons'], 'command');

        $created = 0;
        foreach ($jobs as $job) {
            if (in_array($job['command'], $existingCommands, true)) {
                continue;
            }

            $result = $this->callUapi('Cron', 'add_line', [
                'minute' => $job['minute'],
                'hour' => $job['hour'],
                'day' => $job['day'],
                'month' => $job['month'],
                'weekday' => $job['weekday'],
                'command' => $job['command'],
            ]);
            if (!$result['ok']) {
                $result = $this->callApi2('Cron', 'add_line', [
                    'minute' => $job['minute'],
                    'hour' => $job['hour'],
                    'day' => $job['day'],
                    'month' => $job['month'],
                    'weekday' => $job['weekday'],
                    'command' => $job['command'],
                ]);
            }

            if ($result['ok']) {
                $created++;
            }
        }

        return [
            'ok' => true,
            'message' => $created > 0 
                ? "Created {$created} new cron job(s) successfully." 
                : "All required cron jobs already exist.",
        ];
    }

    private function callUapi(string $module, string $function, array $params): array
    {
        $url = "https://{$this->host}/execute/{$module}/{$function}";

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'message' => 'Failed to initialize cURL', 'data' => []];
        }

        $headers = [
            'Authorization: cpanel ' . $this->username . ':' . $this->apiToken,
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($body) || $status !== 200) {
            return ['ok' => false, 'message' => $error ?: "HTTP {$status}", 'data' => []];
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return ['ok' => false, 'message' => 'Invalid JSON response', 'data' => []];
        }

        if (!empty($json['errors'])) {
            return ['ok' => false, 'message' => implode(', ', $json['errors']), 'data' => $json];
        }

        return ['ok' => true, 'message' => 'OK', 'data' => $json];
    }

    private function callApi2(string $module, string $function, array $params): array
    {
        $query = array_merge([
            'cpanel_jsonapi_user' => $this->username,
            'cpanel_jsonapi_apiversion' => 2,
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
        ], $params);
        $url = 'https://' . $this->host . '/json-api/cpanel?' . http_build_query($query);

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'message' => 'Failed to initialize cURL', 'data' => []];
        }

        $password = trim((string) \config_value('cpanel.password', ''));
        $headers = $password === ''
            ? ['Authorization: cpanel ' . $this->username . ':' . $this->apiToken, 'Accept: application/json']
            : ['Authorization: Basic ' . base64_encode($this->username . ':' . $password), 'Accept: application/json'];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($body) || $status !== 200) {
            return ['ok' => false, 'message' => $error ?: "HTTP {$status}", 'data' => []];
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return ['ok' => false, 'message' => 'Invalid JSON response', 'data' => []];
        }

        $result = $json['cpanelresult']['event']['result'] ?? $json['cpanelresult']['preevent']['result'] ?? 0;
        if ((int) $result !== 1) {
            $reason = $json['cpanelresult']['error'] ?? $json['cpanelresult']['reason'] ?? 'cPanel API2 call failed.';
            return ['ok' => false, 'message' => (string) $reason, 'data' => $json];
        }

        return ['ok' => true, 'message' => 'OK', 'data' => $json];
    }
}
