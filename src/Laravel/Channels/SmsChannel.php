<?php

declare(strict_types=1);

namespace GhanaSms\Laravel\Channels;

use GhanaSms\DTO\Message;
use GhanaSms\DTO\SmsResponse;
use GhanaSms\Exceptions\SmsException;
use GhanaSms\Laravel\Messages\SmsMessage;
use GhanaSms\SmsManager;

/**
 * Laravel notification channel. Failures throw typed exceptions, so queued
 * notifications are retried or marked failed instead of silently lost.
 *
 * The parameters are deliberately untyped against Laravel classes so the
 * channel can be unit-tested without a Laravel installation.
 */
final class SmsChannel
{
    public function __construct(private readonly SmsManager $sms)
    {
    }

    public function send(object $notifiable, object $notification): ?SmsResponse
    {
        $to = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('sms', $notification)
            : null;

        if ($to === null || $to === '') {
            return null;
        }

        if (!method_exists($notification, 'toSms')) {
            throw new SmsException(
                $notification::class . ' must define a toSms() method to use the sms channel.'
            );
        }

        $message = $notification->toSms($notifiable);

        if (is_string($message)) {
            $message = new SmsMessage($message);
        }

        if (!$message instanceof SmsMessage) {
            throw new SmsException('toSms() must return a string or a GhanaSms\Laravel\Messages\SmsMessage.');
        }

        return $this->sms
            ->driver($message->driver)
            ->send(new Message((string) $to, $message->content, $message->from))
            ->throwIfFailed();
    }
}
