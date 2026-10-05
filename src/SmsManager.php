<?php

declare(strict_types=1);

namespace GhanaSms;

use Closure;
use GhanaSms\Contracts\SmsDriver;
use GhanaSms\Drivers\ArkeselDriver;
use GhanaSms\Drivers\MnotifyDriver;
use GhanaSms\DTO\Message;
use GhanaSms\DTO\SmsResponse;
use GhanaSms\Exceptions\SmsException;
use GuzzleHttp\Client;

final class SmsManager
{
    /** @var array<string, SmsDriver> */
    private array $resolved = [];

    /** @var array<string, Closure> */
    private array $custom = [];

    public function __construct(private readonly array $config)
    {
    }

    public function driver(?string $name = null): SmsDriver
    {
        $name ??= $this->config['default'] ?? throw new SmsException('No default SMS driver configured.');

        return $this->resolved[$name] ??= $this->build($name);
    }

    /** Register your own driver: $manager->extend('foo', fn (array $cfg) => new FooDriver(...)) */
    public function extend(string $name, Closure $factory): void
    {
        $this->custom[$name] = $factory;
    }

    public function send(string $to, string $body, ?string $senderId = null): SmsResponse
    {
        return $this->driver()->send(new Message($to, $body, $senderId));
    }

    /**
     * @param Message[] $messages
     * @return SmsResponse[] same order as the input
     */
    public function sendBulk(array $messages): array
    {
        return $this->driver()->sendBulk($messages);
    }

    public function balance(): ?float
    {
        return $this->driver()->balance();
    }

    private function build(string $name): SmsDriver
    {
        $cfg = $this->config['drivers'][$name] ?? throw new SmsException("SMS driver [{$name}] is not configured.");

        if (isset($this->custom[$name])) {
            return ($this->custom[$name])($cfg);
        }

        return match ($name) {
            'arkesel' => new ArkeselDriver(new Client(), $cfg['api_key'] ?? '', $cfg['sender'] ?? null),
            'mnotify' => new MnotifyDriver(new Client(), $cfg['api_key'] ?? '', $cfg['sender'] ?? null),
            default   => throw new SmsException("SMS driver [{$name}] is not supported."),
        };
    }
}
