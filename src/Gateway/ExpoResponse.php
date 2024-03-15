<?php declare(strict_types=1);

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
     */
    private function __construct(private string $type, private array|string|null $context = null) {}

    /**
     * Create a "failed" ExpoResponse instance.
     *
     * @param $errors array<int, ExpoError>
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
     */
    public static function ok(): self
    {
        return new self(self::OK);
    }

    public function errors(): array
    {
        return is_array($this->context) ? $this->context : [];
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
