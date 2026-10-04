<?php

declare(strict_types=1);

namespace GhanaSms\Tests;

use GhanaSms\Exceptions\InvalidPhoneException;
use GhanaSms\Support\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNormalizerTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function test_normalizes_valid_numbers(string $input): void
    {
        $this->assertSame('233241234567', PhoneNormalizer::normalize($input));
    }

    public static function validNumbers(): array
    {
        return [
            ['0241234567'],
            ['+233241234567'],
            ['233241234567'],
            ['241234567'],
            ['024 123 4567'],
            ['+233 24 123 4567'],
            ['00233241234567'],
        ];
    }

    public function test_rejects_invalid_numbers(): void
    {
        $this->expectException(InvalidPhoneException::class);
        PhoneNormalizer::normalize('12345');
    }
}
