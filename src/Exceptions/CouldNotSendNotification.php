<?php declare(strict_types=1);

namespace NotificationChannels\Expo\Exceptions;

use Exception;

final class CouldNotSendNotification extends Exception
{
    public static function becauseNotifiableIsInvalid(): self
    {
        return new self('You must provide an instance of Notifiable.');
    }

    public static function becauseTheMessageIsMissing(): self
    {
        return new self('Notification is missing the toExpo method.');
    }

    public static function becauseTheServiceRespondedWithAnError(string $message): self
    {
        return new self("Expo responded with an error: {$message}");
    }
}
