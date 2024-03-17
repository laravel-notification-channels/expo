<?php declare(strict_types=1);

namespace Tests\Integration;

use NotificationChannels\Expo\Gateway\ExpoGateway;
use NotificationChannels\Expo\Gateway\ExpoGatewayUsingGuzzle;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Unit\ExpoGatewayContractTests;

#[Group('network')]
final class ExpoGatewayUsingGuzzleTest extends TestCase
{
    use ExpoGatewayContractTests;

    protected function getInstance(): ExpoGateway
    {
        return new ExpoGatewayUsingGuzzle();
    }
}
