<?php declare(strict_types=1);

namespace Tests\Unit;

use NotificationChannels\Expo\Gateway\ExpoResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExpoResponseTest extends TestCase
{
    #[Test]
    public function it_can_create_a_failure_response(): void
    {
        $response = ExpoResponse::failed($errors = ['status' => 500]);

        $this->assertTrue($response->isFailure());
        $this->assertFalse($response->isFatal());
        $this->assertFalse($response->isOk());
        $this->assertSame($errors, $response->errors());
        $this->assertSame('', $response->message());
    }

    #[Test]
    public function it_can_create_a_fatal_response(): void
    {
        $response = ExpoResponse::fatal($message = 'Something went horribly wrong.');

        $this->assertTrue($response->isFatal());
        $this->assertFalse($response->isFailure());
        $this->assertFalse($response->isOk());
        $this->assertSame($message, $response->message());
        $this->assertSame([], $response->errors());
    }

    #[Test]
    public function it_can_create_an_ok_response(): void
    {
        $response = ExpoResponse::ok();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->isFailure());
        $this->assertFalse($response->isFatal());
        $this->assertSame('', $response->message());
        $this->assertSame([], $response->errors());
    }
}
