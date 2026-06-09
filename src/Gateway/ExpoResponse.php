<?php

declare(strict_types=1);

namespace NotificationChannels\Expo\Gateway;

use NotificationChannels\Expo\ExpoError;

/** @internal */
final readonly class ExpoResponse
{
    private const string FAILED = 'failed';

    private const string FATAL = 'fatal';

    private const string OK = 'ok';

    /**
     * Create a new ExpoResponse instance.
     *
     * @param  array<int, ExpoError>|array<int, string>|string|null  $context
     */
    private function __construct(private string $type, private array|string|null $context = null)
    {
        //
    }

    /**
     * Create a "failed" ExpoResponse instance.
     *
     * @param  array<int, ExpoError>  $errors
     */
    public static function failed(array $errors): self
    {
        return new self(self::FAILED, $errors);
    }

    /**
     * Create a "fatal" ExpoResponse instance.
     */
    public static function fatal(string $message): self
    {
        return new self(self::FATAL, $message);
    }

    /**
     * Create an "ok" ExpoResponse instance.
     *
     * @param  array<int, string>  $tickets  recipient index → Expo ticket UUID
     */
    public static function ok(array $tickets = []): self
    {
        return new self(self::OK, $tickets);
    }

    /**
     * @return array<int, ExpoError>
     */
    public function errors(): array
    {
        if ($this->type !== self::FAILED || ! is_array($this->context)) {
            return [];
        }

        /** @var array<int, ExpoError> */
        return $this->context;
    }

    /**
     * Return the ticket IDs of successfully accepted pushes.
     *
     * @return array<int, string> map of recipient index → Expo ticket UUID,
     *                            matching the order of $envelope->recipients
     */
    public function tickets(): array
    {
        if ($this->type !== self::OK || ! is_array($this->context)) {
            return [];
        }

        /** @var array<int, string> */
        return $this->context;
    }

    public function isFatal(): bool
    {
        return $this->type === self::FATAL;
    }

    public function isFailure(): bool
    {
        return $this->type === self::FAILED;
    }

    public function isOk(): bool
    {
        return $this->type === self::OK;
    }

    public function message(): string
    {
        return is_string($this->context) ? $this->context : '';
    }
}
