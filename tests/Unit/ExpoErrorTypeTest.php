<?php declare(strict_types=1);

namespace Tests\Unit;

use NotificationChannels\Expo\ExpoErrorType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExpoErrorTypeTest extends TestCase
{
    #[Test]
    public function it_is_assertable(): void
    {
        $type = ExpoErrorType::MessageTooBig;

        $this->assertTrue($type->isMessageTooBig());
        $this->assertFalse($type->isDeviceNotRegistered());
    }

    #[DataProvider('errors')]
    #[Test]
    public function it_can_be_instantiated_using_the_backed_values(string $error): void
    {
        $instance = ExpoErrorType::from($error);

        $this->assertSame($error, $instance->value);
    }

    public static function errors(): array
    {
        return [
            ['DeviceNotRegistered'],
            ['MessageTooBig'],
            ['MessageRateExceeded'],
            ['MismatchSenderId'],
            ['InvalidCredentials'],
        ];
    }
}
