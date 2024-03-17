<?php declare(strict_types=1);

namespace NotificationChannels\Expo\Gateway;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use NotificationChannels\Expo\ExpoMessage;
use NotificationChannels\Expo\ExpoPushToken;

/** @internal */
final readonly class ExpoEnvelope implements Arrayable, Jsonable
{
    /**
     * Create a new ExpoEnvelope instance.
     *
     * @param array<int, ExpoPushToken> $recipients
     */
    private function __construct(public array $recipients, public ExpoMessage $message)
    {
        if (! count($recipients)) {
            throw new InvalidArgumentException('There must be at least 1 recipient.');
        }
    }

    /**
     * @see __construct()
     */
    public static function make(array $recipients, ExpoMessage $message): self
    {
        return new self($recipients, $message);
    }

    /**
     * Get the ExpoEnvelope instance as an array.
     */
    public function toArray(): array
    {
        $envelope = $this->message->toArray();
        $envelope['to'] = array_map(static fn (ExpoPushToken $token) => $token->asString(), $this->recipients);

        return $envelope;
    }

    /**
     * Convert the ExpoEnvelope instance to its JSON representation.
     *
     * @param int $options
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options) ?: '';
    }
}
