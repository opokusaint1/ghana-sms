<?php

declare(strict_types=1);

namespace GhanaSms\Drivers;

use GhanaSms\DTO\SmsResponse;
use GhanaSms\Exceptions\SmsException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * mNotify uses the API key as a URL query parameter (?key=...).
 *
 * NOTE: not yet verified against the live API with a valid key. The error
 * format (an "error" key) was observed live; success parsing and the balance
 * endpoint should be confirmed.
 */
final class MnotifyDriver extends BaseDriver
{
    private const BASE_URL = 'https://api.mnotify.com/api/';

    protected function providerName(): string
    {
        return 'mNotify';
    }

    protected function dispatch(string $sender, string $body, array $recipients): array
    {
        $json = $this->request('POST', 'sms/quick', [
            'recipient'     => $recipients,
            'sender'        => $sender,
            'message'       => $body,
            'is_schedule'   => false,
            'schedule_date' => '',
        ]);

        if (($json['status'] ?? null) === 'success') {
            $summary = $json['summary'] ?? [];
            $id = $summary['message_id'] ?? $summary['_id'] ?? null;
            $id = $id !== null ? (string) $id : null;

            return array_map(
                fn (string $to) => new SmsResponse(true, $id, null, null, $json),
                $recipients
            );
        }

        $failure = SmsResponse::failure((string) ($json['message'] ?? $json['error'] ?? 'Unknown error'), null, $json);

        return array_fill(0, count($recipients), $failure);
    }

    public function balance(): ?float
    {
        // TODO: confirm endpoint and key against mNotify's docs.
        $json = $this->request('GET', 'balance/sms');

        $value = $json['balance'] ?? $json['data']['balance'] ?? null;

        return $value !== null ? (float) $value : null;
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $options = [
            'query'       => ['key' => $this->apiKey],
            'headers'     => ['Accept' => 'application/json'],
            'http_errors' => false,
            'timeout'     => 15,
        ];

        if ($method === 'POST') {
            $options['json'] = $payload;
        }

        try {
            $response = $this->http->request($method, self::BASE_URL . $path, $options);
        } catch (GuzzleException $e) {
            throw new SmsException('mNotify request failed: ' . $e->getMessage(), 0, $e);
        }

        $decoded = json_decode((string) $response->getBody(), true);

        if (!is_array($decoded)) {
            throw new SmsException('mNotify returned an invalid response (HTTP ' . $response->getStatusCode() . ').');
        }

        return $decoded;
    }
}
