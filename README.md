<p align="center"><img src="https://github.com/laravel-notification-channels/expo/blob/main/art/socialcard.png?raw=true" alt="Social Card of Laravel Expo Channel"></p>

# Expo Notifications Channel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel-notification-channels/expo.svg?style=flat-square)](https://packagist.org/packages/laravel-notification-channels/expo)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/laravel-notification-channels/expo/run-tests.yml?branch=main)](https://github.com/laravel-notification-channels/expo/actions?query=workflow%3ATests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel-notification-channels/expo.svg?style=flat-square)](https://packagist.org/packages/laravel-notification-channels/expo)

[Expo](https://docs.expo.dev/push-notifications/overview/) channel for pushing notifications to your React Native apps.

## Contents

- [Installation](#installation)
- [Additional Security](#additional-security-optional)
- [Usage](#usage)
- [Expo Message Request Format](#expo-message-request-format)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing) 
- [Security](#security)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require laravel-notification-channels/expo
```

## Additional Security (optional)

You can require any push notifications to be sent with an additional [Access Token](https://docs.expo.dev/push-notifications/sending-notifications/#additional-security) before Expo delivers them to your users.

If you want to make use of this additional security layer, add the following to your `config/services.php` file:

```php
'expo' => [
    'access_token' => env('EXPO_ACCESS_TOKEN'),
],
```

## Usage

You can now use the `expo` channel in the `via()` method of your `Notification`s.

### Notification / `ExpoMessage`

First things first, you need to have a [Notification](https://laravel.com/docs/9.x/notifications) that needs to be delivered to someone. Check out the [Laravel documentation](https://laravel.com/docs/9.x/notifications#generating-notifications) for more information on generating notifications. 

```php
final class SuspiciousActivityDetected extends Notification
{
    public function toExpo($notifiable): ExpoMessage
    {
        return ExpoMessage::create('Suspicious Activity')
            ->body('Someone tried logging in to your account!')
            ->data($notifiable->only('email', 'id'))
            ->expiresAt(Carbon::now()->addHour())
            ->priority('high')
            ->playSound();
    }

    public function via($notifiable): array
    {
        return ['expo'];
    }
}
```

> **Note** Detailed explanation regarding the Expo Message Request Format can be found [here](#expo-message-request-format).

You can also apply conditionals to `ExpoMessage` without breaking the method chain:

```php
public function toExpo($notifiable): ExpoMessage
{
    return ExpoMessage::create('Suspicious Activity')
        ->body('Someone tried logging in to your account!')
        ->when($notifiable->wantsSound(), fn ($msg) => $msg->playSound())
        ->unless($notifiable->isVip(), fn ($msg) => $msg->normal(), fn ($msg) => $msg->high());
}
```

### Notifiable / `ExpoPushToken`

Next, you will have to set a `routeNotificationForExpo()` method in your `Notifiable` model. 

#### Unicasting (single device)

The method **must** return either an instance of `ExpoPushToken` or `null`.  An example:

```php
final class User extends Authenticatable
{
    use Notifiable;

    protected $casts = ['expo_token' => ExpoPushToken::class];

    public function routeNotificationForExpo(): ?ExpoPushToken
    {
        return $this->expo_token;
    }
}
```

> **Warning** No notifications will be sent in case of `null`.

> **Note** More info regarding the model cast can be found [here](#model-casting).

#### Multicasting (multiple devices)

The method **must** return an `array<int, ExpoPushToken>` or `Collection<int, ExpoPushToken>`, 
the specific implementation depends on your use case. An example:

```php
final class User extends Authenticatable
{
    use Notifiable;

    /**
    * @return Collection<int, ExpoPushToken>
    */
    public function routeNotificationForExpo(): Collection
    {
        return $this->devices->pluck('expo_token');
    }
}
```

> **Warning** No notifications will be sent in case of an empty `Collection`.

### Sending

Once everything is in place, you can simply send a notification by calling:

```php
$user->notify(new SuspiciousActivityDetected());
```

### Validation

You ought to have an HTTP endpoint that associates a given `ExpoPushToken` with an authenticated `User` so that you can deliver push notifications. For this reason, we're also providing a custom validation `ExpoPushTokenRule` class which you can use to protect your endpoints. An example:

```php
final class StoreDeviceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'min:2', 'max:255'],
            'token' => ['required', ExpoPushToken::rule()],
        ];
    }
}
```

### Model casting

The `ExpoChannel` expects you to return an instance of `ExpoPushToken` from your `Notifiable`s. You can easily achieve this by applying the `ExpoPushToken` as a custom model cast. An example:

```php
final class User extends Authenticatable
{
    use Notifiable;

    protected $casts = ['expo_token' => AsExpoPushToken::class];
}
```

This custom value object guarantees the integrity of the push token. You should make sure that [only valid tokens](#validation) are saved. 

### Handling failed deliveries

Unfortunately, Laravel does not provide an [OOB solution](https://github.com/laravel-notification-channels/channels/issues/16) for handling failed deliveries. However, there is a `NotificationFailed` event which Laravel does provide so you can hook into failed delivery attempts. This is particularly useful when an old token is no longer valid and the service starts responding with `DeviceNotRegistered` errors.

You can register an event listener that listens to this event and handles the appropriate errors. An example:

```php
final class HandleFailedExpoNotifications
{
    public function handle(NotificationFailed $event)
    {
        if ($event->channel !== 'expo') return;
        
        /** @var ExpoError $error */
        $error = $event->data;

        // Remove old token
        if ($error->type->isDeviceNotRegistered()) {
            $event->notifiable->update(['expo_token' => null]);
        } else {
            // do something else like logging...
        }
    }
}
```

The `NotificationFailed::$data` property will contain an instance of `ExpoError` which has the following properties:

```php
final class ExpoError
{
    private function __construct(
        public readonly ExpoErrorType $type,
        public readonly ExpoPushToken $token,
        public readonly string $message,
    ) {}
}
```

## Expo Message Request Format

The `ExpoMessage` class contains the following methods for defining the message payload. All of these methods correspond to the available payload defined in the [Expo Push documentation](https://docs.expo.dev/push-notifications/sending-notifications/#message-request-format). 

- [Badge (iOS)](#badge-ios)
- [Body](#body)
- [Category ID](#category-id)
- [Channel ID (Android)](#channel-id-android)
- [JSON data](#json-data)
- [Expiration](#expiration)
- [Mutable content (iOS)](#mutable-content-ios)
- [Notification sound (iOS)](#notification-sound-ios)
- [Priority](#priority)
- [Subtitle (iOS)](#subtitle-ios)
- [Title](#title)
- [TTL (Time to live)](#ttl-time-to-live)

### Badge (iOS)

Sets the number to display in the badge on the app icon.

```php
badge(int $value)
```

> **Note**
> The value must be greater than or equal to 0.

### Body

Sets the message body to display in the notification.

```php
body(string $value)
text(string $value)
```

> **Note**
> The value must not be empty.

### Category ID

Sets the ID of the notification category that this notification is associated with.

```php
categoryId(string $value)
```

> **Note**
> The value must not be empty.

### Channel ID (Android)

Sets the ID of the Notification Channel through which to display this notification.

```php
channelId(string $value)
```

> **Note**
> The value must not be empty.

### JSON data

Sets the JSON data for the message.

```php
data(Arrayable|Jsonable|JsonSerializable|array $value)
```

> **Warning**
> We're compressing JSON payloads that exceed 1 KiB using Gzip (if `ext-zlib` is available). While you could technically send more than 4 KiB of data, this is not recommended.

### Expiration

Sets the expiration time of the message. Same effect as TTL.

```php
expiresAt(DateTimeInterface|int $value)
```

> **Warning**
> `TTL` takes precedence if both are set.

> **Note**
> The value must be in the future.

### Mutable content (iOS)

Sets whether the notification can be intercepted by the client app.

```php
mutableContent(bool $value = true)
```

### Notification sound (iOS)

Play the default notification sound when the recipient receives the notification.

```php
playSound()
```

> **Warning**
> Custom sounds are not supported.

### Priority

Sets the delivery priority of the message.

```php
priority(string $value)
default()
normal()
high()
```

> **Note**
> The value must be `default`, `normal` or `high`.

### Subtitle (iOS)

Sets the subtitle to display in the notification below the title.

```php
subtitle(string $value)
```

> **Note**
> The value must not be empty.

### Title

Set the title to display in the notification.

```php
title(string $value)
```

> **Note**
> The value must not be empty.

### TTL (Time to live)

Set the number of seconds for which the message may be kept around for redelivery.

```php
ttl(int $value)
expiresIn(int $value)
```

> **Warning**
> Takes precedence over `expiration` if both are set.

> **Note**
> The value must be greater than 0.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email muhammed@dive.be instead of using the issue tracker.

## Credits

- [Muhammed Sari](https://github.com/mabdullahsari)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
