<?php

declare(strict_types=1);

namespace GhanaSms\DTO;

final class SmsResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {
    }
}
