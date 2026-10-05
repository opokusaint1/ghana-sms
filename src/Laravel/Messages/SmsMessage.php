<?php

declare(strict_types=1);

namespace GhanaSms\Laravel\Messages;

/**
 * Fluent message for notifications:
 *   SmsMessage::create('Your order shipped')->from('MyShop')->driver('mnotify')
 */
final class SmsMessage
{
    public function __construct(
        public string $content = '',
        public ?string $from = null,
        public ?string $driver = null,
    ) {
    }

    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function from(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function driver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }
}
