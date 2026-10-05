# Changelog

## 0.2.0
- Real bulk sending: messages with the same body and sender go out in one request per provider (chunked at 100 recipients).
- Typed errors: `SmsResponse::$errorType` (`ErrorType` enum) and `throwIfFailed()` with `AuthenticationFailedException`, `InsufficientBalanceException`, `InvalidSenderException`.
- `BaseDriver` extracts shared logic, so new providers need far less code.
- `SmsManager::sendBulk()` and `SmsManager::balance()`.
- mNotify errors returned under an `error` key are now read correctly.

## 0.1.0
- Initial release: Arkesel and mNotify drivers, phone normalizer, Laravel service provider and facade.
