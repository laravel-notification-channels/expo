<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Events\Dispatcher;
use Illuminate\Events\NullDispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Testing\Fakes\EventFake;
use NotificationChannels\Expo\Events\ExpoNotificationFailed;
use NotificationChannels\Expo\Events\ExpoNotificationSent;
use NotificationChannels\Expo\Exceptions\CouldNotSendNotification;
use NotificationChannels\Expo\ExpoChannel;
use NotificationChannels\Expo\ExpoError;
use NotificationChannels\Expo\ExpoMessage;
use NotificationChannels\Expo\ExpoPushToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\InMemoryExpoGateway;

final class ChannelTest extends TestCase
{
    private ExpoChannel $channel;

    private InMemoryExpoGateway $gateway;

    private EventFake $events;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new InMemoryExpoGateway;
        $this->events = new EventFake(new NullDispatcher(new Dispatcher));
        $this->channel = new ExpoChannel($this->gateway, $this->events);
    }

    #[Test]
    public function it_can_send_a_push_notification(): void
    {
        $notifiable = new Customer;
        $notification = new FoodWasDelivered;

        $this->gateway->assertNothingSent();

        $this->channel->send($notifiable, $notification);

        $this->gateway->assertSent($notifiable->routeNotificationForExpo(), $notification->toExpo($notifiable));
        $this->events->assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    public function it_throws_if_the_service_responds_with_an_unexpected_error(): void
    {
        $this->expectException(CouldNotSendNotification::class);
        $this->expectExceptionMessage('Expo responded with an error: Something went wrong.');

        $this->gateway->bail('Something went wrong.');

        $this->channel->send(new Customer, new FoodWasDelivered);
    }

    #[Test]
    public function it_dispatches_failed_events_when_something_goes_wrong(): void
    {
        $notifiable = new FraudulentCustomer;
        $notification = new FoodWasDelivered;

        $this->events->assertNothingDispatched();

        $this->channel->send($notifiable, $notification);

        $this->events->assertDispatched(
            NotificationFailed::class,
            static fn (NotificationFailed $event) => $event->channel === 'expo'
                && $event->data['error'] instanceof ExpoError
        );

        $this->events->assertDispatched(
            ExpoNotificationFailed::class,
            static fn (ExpoNotificationFailed $event) => $event->notifiable === $notifiable
                && $event->notification === $notification
        );
    }

    #[Test]
    public function it_doesnt_send_any_notifications_if_the_token_is_null(): void
    {
        $notifiable = new NullCustomer;
        $notification = new FoodWasDelivered;

        $this->channel->send($notifiable, $notification);

        $this->gateway->assertNothingSent();
    }

    #[Test]
    public function it_doesnt_send_any_notifications_if_the_token_collection_is_empty(): void
    {
        $notifiable = new EmptyCollectionCustomer;
        $notification = new FoodWasDelivered;

        $this->channel->send($notifiable, $notification);

        $this->gateway->assertNothingSent();
    }

    #[Test]
    public function it_throws_if_the_notification_doesnt_provide_a_message(): void
    {
        $this->expectException(CouldNotSendNotification::class);
        $this->expectExceptionMessage('Notification is missing the toExpo method.');

        $this->channel->send(new Customer, new CarHasCrashed);
    }

    #[Test]
    public function it_throws_if_the_notifiable_is_invalid(): void
    {
        $this->expectException(CouldNotSendNotification::class);
        $this->expectExceptionMessage('You must provide an instance of Notifiable.');

        $this->channel->send(new Guest, new FoodWasDelivered);
    }

    #[Test]
    public function expo_notification_sent_event_is_dispatched_with_token_ticket_map(): void
    {
        $notifiable = new TwoTokenCustomer;
        $notification = new FoodWasDelivered;

        $this->channel->send($notifiable, $notification);

        $this->events->assertDispatched(
            ExpoNotificationSent::class,
            static function (ExpoNotificationSent $event) use ($notifiable, $notification): bool {
                return $event->notifiable === $notifiable
                    && $event->notification === $notification
                    && $event->tickets === [
                        (string) ExpoPushToken::make(InMemoryExpoGateway::VALID_TOKEN) => 'ticket-0',
                        (string) ExpoPushToken::make(InMemoryExpoGateway::SECOND_VALID_TOKEN) => 'ticket-1',
                    ];
            }
        );
    }

    #[Test]
    public function sent_event_carries_the_original_notification_instance(): void
    {
        $notifiable = new Customer;
        $notification = new FoodWasDelivered;

        $this->channel->send($notifiable, $notification);

        $this->events->assertDispatched(
            ExpoNotificationSent::class,
            static fn (ExpoNotificationSent $event) => $event->notification === $notification
        );
    }

    #[Test]
    public function sent_event_is_not_dispatched_when_response_is_failed_or_fatal(): void
    {
        $this->channel->send(new FraudulentCustomer, new FoodWasDelivered);

        $this->events->assertNotDispatched(ExpoNotificationSent::class);

        try {
            $this->gateway->bail('Boom');
            $this->channel->send(new Customer, new FoodWasDelivered);
        } catch (CouldNotSendNotification) {
            // expected
        }

        $this->events->assertNotDispatched(ExpoNotificationSent::class);
    }
}

final class FoodWasDelivered extends Notification
{
    public function toExpo(mixed $notifiable): ExpoMessage
    {
        return ExpoMessage::create('Food Delivered')
            ->body('Your food was delivered on time!')
            ->playSound();
    }
}

final class CarHasCrashed extends Notification
{
}

final class Guest
{
}

final class Customer
{
    use Notifiable;

    public function routeNotificationForExpo(): ExpoPushToken
    {
        return ExpoPushToken::make(InMemoryExpoGateway::VALID_TOKEN);
    }
}

final class EmptyCollectionCustomer
{
    use Notifiable;

    public function routeNotificationForExpo(): Collection
    {
        return Collection::make();
    }
}

final class FraudulentCustomer
{
    use Notifiable;

    public function routeNotificationForExpo(): ExpoPushToken
    {
        return ExpoPushToken::make('ExpoPushToken[RmddzXcd66CsTIkQnCpYPa]');
    }
}

final class NullCustomer
{
    use Notifiable;

    public function routeNotificationForExpo(): null
    {
        return null;
    }
}

final class TwoTokenCustomer
{
    use Notifiable;

    /** @return array<int, ExpoPushToken> */
    public function routeNotificationForExpo(): array
    {
        return [
            ExpoPushToken::make(InMemoryExpoGateway::VALID_TOKEN),
            ExpoPushToken::make(InMemoryExpoGateway::SECOND_VALID_TOKEN),
        ];
    }
}
