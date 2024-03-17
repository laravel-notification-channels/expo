<?php declare(strict_types=1);

namespace NotificationChannels\Expo\Gateway;

/** @internal */
interface ExpoGateway
{
    /**
     * Send the notifications to Expo's Push Service.
     */
    public function sendPushNotifications(ExpoEnvelope $envelope): ExpoResponse;
}
