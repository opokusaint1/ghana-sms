<?php

declare(strict_types=1);

namespace GhanaSms\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \GhanaSms\DTO\SmsResponse send(string $to, string $body, ?string $senderId = null)
 * @method static \GhanaSms\Contracts\SmsDriver driver(?string $name = null)
 */
class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sms';
    }
}
