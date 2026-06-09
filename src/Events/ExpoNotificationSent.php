<?php

declare(strict_types=1);

namespace NotificationChannels\Expo\Events;

use Illuminate\Notifications\Notification;

final readonly class ExpoNotificationSent
{
    /**
     * @param  array<string, string>  $tickets  map of push-token string → Expo ticket UUID
     */
    public function __construct(
        public object $notifiable,
        public Notification $notification,
        public array $tickets,
    ) {
    }
}
