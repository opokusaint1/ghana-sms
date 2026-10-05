<?php

declare(strict_types=1);

namespace GhanaSms\Drivers;

use GhanaSms\DTO\SmsResponse;
use GhanaSms\Exceptions\SmsException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Verified live: send. Check Arkesel's docs for batch limits and balance format.
 */
final class ArkeselDriver extends BaseDriver
{
    private const BASE_URL = 'https://sms.arkesel.com/api/v2/';

    protected function providerName(): string
    {
        return 'Arkesel';
    }

    protected function dispatch(string $sender, string $body, array $recipients): array
    {
        $json = $this->request('POST', 'sms/send', [
            'sender'     => $sender,
            'message'    => $body,
            'recipients' => $recipients,
        ]);

        if (($json['status'] ?? null) === 'success') {
            $ids = [];
            foreach ($json['data'] ?? [] as $row) {
                if (isset($row['recipient'], $row['id'])) {
                    $ids[(string) $row['recipient']] = (string) $row['id'];
                }
            }

            return array_map(
                fn (string $to) => new SmsResponse(true, $ids[$to] ?? null, null, null, $json),
                $recipients
            );
        }

        $failure = SmsResponse::failure((string) ($json['message'] ?? $json['error'] ?? 'Unknown error'), null, $json);

        return array_fill(0, count($recipients), $failure);
    }

    public function balance(): ?float
    {
        $json = $this->request('GET', 'clients/balance-details');

        // TODO: confirm the exact key in Arkesel's docs.
        $value = $json['data']['sms_balance'] ?? $json['data']['balance'] ?? $json['sms_balance'] ?? null;

        return $value !== null ? (float) $value : null;
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $options = [
            'headers'     => ['api-key' => $this->apiKey, 'Accept' => 'application/json'],
            'http_errors' => false,
            'timeout'     => 15,
        ];

        if ($method === 'POST') {
            $options['json'] = $payload;
        }

        try {
            $response = $this->http->request($method, self::BASE_URL . $path, $options);
        } catch (GuzzleException $e) {
            throw new SmsException('Arkesel request failed: ' . $e->getMessage(), 0, $e);
        }

        $decoded = json_decode((string) $response->getBody(), true);

        if (!is_array($decoded)) {
            throw new SmsException('Arkesel returned an invalid response (HTTP ' . $response->getStatusCode() . ').');
        }

        return $decoded;
    }
}
