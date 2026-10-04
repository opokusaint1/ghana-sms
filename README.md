# ghana-sms

One consistent API for Ghanaian SMS providers. Swap providers by changing one config value.

> Note: the mNotify driver is unit-tested but not yet verified against the live API.

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
$response->success; // bool
```

## Roadmap
- [x] Arkesel
- [ ] mNotify
- [ ] Hubtel
- [ ] WordPress adapter
- [ ] Payments (MoMo, Paystack)

## Testing
```bash
composer install && composer test
```
