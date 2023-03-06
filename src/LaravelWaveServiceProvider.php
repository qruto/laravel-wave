<?php

namespace Qruto\LaravelWave;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Event;
use Qruto\LaravelWave\Commands\SsePingCommand;
use Qruto\LaravelWave\Events\SseConnectionClosedEvent;
use Qruto\LaravelWave\Listeners\RemoveStoredConnectionListener;
use Qruto\LaravelWave\Storage\BroadcastEventHistory;
use Qruto\LaravelWave\Storage\BroadcastEventHistoryCached;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRedisRepository;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRepository;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelWaveServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-wave')
            ->hasConfigFile()
            ->hasRoute('routes')
            ->hasCommand(SsePingCommand::class);
    }

    public function registeringPackage()
    {
        $redisConnectionName = config('broadcasting.connections.redis.connection');

        config()->set("database.redis.$redisConnectionName-subscription", config("database.redis.$redisConnectionName"));

        $this->app->bind(BroadcastEventHistory::class, BroadcastEventHistoryCached::class);

        $this->app->extend(BroadcastManager::class, fn($service, $app) => new BroadcastManagerExtended($app));

        $this->app->bind(ServerSentEventSubscriber::class, RedisSubscriber::class);
        $this->app->bind(PresenceChannelUsersRepository::class, PresenceChannelUsersRedisRepository::class);
    }

    public function bootingPackage()
    {
        Event::listen(
            SseConnectionClosedEvent::class,
            [RemoveStoredConnectionListener::class, 'handle']
        );
    }
}
