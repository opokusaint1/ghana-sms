<?php

declare(strict_types=1);

namespace GhanaSms\Tests;

use GhanaSms\Enums\ErrorType;
use GhanaSms\Support\ErrorClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ErrorClassifierTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_classifies(string $message, ErrorType $expected): void
    {
        $this->assertSame($expected, ErrorClassifier::classify($message));
    }

    public static function cases(): array
    {
        return [
            ['invalid api key. please make sure your api key is valid and enabled', ErrorType::Authentication],
            ['Unauthorized', ErrorType::Authentication],
            ['Insufficient balance', ErrorType::InsufficientBalance],
            ['Not enough credit', ErrorType::InsufficientBalance],
            ['Sender ID not approved', ErrorType::InvalidSender],
            ['Invalid recipient number', ErrorType::InvalidRecipient],
            ['Something strange happened', ErrorType::Unknown],
        ];
    }
}
