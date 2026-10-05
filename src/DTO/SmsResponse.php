<?php

declare(strict_types=1);

namespace GhanaSms\DTO;

use GhanaSms\Enums\ErrorType;
use GhanaSms\Exceptions\AuthenticationFailedException;
use GhanaSms\Exceptions\InsufficientBalanceException;
use GhanaSms\Exceptions\InvalidPhoneException;
use GhanaSms\Exceptions\InvalidSenderException;
use GhanaSms\Exceptions\SmsException;
use GhanaSms\Support\ErrorClassifier;

final class SmsResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly ?ErrorType $errorType = null,
        public readonly array $raw = [],
    ) {
    }

    public static function failure(string $error, ?ErrorType $type = null, array $raw = []): self
    {
        return new self(false, null, $error, $type ?? ErrorClassifier::classify($error), $raw);
    }

    /**
     * Throws a typed exception if the send failed. Returns $this on success,
     * so you can chain: $sms->send(...)->throwIfFailed()->messageId
     */
    public function throwIfFailed(): self
    {
        if ($this->success) {
            return $this;
        }

        $message = $this->error ?? 'SMS sending failed.';

        throw match ($this->errorType) {
            ErrorType::Authentication => new AuthenticationFailedException($message),
            ErrorType::InsufficientBalance => new InsufficientBalanceException($message),
            ErrorType::InvalidSender => new InvalidSenderException($message),
            ErrorType::InvalidRecipient => new InvalidPhoneException($message),
            default => new SmsException($message),
        };
    }
}
