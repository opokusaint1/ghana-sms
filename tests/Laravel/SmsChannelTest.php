<?php

declare(strict_types=1);

namespace GhanaSms\Tests\Laravel;

use GhanaSms\Exceptions\AuthenticationFailedException;
use GhanaSms\Exceptions\SmsException;
use GhanaSms\Laravel\Channels\SmsChannel;
use GhanaSms\Laravel\Messages\SmsMessage;
use GhanaSms\SmsManager;
use GhanaSms\Tests\Support\FakeDriver;
use PHPUnit\Framework\TestCase;

final class SmsChannelTest extends TestCase
{
    private FakeDriver $default;

    private FakeDriver $other;

    private function channel(): SmsChannel
    {
        $this->default = new FakeDriver();
        $this->other = new FakeDriver();

        $manager = new SmsManager([
            'default' => 'fake',
            'drivers' => ['fake' => [], 'other' => []],
        ]);
        $manager->extend('fake', fn () => $this->default);
        $manager->extend('other', fn () => $this->other);

        return new SmsChannel($manager);
    }

    private function notifiable(?string $phone = '0241234567'): object
    {
        return new class ($phone) {
            public function __construct(public ?string $phone)
            {
            }

            public function routeNotificationFor(string $driver, $notification = null): ?string
            {
                return $this->phone;
            }
        };
    }

    private function notification(string|SmsMessage $message): object
    {
        return new class ($message) {
            public function __construct(private string|SmsMessage $message)
            {
            }

            public function toSms($notifiable): string|SmsMessage
            {
                return $this->message;
            }
        };
    }

    public function test_sends_a_plain_string_message(): void
    {
        $channel = $this->channel();

        $channel->send($this->notifiable(), $this->notification('Hello'));

        $this->assertCount(1, $this->default->sent);
        $this->assertSame('0241234567', $this->default->sent[0]->to);
        $this->assertSame('Hello', $this->default->sent[0]->body);
        $this->assertNull($this->default->sent[0]->senderId);
    }

    public function test_message_object_can_set_sender_and_driver(): void
    {
        $channel = $this->channel();

        $channel->send(
            $this->notifiable(),
            $this->notification(SmsMessage::create('Hi')->from('MyShop')->driver('other'))
        );

        $this->assertCount(0, $this->default->sent);
        $this->assertCount(1, $this->other->sent);
        $this->assertSame('MyShop', $this->other->sent[0]->senderId);
    }

    public function test_skips_when_notifiable_has_no_phone(): void
    {
        $channel = $this->channel();

        $result = $channel->send($this->notifiable(null), $this->notification('Hello'));

        $this->assertNull($result);
        $this->assertCount(0, $this->default->sent);
    }

    public function test_failures_throw_typed_exceptions(): void
    {
        $channel = $this->channel();
        $this->default->fail = true;

        $this->expectException(AuthenticationFailedException::class);

        $channel->send($this->notifiable(), $this->notification('Hello'));
    }

    public function test_notification_without_to_sms_is_a_clear_error(): void
    {
        $channel = $this->channel();

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage('toSms()');

        $channel->send($this->notifiable(), new class () {
        });
    }
}
