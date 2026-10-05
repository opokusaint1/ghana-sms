<?php

declare(strict_types=1);

namespace GhanaSms\Support;

use GhanaSms\Enums\ErrorType;

/**
 * Maps a provider's free-text error message to a stable ErrorType.
 * Providers phrase errors differently, so this is keyword based.
 */
final class ErrorClassifier
{
    public static function classify(?string $message): ErrorType
    {
        $m = strtolower($message ?? '');

        return match (true) {
            self::has($m, ['api key', 'apikey', 'unauthor', 'authentic', 'invalid key']) => ErrorType::Authentication,
            self::has($m, ['insufficient', 'balance', 'credit']) => ErrorType::InsufficientBalance,
            self::has($m, ['sender']) => ErrorType::InvalidSender,
            self::has($m, ['recipient', 'phone', 'number']) => ErrorType::InvalidRecipient,
            default => ErrorType::Unknown,
        };
    }

    private static function has(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
