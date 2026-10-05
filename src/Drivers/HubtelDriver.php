<?php

declare(strict_types=1);

namespace GhanaSms\Drivers;

use GhanaSms\DTO\SmsResponse;
use GhanaSms\Exceptions\SmsException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Hubtel SMS API: HTTP Basic Auth (client ID : client secret), JSON body with
 * From / Recipients / Content, response status 0 means accepted.
 *
 * NOTE: not yet verified against the live API. The default base URL is a best
 * guess; confirm it in Hubtel's docs and override it with 'base_url' in config
 * if needed. Hubtel's SMS API exposes no balance endpoint, so balance() is null.
 */
final class HubtelDriver extends BaseDriver
{
    public const DEFAULT_BASE_URL = 'https://sms.hubtel.com/v1/';

    private readonly string $baseUrl;

    public function __construct(
        ClientInterface $http,
        string $clientId,
        private readonly string $clientSecret,
        ?string $defaultSender = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
    ) {
        parent::__construct($http, $clientId, $defaultSender);

        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    protected function providerName(): string
    {
        return 'Hubtel';
    }

    protected function dispatch(string $sender, string $body, array $recipients): array
    {
        $json = $this->request('messages/batch/simple/send', [
            'From'       => $sender,
            'Recipients' => $recipients,
            'Content'    => $body,
        ]);

        $status = $json['status'] ?? null;

        if (is_numeric($status) && (int) $status === 0) {
            $ids = [];
            foreach ($json['data'] ?? [] as $row) {
                if (isset($row['recipient'], $row['messageId'])) {
                    $ids[(string) $row['recipient']] = (string) $row['messageId'];
                }
            }

            return array_map(
                fn (string $to) => new SmsResponse(true, $ids[$to] ?? null, null, null, $json),
                $recipients
            );
        }

        $detail = $json['message'] ?? $json['Message'] ?? $json['error'] ?? null;
        $message = is_string($detail) ? $detail : 'Hubtel returned status ' . json_encode($status);

        $failure = SmsResponse::failure($message, null, $json);

        return array_fill(0, count($recipients), $failure);
    }

    public function balance(): ?float
    {
        return null;
    }

    private function request(string $path, array $payload): array
    {
        try {
            $response = $this->http->request('POST', $this->baseUrl . $path, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->clientSecret),
                    'Accept'        => 'application/json',
                ],
                'json'        => $payload,
                'http_errors' => false,
                'timeout'     => 15,
            ]);
        } catch (GuzzleException $e) {
            throw new SmsException('Hubtel request failed: ' . $e->getMessage(), 0, $e);
        }

        $decoded = json_decode((string) $response->getBody(), true);

        if (!is_array($decoded)) {
            $code = $response->getStatusCode();

            if ($code === 401 || $code === 403) {
                return ['status' => $code, 'message' => 'Unauthorized: check your Hubtel client ID and secret.'];
            }

            throw new SmsException("Hubtel returned an invalid response (HTTP {$code}).");
        }

        return $decoded;
    }
}
