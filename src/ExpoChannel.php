<?php declare(strict_types=1);

namespace NotificationChannels\Expo;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use NotificationChannels\Expo\Exceptions\CouldNotSendNotification;
use NotificationChannels\Expo\Gateway\ExpoEnvelope;
use NotificationChannels\Expo\Gateway\ExpoGateway;

final readonly class ExpoChannel
{
    /**
     * The channel's name.
     */
    public const string NAME = 'expo';

    /**
     * Create a new channel instance.
     */
    public function __construct(private ExpoGateway $gateway, private Dispatcher $events) {}

    /**
     * Send the notification to Expo's Push API.
     *
     * @throws CouldNotSendNotification
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $tokens = $this->getTokens($notifiable, $notification);

        if (! count($tokens)) {
            return;
        }

        $message = $this->getMessage($notifiable, $notification);

        $response = $this->gateway->sendPushNotifications(
            ExpoEnvelope::make($tokens, $message)
        );

        if ($response->isFailure()) {
            $this->dispatchFailedEvents($notifiable, $notification, $response->errors());
        } elseif ($response->isFatal()) {
            throw CouldNotSendNotification::becauseTheServiceRespondedWithAnError($response->message());
        }
    }

    /**
     * Dispatch failed events for notifications that weren't delivered.
     */
    private function dispatchFailedEvents(object $notifiable, Notification $notification, array $errors): void
    {
        foreach ($errors as $error) {
            $this->events->dispatch(new NotificationFailed($notifiable, $notification, self::NAME, $error));
        }
    }

    /**
     * Get the message that should be delivered.
     *
     * @throws CouldNotSendNotification
     */
    private function getMessage(object $notifiable, Notification $notification): ExpoMessage
    {
        if (! method_exists($notification, 'toExpo')) {
            throw CouldNotSendNotification::becauseTheMessageIsMissing();
        }

        return $notification->toExpo($notifiable);
    }

    /**
     * Get the recipients that the message should be delivered to.
     *
     * @return array<int, ExpoPushToken>
     *
     * @throws CouldNotSendNotification
     */
    private function getTokens(object $notifiable, Notification $notification): array
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            throw CouldNotSendNotification::becauseNotifiableIsInvalid();
        }

        $tokens = $notifiable->routeNotificationFor(self::NAME, $notification);

        if ($tokens instanceof Arrayable) {
            $tokens = $tokens->toArray();
        }

        return Arr::wrap($tokens);
    }
}
