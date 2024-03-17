<?php declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use NotificationChannels\Expo\Casts\AsExpoPushToken;
use NotificationChannels\Expo\ExpoPushToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CastingTest extends TestCase
{
    #[Test]
    public function it_can_get_an_attribute_as_an_expo_push_token(): void
    {
        $user = new User(['expo_token' => $token = 'ExponentPushToken[FtT1dBIc5Wp92HEGuJUhL4]']);
        $notifiable = new Notifiable(['expo_token' => $token]);

        $this->assertInstanceOf(ExpoPushToken::class, $user->expo_token);
        $this->assertInstanceOf(ExpoPushToken::class, $notifiable->expo_token);
        $this->assertEquals($token, $user->expo_token);
        $this->assertEquals($token, $notifiable->expo_token);
    }

    #[Test]
    public function it_can_set_an_expo_push_token_on_an_attribute(): void
    {
        $user = new User();
        $notifiable = new Notifiable();

        $user->expo_token = $token = 'ExponentPushToken[FtT1dBIc5Wp92HEGuJUhL4]';
        $notifiable->expo_token = $token;

        $this->assertInstanceOf(ExpoPushToken::class, $user->expo_token);
        $this->assertInstanceOf(ExpoPushToken::class, $notifiable->expo_token);
        $this->assertEquals($token, $user->expo_token);
        $this->assertEquals($token, $notifiable->expo_token);
    }

    #[Test]
    public function it_ignores_nulls(): void
    {
        $user = new User(['expo_token' => null]);

        $this->assertNull($user->expo_token);
    }

    #[Test]
    public function it_disallows_invalid_expo_push_tokens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User(['expo_token' => 'blablabla']);
    }

    #[Test]
    public function it_disallows_invalid_data_types(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given value cannot be cast to an instance of ExpoPushToken.');

        new User(['expo_token' => 12345]);
    }
}

/*
 * @property ExpoPushToken|null $expo_token
 */
final class User extends Model
{
    protected $casts = ['expo_token' => AsExpoPushToken::class];

    protected $guarded = [];
}

/*
 * @property ExpoPushToken|null $expo_token
 */
final class Notifiable extends Model
{
    protected $casts = ['expo_token' => ExpoPushToken::class];

    protected $guarded = [];
}
