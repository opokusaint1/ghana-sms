<?php

declare(strict_types=1);

namespace GhanaSms\Drivers;

use GhanaSms\Contracts\SmsDriver;
use GhanaSms\DTO\Message;
use GhanaSms\DTO\SmsResponse;
use GhanaSms\Enums\ErrorType;
use GhanaSms\Exceptions\InvalidPhoneException;
use GhanaSms\Exceptions\SmsException;
use GhanaSms\Support\PhoneNormalizer;
use GuzzleHttp\ClientInterface;

/**
 * Shared logic for drivers: phone normalizing, sender resolution, and bulk
 * grouping. A new provider only needs dispatch(), balance() and its HTTP call.
 */
abstract class BaseDriver implements SmsDriver
{
    public function __construct(
        protected readonly ClientInterface $http,
        protected readonly string $apiKey,
        protected readonly ?string $defaultSender = null,
    ) {
    }

    abstract protected function providerName(): string;

    /**
     * Send one body to many (already normalized) recipients in a single request.
     *
     * @param string[] $recipients
     * @return SmsResponse[] one response per recipient, same order
     */
    abstract protected function dispatch(string $sender, string $body, array $recipients): array;

    /** Max recipients per request. Conservative default; override per provider. */
    protected function batchSize(): int
    {
        return 100;
    }

    public function send(Message $message): SmsResponse
    {
        $sender = $this->resolveSender($message);
        $to = PhoneNormalizer::normalize($message->to);

        return $this->dispatchAll($sender, $message->body, [$to])[0];
    }

    public function sendBulk(array $messages): array
    {
        $results = [];
        $groups = [];

        foreach ($messages as $i => $message) {
            try {
                $to = PhoneNormalizer::normalize($message->to);
            } catch (InvalidPhoneException $e) {
                $results[$i] = SmsResponse::failure($e->getMessage(), ErrorType::InvalidRecipient);
                continue;
            }

            $sender = $this->resolveSender($message);
            $key = $sender . "\0" . $message->body;

            $groups[$key]['sender'] = $sender;
            $groups[$key]['body'] = $message->body;
            $groups[$key]['items'][$i] = $to;
        }

        foreach ($groups as $group) {
            $indexes = array_keys($group['items']);
            $responses = $this->dispatchAll($group['sender'], $group['body'], array_values($group['items']));

            foreach ($indexes as $position => $i) {
                $results[$i] = $responses[$position];
            }
        }

        ksort($results);

        return $results;
    }

    protected function resolveSender(Message $message): string
    {
        $sender = $message->senderId ?? $this->defaultSender;

        if ($sender === null || $sender === '') {
            throw new SmsException($this->providerName() . ' requires a sender ID.');
        }

        return $sender;
    }

    /** @return SmsResponse[] */
    private function dispatchAll(string $sender, string $body, array $recipients): array
    {
        $out = [];

        foreach (array_chunk($recipients, $this->batchSize()) as $chunk) {
            foreach ($this->dispatch($sender, $body, $chunk) as $response) {
                $out[] = $response;
            }
        }

        return $out;
    }
}
