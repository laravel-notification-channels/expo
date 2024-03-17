<?php declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use NotificationChannels\Expo\ExpoMessage;
use NotificationChannels\Expo\ExpoPushToken;
use NotificationChannels\Expo\Gateway\ExpoEnvelope;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvelopeTest extends TestCase
{
    #[Test]
    public function it_can_make_an_instance(): void
    {
        $envelope = ExpoEnvelope::make($this->recipients(), $this->message());

        $this->assertInstanceOf(ExpoEnvelope::class, $envelope);
    }

    #[Test]
    public function it_doesnt_allow_creation_if_there_are_no_recipients(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There must be at least 1 recipient.');

        ExpoEnvelope::make([], $this->message());
    }

    #[Test]
    public function it_is_arrayable_and_jsonable(): void
    {
        $envelope = ExpoEnvelope::make($this->recipients(), $this->message());

        $array = $envelope->toArray();

        $this->assertSame($data = [
            'title' => 'iOS',
            'body' => 'Android',
            'priority' => 'default',
            'sound' => 'default',
            'badge' => 1337,
            'mutableContent' => false,
            'to' => ['ExponentPushToken[FtT1dBIc5Wp92HEGuJUhL4]'],
        ], $array);

        $this->assertSame(json_encode($data), $envelope->toJson());
    }

    private function recipients(): array
    {
        return [ExpoPushToken::make('ExponentPushToken[FtT1dBIc5Wp92HEGuJUhL4]')];
    }

    private function message(): ExpoMessage
    {
        return ExpoMessage::create('iOS', 'Android')
            ->playSound()
            ->badge(1337);
    }
}
