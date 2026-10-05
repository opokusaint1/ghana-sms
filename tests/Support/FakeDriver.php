<?php

declare(strict_types=1);

namespace GhanaSms\Tests\Support;

use GhanaSms\Contracts\SmsDriver;
use GhanaSms\DTO\Message;
use GhanaSms\DTO\SmsResponse;

final class FakeDriver implements SmsDriver
{
    /** @var Message[] */
    public array $sent = [];

    public bool $fail = false;

    public function send(Message $message): SmsResponse
    {
        if ($this->fail) {
            return SmsResponse::failure('invalid api key');
        }

        $this->sent[] = $message;

        return new SmsResponse(true, 'id-' . count($this->sent));
    }

    public function sendBulk(array $messages): array
    {
        return array_map(fn (Message $m) => $this->send($m), $messages);
    }

    public function balance(): ?float
    {
        return null;
    }
}
