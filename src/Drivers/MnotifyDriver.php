<?php

declare(strict_types=1);

namespace GhanaSms\Drivers;

use GhanaSms\Contracts\SmsDriver;
use GhanaSms\DTO\Message;
use GhanaSms\DTO\SmsResponse;
use GhanaSms\Exceptions\SmsException;
use GhanaSms\Support\PhoneNormalizer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * mNotify uses the API key as a URL query parameter (?key=...).
 *
 * NOTE: verify request/response fields against https://developer.mnotify.com
 * before releasing. The send endpoint is confirmed; the balance endpoint and
 * exact response keys should be checked with a live call.
 */
final class MnotifyDriver implements SmsDriver
{
    private const BASE_URL = 'https://api.mnotify.com/api/';

    public function __construct(
        private readonly ClientInterface $http,
        private readonly string $apiKey,
        private readonly ?string $defaultSender = null,
    ) {
    }

    public function send(Message $message): SmsResponse
    {
        $sender = $message->senderId ?? $this->defaultSender;
        if ($sender === null) {
            throw new SmsException('mNotify requires a sender ID.');
        }

        $json = $this->request('POST', 'sms/quick', [
            'recipient'     => [PhoneNormalizer::normalize($message->to)],
            'sender'        => $sender,
            'message'       => $message->body,
            'is_schedule'   => false,
            'schedule_date' => '',
        ]);

        $ok = ($json['status'] ?? null) === 'success';
        $summary = $json['summary'] ?? [];
        $id = $summary['message_id'] ?? $summary['_id'] ?? null;

        return new SmsResponse(
            success: $ok,
            messageId: $id !== null ? (string) $id : null,
            error: $ok ? null : (string) ($json['message'] ?? 'Unknown error'),
            raw: $json,
        );
    }

    public function sendBulk(array $messages): array
    {
        // Simple v1: one request per message.
        return array_map(fn (Message $m) => $this->send($m), $messages);
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
