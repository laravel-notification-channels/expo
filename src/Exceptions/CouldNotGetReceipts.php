<?php

declare(strict_types=1);

namespace NotificationChannels\Expo\Exceptions;

use Exception;

final class CouldNotGetReceipts extends Exception
{
    public static function becauseTheServiceRespondedWithAnError(string $body): self
    {
        return new self("Expo responded with an error while fetching receipts: {$body}");
    }
}
