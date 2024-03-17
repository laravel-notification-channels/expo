<?php declare(strict_types=1);

namespace NotificationChannels\Expo;

final readonly class ExpoError
{
    /**
     * Create an ExpoError instance.
     */
    private function __construct(
        public ExpoErrorType $type,
        public ExpoPushToken $token,
        public string $message,
    ) {}

    /**
     * @see __construct()
     */
    public static function make(ExpoErrorType $type, ExpoPushToken $token, string $message): ExpoError
    {
        return new self($type, $token, $message);
    }
}
