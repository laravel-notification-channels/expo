<?php declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use NotificationChannels\Expo\Validation\ExpoPushTokenRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\ExpoTokensDataset;

final class ValidationRuleTest extends TestCase
{
    use ExpoTokensDataset;

    #[Test]
    public function it_fails_due_to_data_type(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('validation.string');

        $validator = new Validator($this->trans(), ['token' => 12345], ['token' => ExpoPushTokenRule::make()]);
        $validator->validate();
    }

    #[DataProvider('invalid')]
    #[Test]
    public function it_fails(string $token): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('validation.regex');

        $validator = new Validator($this->trans(), compact('token'), ['token' => ExpoPushTokenRule::make()]);
        $validator->validate();
    }

    #[DataProvider('valid')]
    #[Test]
    public function it_passes(string $token): void
    {
        $validator = new Validator($this->trans(), compact('token'), ['token' => ExpoPushTokenRule::make()]);

        $this->assertTrue($validator->passes());
    }

    private function trans(): Translator
    {
        return new Translator(new ArrayLoader(), 'en');
    }
}
