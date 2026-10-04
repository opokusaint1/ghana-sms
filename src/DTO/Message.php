<?php

declare(strict_types=1);

namespace GhanaSms\DTO;

final class Message
{
    public function __construct(
        public readonly string $to,
        public readonly string $body,
        public readonly ?string $senderId = null,
    ) {
    }
}
