<?php

declare(strict_types=1);

namespace GhanaSms\Contracts;

use GhanaSms\DTO\Message;
use GhanaSms\DTO\SmsResponse;

interface SmsDriver
{
    public function send(Message $message): SmsResponse;

    /**
     * @param Message[] $messages
     * @return SmsResponse[]
     */
    public function sendBulk(array $messages): array;

    /** Remaining balance, or null if the provider doesn't expose it. */
    public function balance(): ?float;
}
