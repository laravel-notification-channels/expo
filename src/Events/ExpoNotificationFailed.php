<?php

declare(strict_types=1);

namespace NotificationChannels\Expo\Events;

use Illuminate\Notifications\Notification;
use NotificationChannels\Expo\ExpoError;

/**
 * Dispatched for each token Expo refused, beside Laravel's own event.
 *
 * The framework's NotificationFailed carries the error in an untyped `$data`
 * documented as an array, so a listener can only reach the ExpoError through a
 * `@var` annotation or an `instanceof` a static analyser reads as impossible.
 * This event states the type natively, which is what the success path's
 * ExpoNotificationSent already does.
 */
final readonly class ExpoNotificationFailed
{
    public function __construct(
        public object $notifiable,
        public Notification $notification,
        public ExpoError $error,
    ) {
    }
}
