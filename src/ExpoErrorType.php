<?php declare(strict_types=1);

namespace NotificationChannels\Expo;

enum ExpoErrorType: string
{
    /**
     * The device cannot receive push notifications anymore.
     * You should stop sending messages to the corresponding Expo push token.
     */
    case DeviceNotRegistered = 'DeviceNotRegistered';

    /**
     * The total notification payload was too large.
     * On Android and iOS the total payload must be at most 4096 bytes.
     */
    case MessageTooBig = 'MessageTooBig';

    /**
     * You are sending messages too frequently to the given device.
     * Implement exponential backoff and slowly retry sending messages.
     */
    case MessageRateExceeded = 'MessageRateExceeded';

    /**
     * There is an issue with your FCM push credentials.
     * There are two pieces to FCM push credentials: your FCM server key, and your google-services.json file.
     * Both must be associated with the same sender ID.
     * You can find your sender ID in the same place you find your server key.
     * Check that the server key is the same as the one returned from running expo push:android:show,
     * and that the sender ID is the same as the one in your project's google-services.json file (under project_number).
     */
    case MismatchSenderId = 'MismatchSenderId';

    /**
     * Your push notification credentials for your standalone app are invalid (ex: you may have revoked them).
     * Run expo build:ios -c to regenerate new push notification credentials for iOS.
     * If you revoke an APN key, all apps that rely on that key will no longer be able to send
     * or receive push notifications until you upload a new key to replace it.
     * Uploading a new APN key will not change your users' Expo Push Tokens.
     */
    case InvalidCredentials = 'InvalidCredentials';

    /**
     * Assert whether the error type is DeviceNotRegistered.
     */
    public function isDeviceNotRegistered(): bool
    {
        return $this === self::DeviceNotRegistered;
    }

    /**
     * Assert whether the error type is MessageTooBig.
     */
    public function isMessageTooBig(): bool
    {
        return $this === self::MessageTooBig;
    }

    /**
     * Assert whether the error type is MessageRateExceeded.
     */
    public function isMessageRateExceeded(): bool
    {
        return $this === self::MessageRateExceeded;
    }

    /**
     * Assert whether the error type is MismatchSenderId.
     */
    public function isMismatchSenderId(): bool
    {
        return $this === self::MismatchSenderId;
    }

    /**
     * Assert whether the error type is InvalidCredentials.
     */
    public function isInvalidCredentials(): bool
    {
        return $this === self::InvalidCredentials;
    }
}
