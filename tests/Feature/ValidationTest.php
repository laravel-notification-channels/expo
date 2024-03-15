<?php declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use NotificationChannels\Expo\ExpoPushToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ValidationTest extends TestCase
{
    protected function defineWebRoutes($router): void
    {
        $router->post('push-tokens', static function (Request $request) {
            $request->validate(['token' => ['required', ExpoPushToken::rule()]]);

            return ['message' => 'ok'];
        });
    }

    #[Test]
    public function test_string_validation(): void
    {
        $token = 123456789;

        $response = $this->postJson('push-tokens', ['token' => $token]);

        $response->assertJsonValidationErrorFor('token');
        $this->assertSame($response->json('message'), 'The token field must be a string.');
    }

    #[Test]
    public function test_format_validation(): void
    {
        $token = 'ExpoPushToken[]';

        $response = $this->postJson('push-tokens', ['token' => $token]);

        $response->assertJsonValidationErrorFor('token');
        $this->assertSame($response->json('message'), 'The token field format is invalid.');
    }

    #[Test]
    public function test_happy_path(): void
    {
        $token = 'ExpoPushToken[GO3iMZEnfkqSsOEPWG9NWv]';

        $response = $this->postJson('push-tokens', ['token' => $token]);

        $response->assertOk();
    }
}
