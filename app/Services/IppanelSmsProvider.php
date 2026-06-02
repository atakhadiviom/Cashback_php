<?php

declare(strict_types=1);

namespace App\Services;

final class IppanelSmsProvider
{
    public function send(array $settings, string $phoneNumber, string $message): array
    {
        $token = trim((string) ($settings['api_token'] ?? ''));
        $sender = trim((string) ($settings['sender_number'] ?? ''));
        if ($token === '' || $sender === '') {
            return ['ok' => false, 'response' => 'ippanel token or sender number is empty'];
        }

        $payload = json_encode([
            'sending_type' => 'webservice',
            'from_number' => $sender,
            'message' => $message,
            'params' => ['recipients' => [$phoneNumber]],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://edge.ippanel.com/v1/api/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'response' => $error ?: 'curl failed'];
        }

        return ['ok' => $code >= 200 && $code < 300, 'response' => 'HTTP ' . $code . ': ' . $response];
    }
}
