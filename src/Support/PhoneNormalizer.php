<?php

declare(strict_types=1);

namespace GhanaSms\Support;

use GhanaSms\Exceptions\InvalidPhoneException;

final class PhoneNormalizer
{
    /**
     * Converts 024xxxxxxx, +23324xxxxxxx, 23324xxxxxxx, 24xxxxxxx
     * into international format without plus: 23324xxxxxxx
     */
    public static function normalize(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '00233')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '233') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '233' . substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '233' . $digits;
        }

        throw new InvalidPhoneException("Invalid Ghana phone number: {$number}");
    }
}
