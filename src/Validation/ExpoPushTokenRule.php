<?php declare(strict_types=1);

namespace NotificationChannels\Expo\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use NotificationChannels\Expo\ExpoPushToken;

final readonly class ExpoPushTokenRule implements ValidationRule
{
    /**
     * Create a new ExpoPushTokenRule instance.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Run the rule and determine whether the value is a valid push token.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        try {
            ExpoPushToken::make($value);
        } catch (InvalidArgumentException) {
            $fail('validation.regex')->translate();
        }
    }
}
