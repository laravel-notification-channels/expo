<?php declare(strict_types=1);

namespace Tests\Unit;

use NotificationChannels\Expo\ExpoError;
use NotificationChannels\Expo\ExpoErrorType;
use NotificationChannels\Expo\ExpoPushToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ErrorTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $error = ExpoError::make(
            ExpoErrorType::InvalidCredentials,
            ExpoPushToken::make('ExpoPushToken[abcdefgh]'),
            'The credentials are invalid'
        );

        $this->assertTrue($error->token->equals('ExpoPushToken[abcdefgh]'));
        $this->assertTrue($error->type->isInvalidCredentials());
        $this->assertSame('The credentials are invalid', $error->message);
    }
}
