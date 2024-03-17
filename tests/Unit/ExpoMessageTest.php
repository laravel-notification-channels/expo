<?php declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use JsonSerializable;
use NotificationChannels\Expo\ExpoMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExpoMessageTest extends TestCase
{
    #[Test]
    public function it_can_be_constructed_with_a_title_and_a_body(): void
    {
        $msg = ExpoMessage::create('John', 'Cena');

        ['body' => $body, 'title' => $title] = $msg->toArray();

        $this->assertSame('John', $title);
        $this->assertSame('Cena', $body);
    }

    #[Test]
    public function it_can_set_a_badge(): void
    {
        $msg = ExpoMessage::create()->badge($value = 1337);

        ['badge' => $badge] = $msg->toArray();

        $this->assertSame($value, $badge);
    }

    #[Test]
    public function it_doesnt_allow_a_badge_below_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The badge must be greater than or equal to 0.');

        ExpoMessage::create()->badge(-1337);
    }

    #[Test]
    public function it_can_set_a_body(): void
    {
        $msgA = ExpoMessage::create()->body($value = 'Laravel, Framework');
        $msgB = ExpoMessage::create()->text($value);

        ['body' => $bodyA] = $msgA->toArray();
        ['body' => $bodyB] = $msgB->toArray();

        $this->assertSame($value, $bodyA);
        $this->assertSame($value, $bodyB);
    }

    #[Test]
    public function it_doesnt_allow_an_empty_body(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The body must not be empty.');

        ExpoMessage::create()->body('');
    }

    #[Test]
    public function it_can_set_a_category_id(): void
    {
        $msg = ExpoMessage::create()->categoryId($value = 'Laravel');

        ['categoryId' => $categoryId] = $msg->toArray();

        $this->assertSame($value, $categoryId);
    }

    #[Test]
    public function it_doesnt_allow_an_empty_category_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The categoryId must not be empty.');

        ExpoMessage::create()->categoryId('');
    }

    #[Test]
    public function it_can_set_a_channel_id(): void
    {
        $msg = ExpoMessage::create()->channelId($value = 'Laravel');

        ['channelId' => $channelId] = $msg->toArray();

        $this->assertSame($value, $channelId);
    }

    #[Test]
    public function it_doesnt_allow_an_empty_channel_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The channelId must not be empty.');

        ExpoMessage::create()->channelId('');
    }

    #[Test]
    public function it_can_set_the_json_data(): void
    {
        $msgA = ExpoMessage::create()->data($value = ['laravel' => 'framework']);
        $msgB = ExpoMessage::create()->data(new TestArrayable());
        $msgC = ExpoMessage::create()->data(new TestJsonable());
        $msgD = ExpoMessage::create()->data(new TestJsonSerializable());

        ['data' => $dataA] = $msgA->toArray();
        ['data' => $dataB] = $msgB->toArray();
        ['data' => $dataC] = $msgC->toArray();
        ['data' => $dataD] = $msgD->toArray();

        $this->assertEquals($data = json_encode($value), $dataA);
        $this->assertEquals($data, $dataB);
        $this->assertEquals($data, $dataC);
        $this->assertEquals($data, $dataD);
    }

    #[Test]
    public function it_can_set_the_priority_to_default(): void
    {
        $msgA = ExpoMessage::create()->default();
        $msgB = ExpoMessage::create()->priority('default');

        ['priority' => $priorityA] = $msgA->toArray();
        ['priority' => $priorityB] = $msgB->toArray();

        $this->assertSame($priority = 'default', $priorityA);
        $this->assertSame($priority, $priorityB);
    }

    #[Test]
    public function it_can_set_an_expiration(): void
    {
        $msgA = ExpoMessage::create()->expiresAt($expiration = time() + 60);
        $msgB = ExpoMessage::create()->expiresAt(Carbon::now()->addSeconds(60));

        ['expiration' => $expirationA] = $msgA->toArray();
        ['expiration' => $expirationB] = $msgB->toArray();

        $this->assertSame($expiration, $expirationA);
        $this->assertSame($expiration, $expirationB);
    }

    #[Test]
    public function it_doesnt_allow_an_expiration_in_the_past(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The expiration time must be in the future.');

        ExpoMessage::create()->expiresAt(time() - 60);
    }

    #[Test]
    public function it_can_set_the_priority_to_high(): void
    {
        $msgA = ExpoMessage::create()->high();
        $msgB = ExpoMessage::create()->priority('high');

        ['priority' => $priorityA] = $msgA->toArray();
        ['priority' => $priorityB] = $msgB->toArray();

        $this->assertSame($priority = 'high', $priorityA);
        $this->assertSame($priority, $priorityB);
    }

    #[Test]
    public function it_can_set_the_mutable_content(): void
    {
        $msg = ExpoMessage::create()->mutableContent();

        ['mutableContent' => $mutableContent] = $msg->toArray();

        $this->assertTrue($mutableContent);
    }

    #[Test]
    public function it_can_set_the_priority_to_normal(): void
    {
        $msgA = ExpoMessage::create()->normal();
        $msgB = ExpoMessage::create()->priority('normal');

        ['priority' => $priorityA] = $msgA->toArray();
        ['priority' => $priorityB] = $msgB->toArray();

        $this->assertSame($priority = 'normal', $priorityA);
        $this->assertSame($priority, $priorityB);
    }

    #[Test]
    public function it_can_play_a_sound(): void
    {
        $msg = ExpoMessage::create()->playSound();

        ['sound' => $sound] = $msg->toArray();

        $this->assertSame('default', $sound);
    }

    #[Test]
    public function it_can_set_a_priority(): void
    {
        $msg = ExpoMessage::create()->priority('HIGH');

        ['priority' => $priority] = $msg->toArray();

        $this->assertSame('high', $priority);
    }

    #[Test]
    public function it_doesnt_allow_an_invalid_priority(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The priority must be default, normal or high.');

        ExpoMessage::create()->priority('extreme');
    }

    #[Test]
    public function it_can_set_a_subtitle(): void
    {
        $msg = ExpoMessage::create()->subtitle($value = "You can't see me");

        ['subtitle' => $subtitle] = $msg->toArray();

        $this->assertSame($value, $subtitle);
    }

    #[Test]
    public function it_doesnt_allow_an_empty_subtitle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The subtitle must not be empty.');

        ExpoMessage::create()->subtitle('');
    }

    #[Test]
    public function it_can_set_a_title(): void
    {
        $msg = ExpoMessage::create()->title($value = "You can't see me");

        ['title' => $title] = $msg->toArray();

        $this->assertSame($value, $title);
    }

    #[Test]
    public function it_doesnt_allow_an_empty_title(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The title must not be empty.');

        ExpoMessage::create()->title('');
    }

    #[Test]
    public function it_can_set_a_ttl(): void
    {
        $msgA = ExpoMessage::create()->ttl(60);
        $msgB = ExpoMessage::create()->expiresIn(45);

        ['ttl' => $ttlA] = $msgA->toArray();
        ['ttl' => $ttlB] = $msgB->toArray();

        $this->assertSame(60, $ttlA);
        $this->assertSame(45, $ttlB);
    }

    #[Test]
    public function it_doesnt_allow_zero_or_a_negative_ttl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The TTL must be greater than 0.');

        ExpoMessage::create()->ttl(0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The TTL must be greater than 0.');

        ExpoMessage::create()->ttl(-60);
    }

    #[Test]
    public function it_is_arrayable_and_json_serializable(): void
    {
        $msg = ExpoMessage::create('Exponent', 'Firebase Cloud Messaging')
            ->badge(3)
            ->playSound()
            ->ttl(120)
            ->high();

        $arrayable = $msg->toArray();
        $jsonSerializable = $msg->jsonSerialize();

        $this->assertSame($data = [
            'title' => 'Exponent',
            'body' => 'Firebase Cloud Messaging',
            'ttl' => 120,
            'priority' => 'high',
            'sound' => 'default',
            'badge' => 3,
            'mutableContent' => false,
        ], $arrayable);

        $this->assertSame($data, $jsonSerializable);
    }
}

final class TestArrayable implements Arrayable
{
    public function toArray(): array
    {
        return ['laravel' => 'framework'];
    }
}

final class TestJsonable implements Jsonable
{
    public function toJson($options = 0): string
    {
        return json_encode(['laravel' => 'framework'], $options);
    }
}

final class TestJsonSerializable implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return ['laravel' => 'framework'];
    }
}
