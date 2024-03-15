<?php declare(strict_types=1);

namespace Tests;

use NotificationChannels\Expo\ExpoServiceProvider;
use NotificationChannels\Expo\Gateway\ExpoGateway;
use Orchestra\Testbench\TestCase as TestCaseBase;

abstract class TestCase extends TestCaseBase
{
    protected function defineEnvironment($app): void
    {
        $app->bind(ExpoGateway::class, InMemoryExpoGateway::class);
    }

    protected function getPackageProviders($app): array
    {
        return [ExpoServiceProvider::class];
    }
}
