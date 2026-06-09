<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NotificationChannels\Expo\Exceptions\CouldNotGetReceipts;
use NotificationChannels\Expo\ExpoMessage;
use NotificationChannels\Expo\ExpoPushToken;
use NotificationChannels\Expo\Gateway\ExpoEnvelope;
use NotificationChannels\Expo\Gateway\ExpoGatewayUsingGuzzle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExpoGatewayUsingGuzzleTest extends TestCase
{
    #[Test]
    public function send_collects_ticket_ids_into_the_ok_response_indexed_by_recipient(): void
    {
        $gateway = $this->gatewayReturning(new Response(200, [], json_encode([
            'data' => [
                ['status' => 'ok', 'id' => 'ticket-1'],
                ['status' => 'ok', 'id' => 'ticket-2'],
            ],
        ])));

        $envelope = ExpoEnvelope::make([
            ExpoPushToken::make('ExponentPushToken[FtT1dBIc5Wp92HEGuJUhL4]'),
            ExpoPushToken::make('ExponentPushToken[GuU2eCJd6Xq03IFHvKViM5]'),
        ], ExpoMessage::create('Hi', 'There'));

        $response = $gateway->sendPushNotifications($envelope);

        $this->assertTrue($response->isOk());
        $this->assertSame(['ticket-1', 'ticket-2'], $response->tickets());
    }

    #[Test]
    public function get_receipts_posts_to_expo_and_returns_data_keyed_by_ticket(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'ticket-1' => ['status' => 'ok'],
                    'ticket-2' => ['status' => 'error', 'message' => 'oops'],
                ],
            ])),
        ]);

        $gateway = $this->gatewayWith($mock);

        $receipts = $gateway->getReceipts(['ticket-1', 'ticket-2']);

        $this->assertSame([
            'ticket-1' => ['status' => 'ok'],
            'ticket-2' => ['status' => 'error', 'message' => 'oops'],
        ], $receipts);
    }

    #[Test]
    public function get_receipts_returns_empty_array_for_empty_input(): void
    {
        $gateway = new ExpoGatewayUsingGuzzle;

        $this->assertSame([], $gateway->getReceipts([]));
    }

    #[Test]
    public function get_receipts_throws_could_not_get_receipts_on_non_200_response(): void
    {
        $gateway = $this->gatewayReturning(new Response(500, [], 'Internal Server Error'));

        $this->expectException(CouldNotGetReceipts::class);
        $this->expectExceptionMessage('Expo responded with an error while fetching receipts: Internal Server Error');

        $gateway->getReceipts(['ticket-1']);
    }

    private function gatewayReturning(Response $response): ExpoGatewayUsingGuzzle
    {
        return $this->gatewayWith(new MockHandler([$response]));
    }

    private function gatewayWith(MockHandler $handler): ExpoGatewayUsingGuzzle
    {
        return new ExpoGatewayUsingGuzzle(null, HandlerStack::create($handler));
    }
}
