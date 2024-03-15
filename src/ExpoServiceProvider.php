<?php declare(strict_types=1);

namespace NotificationChannels\Expo;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use NotificationChannels\Expo\Gateway\ExpoGateway;
use NotificationChannels\Expo\Gateway\ExpoGatewayUsingGuzzle;

final class ExpoServiceProvider extends ServiceProvider
{
    /**
     * Register the Expo application services.
     */
    public function register(): void
    {
        $this->app->bind(ExpoGateway::class, $this->createExpoGateway(...));
        $this->app->singleton(ExpoChannel::class);

        $this->callAfterResolving(ChannelManager::class, $this->extendManager(...));
    }

    /**
     * Create a new ExpoGateway instance.
     */
    private function createExpoGateway(Application $app): ExpoGatewayUsingGuzzle
    {
        /** @var Repository $config */
        $config = $app->make(Repository::class);

        $accessToken = $config->get('services.expo.access_token');

        if (! is_null($accessToken) && ! is_string($accessToken)) {
            throw new InvalidArgumentException('The provided access token is not a valid Expo Access Token.');
        }

        return new ExpoGatewayUsingGuzzle($accessToken);
    }

    /**
     * Extend the ChannelManager with ExpoChannel.
     */
    private function extendManager(ChannelManager $cm): void
    {
        $cm->extend(ExpoChannel::NAME, static fn (Application $app) => $app->make(ExpoChannel::class));
    }
}
