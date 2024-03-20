<?php

namespace Qruto\Wave;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Event;
use Qruto\Wave\Console\Commands\BroadcastingInstallCommand;
use Qruto\Wave\Console\Commands\ConfigPublishCommand;
use Qruto\Wave\Console\Commands\ServeCommand;
use Qruto\Wave\Console\Commands\SsePingCommand;
use Qruto\Wave\Events\SseConnectionClosedEvent;
use Qruto\Wave\Listeners\RemoveStoredConnectionListener;
use Qruto\Wave\Storage\BroadcastEventHistory;
use Qruto\Wave\Storage\BroadcastEventHistoryRedisStream;
use Qruto\Wave\Storage\PresenceChannelUsersRedisRepository;
use Qruto\Wave\Storage\PresenceChannelUsersRepository;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WaveServiceProvider extends PackageServiceProvider
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
            ->hasCommand(SsePingCommand::class)
            ->hasCommand(ServeCommand::class);

        if (laravel11OrHigher()) {
            $package
                ->hasCommand(ConfigPublishCommand::class)
                ->hasCommand(BroadcastingInstallCommand::class);
        }
    }

    public function registeringPackage()
    {
        $redisConnectionName = config('broadcasting.connections.redis.connection');

        config()->set("database.redis.$redisConnectionName-subscription", config("database.redis.$redisConnectionName"));

        $this->app->bind(BroadcastEventHistory::class, BroadcastEventHistoryRedisStream::class);
        $this->app->bind(PresenceChannelEvent::class, PresenceChannelEventHandler::class);

        $this->app->extend(BroadcastManager::class, fn ($service, $app) => new BroadcastManagerExtended($app));

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
