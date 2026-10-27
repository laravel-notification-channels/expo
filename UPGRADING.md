# Upgrade Guide

## v2 → v3

`NotificationFailed::$data` now holds an array instead of the `ExpoError` itself, which is what the
framework documents and what the other notification channels pass.

If you have a listener on `NotificationFailed`, read the error from the `error` key:

```php
// Before
$error = $event->data;

// After
$error = $event->data['error'];
```

Listeners on `ExpoNotificationFailed` need no change.

## v1 → v2

The Expo notifications channel has been rewritten from the ground up with a much more stable foundation.

Unfortunately, this means that there is no direct upgrade path from v1 to v2.
