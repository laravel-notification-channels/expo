<?php declare(strict_types=1);

namespace NotificationChannels\Expo\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use NotificationChannels\Expo\ExpoPushToken;

final readonly class AsExpoPushToken implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values to an ExpoPushToken.
     */
    public function get($model, string $key, $value, array $attributes): ?ExpoPushToken
    {
        return match (true) {
            is_string($value) => ExpoPushToken::make($value),
            is_null($value), $value instanceof ExpoPushToken => $value,
            default => throw new InvalidArgumentException('The given value cannot be cast to an instance of ExpoPushToken.'),
        };
    }

    /**
     * Transform the attribute to its underlying model values from an ExpoPushToken.
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        $value = $this->get($model, $key, $value, $attributes);

        return $value instanceof ExpoPushToken ? $value->asString() : $value;
    }
}
