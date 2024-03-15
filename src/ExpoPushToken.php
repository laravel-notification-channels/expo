<?php declare(strict_types=1);

namespace NotificationChannels\Expo;

use Illuminate\Contracts\Database\Eloquent\Castable;
use InvalidArgumentException;
use JsonSerializable;
use NotificationChannels\Expo\Casts\AsExpoPushToken;
use NotificationChannels\Expo\Validation\ExpoPushTokenRule;
use Stringable;

final readonly class ExpoPushToken implements Castable, JsonSerializable, Stringable
{
    /**
     * The minimum acceptable length of a push token.
     */
    public const int MIN_LENGTH = 16;

    /**
     * The string representation of the push token.
     */
    private string $value;

    /**
     * Create a new ExpoPushToken instance.
     *
     * @throws InvalidArgumentException
     */
    private function __construct(string $value)
    {
        if (mb_strlen($value) < self::MIN_LENGTH) {
            throw new InvalidArgumentException("{$value} is not a valid push token.");
        }

        if (! str_starts_with($value, 'ExponentPushToken[') && ! str_starts_with($value, 'ExpoPushToken[')) {
            throw new InvalidArgumentException("{$value} is not a valid push token.");
        }

        if (! str_ends_with($value, ']')) {
            throw new InvalidArgumentException("{$value} is not a valid push token.");
        }

        $this->value = $value;
    }

    /**
     * Get the FQCN of the caster to use when casting from / to an ExpoPushToken.
     */
    public static function castUsing(array $arguments): string
    {
        return AsExpoPushToken::class;
    }

    /**
     * @see __construct()
     */
    public static function make(string $token): self
    {
        return new self($token);
    }

    /**
     * Get the rule to validate an ExpoPushToken.
     */
    public static function rule(): ExpoPushTokenRule
    {
        return ExpoPushTokenRule::make();
    }

    /**
     * @see __toString()
     */
    public function asString(): string
    {
        return $this->value;
    }

    /**
     * Determine whether a given token is equal.
     */
    public function equals(self|string $other): bool
    {
        if ($other instanceof self) {
            $other = $other->asString();
        }

        return $other === $this->asString();
    }

    /**
     * Convert the ExpoPushToken instance to its JSON representation.
     */
    public function jsonSerialize(): string
    {
        return $this->asString();
    }

    /**
     * Get the string representation of the push token.
     */
    public function __toString(): string
    {
        return $this->asString();
    }
}
