# ghana-sms

One consistent API for Ghanaian SMS providers. Swap providers by changing one config value.

| Provider | Status |
|----------|--------|
| Arkesel  | Verified live (send) |
| mNotify  | Unit-tested, not yet verified against the live API |
| Hubtel   | Unit-tested, not yet verified against the live API |

## Install

```bash
composer require cyberxgh/ghana-sms
```

## Laravel

```bash
php artisan vendor:publish --tag=sms-config
```

`.env`:

```
SMS_DRIVER=arkesel
ARKESEL_API_KEY=your-key
ARKESEL_SENDER=MyApp

# or
SMS_DRIVER=mnotify
MNOTIFY_API_KEY=your-key
MNOTIFY_SENDER=MyApp

# or
SMS_DRIVER=hubtel
HUBTEL_CLIENT_ID=your-client-id
HUBTEL_CLIENT_SECRET=your-client-secret
HUBTEL_SENDER=MyApp
```

```php
use GhanaSms\Laravel\Facades\Sms;

Sms::send('0241234567', 'Your code is 4821');
```

## Plain PHP

```php
$sms = new GhanaSms\SmsManager([
    'default' => 'arkesel',
    'drivers' => ['arkesel' => ['api_key' => '...', 'sender' => 'MyApp']],
]);

$response = $sms->send('0241234567', 'Hello');
$response->success;   // bool
$response->messageId; // string|null
```

Numbers can be written as `024...`, `+23324...` or `23324...`; they are normalized for you.

## Bulk sending

```php
use GhanaSms\DTO\Message;

$results = $sms->sendBulk([
    new Message('0241234567', 'Reminder: meeting at 3pm'),
    new Message('0201234567', 'Reminder: meeting at 3pm'),
    new Message('0551234567', 'Your order has shipped'),
]);
```

Messages with the same body and sender are sent in a single request. Results come back in the same order as the input; an invalid number fails only that message.

## Error handling

Every failure has a stable type, whichever provider you use:

```php
$response = $sms->send('0241234567', 'Hello');

if (!$response->success) {
    $response->errorType; // ErrorType::Authentication, InsufficientBalance, InvalidSender, InvalidRecipient, Unknown
    $response->error;     // the provider's message
}
```

Or let it throw:

```php
use GhanaSms\Exceptions\InsufficientBalanceException;

try {
    $sms->send('0241234567', 'Hello')->throwIfFailed();
} catch (InsufficientBalanceException $e) {
    // top up credits
}
```

## Roadmap
- [x] Arkesel
- [x] mNotify
- [x] Bulk sending, typed errors
- [x] Hubtel
- [ ] Delivery reports
- [ ] Laravel notification channel
- [ ] WordPress adapter

## Testing
```bash
composer install && composer test

