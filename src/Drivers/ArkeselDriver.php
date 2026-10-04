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
 * NOTE: verify endpoints and response shapes against Arkesel's current docs
 * before releasing. They may change.
 */
final class ArkeselDriver implements SmsDriver
{
    private const BASE_URL = 'https://sms.arkesel.com/api/v2/';

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
            throw new SmsException('Arkesel requires a sender ID.');
        }

        $json = $this->request('POST', 'sms/send', [
            'sender'     => $sender,
            'message'    => $message->body,
            'recipients' => [PhoneNormalizer::normalize($message->to)],
        ]);

        $ok = ($json['status'] ?? null) === 'success';

        return new SmsResponse(
            success: $ok,
            messageId: isset($json['data'][0]['id']) ? (string) $json['data'][0]['id'] : null,
            error: $ok ? null : (string) ($json['message'] ?? 'Unknown error'),
            raw: $json,
        );
    }

    public function sendBulk(array $messages): array
    {
        // Simple v1: one request per message. Optimize later with Arkesel's bulk endpoint.
        return array_map(fn (Message $m) => $this->send($m), $messages);
    }

    public function balance(): ?float
    {
        $json = $this->request('GET', 'clients/balance-details');

        // TODO: confirm the exact key in Arkesel's docs.
        $value = $json['data']['sms_balance'] ?? $json['data']['balance'] ?? null;

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
