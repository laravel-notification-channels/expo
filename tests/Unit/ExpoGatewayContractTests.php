<?php declare(strict_types=1);

namespace Tests\Unit;

use NotificationChannels\Expo\ExpoMessage;
use NotificationChannels\Expo\ExpoPushToken;
use NotificationChannels\Expo\Gateway\ExpoEnvelope;
use NotificationChannels\Expo\Gateway\ExpoGateway;
use PHPUnit\Framework\Attributes\Test;

trait ExpoGatewayContractTests
{
    abstract protected function getInstance(): ExpoGateway;

    #[Test]
    public function it_responds_with_failure_when_invalid_tokens_are_supplied(): void
    {
        $envelope = ExpoEnvelope::make([
            ExpoPushToken::make('ExpoPushToken[Wi54gvIrap4SDW4Dsh6b0h]'),
            $token = ExpoPushToken::make('ExpoPushToken[zblQYn7ReoYrLoHYsXSe0q]'),
        ], ExpoMessage::create('John', 'Cena'));

        $response = $this->getInstance()->sendPushNotifications($envelope);

        $this->assertTrue($response->isFailure());
        $this->assertCount(2, $errors = $response->errors());

        [, $error] = $errors;

        $this->assertTrue($error->token->equals($token));
        $this->assertTrue($error->type->isDeviceNotRegistered());
    }
}
