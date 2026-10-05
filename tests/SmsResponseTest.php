<?php

declare(strict_types=1);

namespace GhanaSms\Tests;

use GhanaSms\DTO\SmsResponse;
use GhanaSms\Enums\ErrorType;
use GhanaSms\Exceptions\AuthenticationFailedException;
use GhanaSms\Exceptions\InsufficientBalanceException;
use GhanaSms\Exceptions\SmsException;
use PHPUnit\Framework\TestCase;

final class SmsResponseTest extends TestCase
{
    public function test_success_returns_itself(): void
    {
        $response = new SmsResponse(true, 'id-1');

        $this->assertSame($response, $response->throwIfFailed());
    }

    public function test_authentication_failure_throws_typed_exception(): void
    {
        $this->expectException(AuthenticationFailedException::class);

        SmsResponse::failure('invalid api key')->throwIfFailed();
    }

    public function test_balance_failure_throws_typed_exception(): void
    {
        $this->expectException(InsufficientBalanceException::class);

        SmsResponse::failure('anything', ErrorType::InsufficientBalance)->throwIfFailed();
    }

    public function test_unknown_failure_throws_base_exception(): void
    {
        $this->expectException(SmsException::class);

        SmsResponse::failure('weird')->throwIfFailed();
    }
}
