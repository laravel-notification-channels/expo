<?php declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use NotificationChannels\Expo\Casts\AsExpoPushToken;
use NotificationChannels\Expo\ExpoPushToken;
use NotificationChannels\Expo\Validation\ExpoPushTokenRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\ExpoTokensDataset;

final class ExpoPushTokenTest extends TestCase
{
    use ExpoTokensDataset;

    #[DataProvider('valid')]
    #[Test]
    public function it_can_create_an_instance(string $value): void
    {
        $token = ExpoPushToken::make($value);

        $this->assertSame($value, $token->asString());
    }

    #[DataProvider('invalid')]
    #[Test]
    public function it_doesnt_allow_invalid_tokens(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("{$value} is not a valid push token.");

        ExpoPushToken::make($value);
    }

    #[Test]
    public function it_is_equatable(): void
    {
        $tokenA = ExpoPushToken::make('ExponentPushToken[FtT1dBIc5Wp92HEGuJUhL4]');
        $tokenB = ExpoPushToken::make('ExponentPushToken[JQoRAH65GV7qZX8YUyx8Rn]');
        $tokenC = 'ExponentPushToken[JQoRAH65GV7qZX8YUyx8Rn]';

        $this->assertTrue($tokenA->equals($tokenA));
        $this->assertFalse($tokenA->equals($tokenB));
        $this->assertFalse($tokenA->equals($tokenC));
        $this->assertTrue($tokenB->equals($tokenC));
    }

    #[Test]
    public function it_is_castable(): void
    {
        $caster = ExpoPushToken::castUsing([]);

        $this->assertSame(AsExpoPushToken::class, $caster);
    }

    #[Test]
    public function it_can_be_validated(): void
    {
        $rule = ExpoPushToken::rule();

        $this->assertInstanceOf(ExpoPushTokenRule::class, $rule);
    }

    #[Test]
    public function it_is_stringable(): void
    {
        $token = ExpoPushToken::make($value = 'ExponentPushToken[FtT1dBIc5Wp92HEGuJUhL4]');

        $this->assertSame($value, $token->asString());
        $this->assertSame($value, (string) $token);
    }

    #[Test]
    public function it_is_json_serializable(): void
    {
        $token = ExpoPushToken::make($value = 'ExponentPushToken[FtT1dBIc5Wp92HEGuJUhL4]');

        $this->assertSame("\"{$value}\"", json_encode($token));
    }
}
